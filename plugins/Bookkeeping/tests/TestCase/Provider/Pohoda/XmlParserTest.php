<?php
declare(strict_types=1);

namespace Bookkeeping\Test\TestCase\Provider\Pohoda;

use Bookkeeping\Provider\Pohoda\XmlParser;
use Cake\TestSuite\TestCase;
use RuntimeException;
use SimpleXMLElement;

/**
 * Bookkeeping\Provider\Pohoda\XmlParser Test Case
 *
 * The parser turns what the accounting system answers into invoice drafts, and what it makes of
 * those numbers is what the application then believes about a customer's debt. It reads XML and
 * returns objects, needing neither a database nor the accounting system to be reachable, so the
 * tests hand it the XML directly.
 *
 * The documents below are written out rather than loaded from a captured response: what each one
 * says is the point of the test that uses it, and a fixture file would hide it.
 */
class XmlParserTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Bookkeeping\Provider\Pohoda\XmlParser
     */
    private XmlParser $parser;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new XmlParser();
    }

    /**
     * A response carrying the given invoice bodies, wrapped in the namespaces the parser registers.
     *
     * @param string $invoices Invoice elements to put in the list.
     * @return \SimpleXMLElement
     */
    private function response(string $invoices): SimpleXMLElement
    {
        return new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<rsp:responsePack'
            . ' xmlns:rsp="http://www.stormware.cz/schema/version_2/response.xsd"'
            . ' xmlns:lst="http://www.stormware.cz/schema/version_2/list.xsd"'
            . ' xmlns:inv="http://www.stormware.cz/schema/version_2/invoice.xsd"'
            . ' xmlns:typ="http://www.stormware.cz/schema/version_2/type.xsd">'
            . '<lst:invoiceList>' . $invoices . '</lst:invoiceList>'
            . '</rsp:responsePack>',
        );
    }

    /**
     * One invoice as the accounting system writes a whole one.
     *
     * @return string
     */
    private function wholeInvoice(): string
    {
        return '<lst:invoice>'
            . '<inv:invoiceHeader>'
            . '<inv:id>4711</inv:id>'
            . '<inv:number><typ:numberRequested>2026000123</typ:numberRequested></inv:number>'
            . '<inv:symVar>9001</inv:symVar>'
            . '<inv:date>2026-01-15</inv:date>'
            . '<inv:dateDue>2026-01-29</inv:dateDue>'
            . '<inv:text>Internet access 1/2026</inv:text>'
            . '<inv:liquidation>'
            . '<typ:date>2026-01-20</typ:date>'
            . '<typ:amountHome>121.00</typ:amountHome>'
            . '</inv:liquidation>'
            . '</inv:invoiceHeader>'
            . '<inv:invoiceSummary><inv:homeCurrency>'
            . '<typ:priceNone>100.00</typ:priceNone>'
            . '<typ:priceLowSum>12.00</typ:priceLowSum>'
            . '<typ:priceHighSum>9.00</typ:priceHighSum>'
            . '</inv:homeCurrency></inv:invoiceSummary>'
            . '</lst:invoice>';
    }

    /**
     * What the accounting system says about an invoice reaches the draft under the names the
     * application knows it by.
     *
     * @return void
     * @link \Bookkeeping\Provider\Pohoda\XmlParser::parseSimpleXML()
     */
    public function testParseReadsAnInvoice(): void
    {
        $drafts = $this->parser->parseSimpleXML($this->response($this->wholeInvoice()));

        $this->assertCount(1, $drafts);
        $draft = $drafts[0];

        $this->assertSame('2026000123', $draft->number);
        $this->assertSame('9001', $draft->variableSymbol);
        $this->assertSame('4711', $draft->accountingIdentifier);
        $this->assertSame('Internet access 1/2026', $draft->text);
        $this->assertSame('pohoda-xml', $draft->metadata['source']);
    }

    /**
     * The variable symbol is also what identifies the customer. The two are read off the same
     * element, and a page that looked the customer up by one and showed the other would be
     * inventing a discrepancy that is not there.
     *
     * @return void
     * @link \Bookkeeping\Provider\Pohoda\XmlParser::parseSimpleXML()
     */
    public function testTheCustomerIsIdentifiedByTheVariableSymbol(): void
    {
        $draft = $this->parser->parseSimpleXML($this->response($this->wholeInvoice()))[0];

        $this->assertSame($draft->variableSymbol, $draft->customerNumber);
    }

    /**
     * The dates are read as dates rather than passed on as the strings they arrived in - what is
     * overdue is decided by comparing them.
     *
     * @return void
     * @link \Bookkeeping\Provider\Pohoda\XmlParser::parseSimpleXML()
     */
    public function testParseReadsTheDates(): void
    {
        $draft = $this->parser->parseSimpleXML($this->response($this->wholeInvoice()))[0];

        $this->assertNotNull($draft->creationDate);
        $this->assertNotNull($draft->dueDate);
        $this->assertNotNull($draft->paymentDate);
        $this->assertSame('2026-01-15', $draft->creationDate->format('Y-m-d'));
        $this->assertSame('2026-01-29', $draft->dueDate->format('Y-m-d'));
        $this->assertSame('2026-01-20', $draft->paymentDate->format('Y-m-d'));
    }

    /**
     * The total is the three VAT rates added up, because that is how the summary is written - what
     * is owed is never in one element to be read off.
     *
     * @return void
     * @link \Bookkeeping\Provider\Pohoda\XmlParser::parseSimpleXML()
     */
    public function testTheTotalIsTheSumOfTheVatRates(): void
    {
        $draft = $this->parser->parseSimpleXML($this->response($this->wholeInvoice()))[0];

        $this->assertNotNull($draft->total);
        $this->assertSame(121.0, $draft->total->toFloat());
    }

    /**
     * What is still owed is what the liquidation says, and it is kept apart from the total - an
     * invoice paid in part is the whole point of the debtors listing.
     *
     * @return void
     * @link \Bookkeeping\Provider\Pohoda\XmlParser::parseSimpleXML()
     */
    public function testTheDebtIsReadSeparatelyFromTheTotal(): void
    {
        $drafts = $this->parser->parseSimpleXML($this->response(
            '<lst:invoice>'
            . '<inv:invoiceHeader>'
            . '<inv:symVar>9002</inv:symVar>'
            . '<inv:liquidation><typ:amountHome>21.00</typ:amountHome></inv:liquidation>'
            . '</inv:invoiceHeader>'
            . '<inv:invoiceSummary><inv:homeCurrency>'
            . '<typ:priceNone>100.00</typ:priceNone>'
            . '</inv:homeCurrency></inv:invoiceSummary>'
            . '</lst:invoice>',
        ));

        $this->assertNotNull($drafts[0]->total);
        $this->assertNotNull($drafts[0]->debt);
        $this->assertSame(100.0, $drafts[0]->total->toFloat());
        $this->assertSame(21.0, $drafts[0]->debt->toFloat());
    }

    /**
     * An invoice nobody has paid anything on carries no liquidation at all, which is not missing
     * data - it is a debt of nothing so far, and must not be read as an unknown amount.
     *
     * @return void
     * @link \Bookkeeping\Provider\Pohoda\XmlParser::parseSimpleXML()
     */
    public function testAnInvoiceWithoutALiquidationOwesNothingYet(): void
    {
        $drafts = $this->parser->parseSimpleXML($this->response(
            '<lst:invoice>'
            . '<inv:invoiceHeader><inv:symVar>9003</inv:symVar></inv:invoiceHeader>'
            . '<inv:invoiceSummary><inv:homeCurrency>'
            . '<typ:priceNone>50.00</typ:priceNone>'
            . '</inv:homeCurrency></inv:invoiceSummary>'
            . '</lst:invoice>',
        ));

        $this->assertNotNull($drafts[0]->debt);
        $this->assertSame(0.0, $drafts[0]->debt->toFloat());
        $this->assertNull($drafts[0]->paymentDate);
    }

    /**
     * A response carrying several invoices yields all of them, in the order they arrived.
     *
     * @return void
     * @link \Bookkeeping\Provider\Pohoda\XmlParser::parseSimpleXML()
     */
    public function testParseReadsEveryInvoiceInTheResponse(): void
    {
        $one = '<lst:invoice><inv:invoiceHeader><inv:symVar>1</inv:symVar></inv:invoiceHeader>'
            . '</lst:invoice>';
        $two = '<lst:invoice><inv:invoiceHeader><inv:symVar>2</inv:symVar></inv:invoiceHeader>'
            . '</lst:invoice>';

        $drafts = $this->parser->parseSimpleXML($this->response($one . $two));

        $this->assertCount(2, $drafts);
        $this->assertSame(['1', '2'], array_map(fn($draft): ?string => $draft->variableSymbol, $drafts));
    }

    /**
     * A response with no invoices in it is an empty answer rather than an error - asking for a
     * period nothing was issued in is an ordinary thing to do.
     *
     * @return void
     * @link \Bookkeeping\Provider\Pohoda\XmlParser::parseSimpleXML()
     */
    public function testAnEmptyResponseYieldsNoDrafts(): void
    {
        $this->assertSame([], $this->parser->parseSimpleXML($this->response('')));
    }

    /**
     * A file that is not XML at all is refused rather than read as an empty answer, which would
     * quietly report that a customer owes nothing.
     *
     * @return void
     * @link \Bookkeeping\Provider\Pohoda\XmlParser::parseFile()
     */
    public function testAFileThatIsNotXmlIsRefused(): void
    {
        $path = TMP . 'pohoda-not-xml.txt';
        file_put_contents($path, 'this is not a document');

        try {
            $this->expectException(RuntimeException::class);

            $this->parser->parseFile($path);
        } finally {
            unlink($path);
        }
    }
}
