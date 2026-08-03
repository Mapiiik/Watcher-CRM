<?php
declare(strict_types=1);

namespace Bookkeeping\Test\TestCase\Provider\Eurofaktura;

use Bookkeeping\Provider\Eurofaktura\JsonParser;
use Cake\TestSuite\TestCase;
use RuntimeException;

/**
 * Bookkeeping\Provider\Eurofaktura\JsonParser Test Case
 *
 * The parser turns what the accounting API answers into invoice drafts, and what it makes of those
 * numbers is what the application then believes about a customer's debt. It reads an array and
 * returns objects, needing neither a database nor the API to be reachable, so the tests hand it the
 * responses directly.
 */
class JsonParserTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Bookkeeping\Provider\Eurofaktura\JsonParser
     */
    private JsonParser $parser;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new JsonParser();
    }

    /**
     * A successful answer carrying the given invoices.
     *
     * @param array<int, array<string, mixed>> $invoices Invoices the API is to have answered with.
     * @return array<string, mixed>
     */
    private function answer(array $invoices): array
    {
        return ['response' => ['status' => 'ok', 'result' => $invoices]];
    }

    /**
     * What the API says about an invoice reaches the draft under the names the application knows it
     * by. The two identifiers are worth watching: the reference is the variable symbol a payment
     * arrives under, and the document id is what the invoice is fetched again by.
     *
     * @return void
     * @link \Bookkeeping\Provider\Eurofaktura\JsonParser::parseSalesInvoiceList()
     */
    public function testParseReadsAnInvoice(): void
    {
        $drafts = $this->parser->parseSalesInvoiceList($this->answer([
            [
                'number' => '2026000123',
                'reference' => '9001',
                'ourContractNumber' => 'S-2026-0001',
                'documentID' => '4711',
                'date' => '2026-01-15',
                'paymentDueDate' => '2026-01-29',
                'documentAmount' => '121.00',
                'amountLeftToBePaid' => '21.00',
            ],
        ]));

        $this->assertCount(1, $drafts);
        $draft = $drafts[0];

        $this->assertSame('2026000123', $draft->number);
        $this->assertSame('9001', $draft->variableSymbol);
        $this->assertSame('S-2026-0001', $draft->customerNumber);
        $this->assertSame('4711', $draft->accountingIdentifier);
        $this->assertNotNull($draft->creationDate);
        $this->assertSame('2026-01-15', $draft->creationDate->format('Y-m-d'));
        $this->assertNotNull($draft->total);
        $this->assertSame(121.0, $draft->total->toFloat());
        $this->assertNotNull($draft->debt);
        $this->assertSame(21.0, $draft->debt->toFloat());
        $this->assertSame('eurofaktura', $draft->metadata['source']);
    }

    /**
     * Where no contract number came with the invoice, the digits of the reference stand in for it.
     * That is how an invoice issued outside the application is still attached to a customer.
     *
     * @return void
     * @link \Bookkeeping\Provider\Eurofaktura\JsonParser::parseSalesInvoiceList()
     */
    public function testTheCustomerFallsBackToTheDigitsOfTheReference(): void
    {
        $drafts = $this->parser->parseSalesInvoiceList($this->answer([
            ['reference' => 'VS-9001/2026'],
        ]));

        $this->assertSame('90012026', $drafts[0]->customerNumber);
    }

    /**
     * An invoice paid in instalments is paid on the day of the last one - that is the date the
     * debt was settled, and taking the first would report it settled before it was.
     *
     * @return void
     * @link \Bookkeeping\Provider\Eurofaktura\JsonParser::parseSalesInvoiceList()
     */
    public function testThePaymentDateIsTheLatestOfThePayments(): void
    {
        $drafts = $this->parser->parseSalesInvoiceList($this->answer([
            [
                'reference' => '9001',
                'PaymentRecords' => [
                    ['paymentDate' => '2026-01-20'],
                    ['paymentDate' => '2026-02-10'],
                    ['paymentDate' => '2026-01-31'],
                ],
            ],
        ]));

        $this->assertNotNull($drafts[0]->paymentDate);
        $this->assertSame('2026-02-10', $drafts[0]->paymentDate->format('Y-m-d'));
    }

    /**
     * A payment record without a date is passed over rather than taken as a payment made on no
     * particular day.
     *
     * @return void
     * @link \Bookkeeping\Provider\Eurofaktura\JsonParser::parseSalesInvoiceList()
     */
    public function testAPaymentWithoutADateIsPassedOver(): void
    {
        $drafts = $this->parser->parseSalesInvoiceList($this->answer([
            [
                'reference' => '9001',
                'PaymentRecords' => [
                    ['paymentDate' => null],
                    ['paymentDate' => '2026-01-20'],
                ],
            ],
        ]));

        $this->assertNotNull($drafts[0]->paymentDate);
        $this->assertSame('2026-01-20', $drafts[0]->paymentDate->format('Y-m-d'));
    }

    /**
     * An invoice owing nothing with no payment behind it is contradictory, and the draft carries
     * the contradiction rather than settling it - whoever loads it is the one who can tell which
     * of the two is wrong.
     *
     * @return void
     * @link \Bookkeeping\Provider\Eurofaktura\JsonParser::parseSalesInvoiceList()
     */
    public function testAnInvoicePaidWithoutADateIsWarnedAbout(): void
    {
        $drafts = $this->parser->parseSalesInvoiceList($this->answer([
            ['reference' => '9001', 'amountLeftToBePaid' => '0.00'],
        ]));

        $this->assertTrue($drafts[0]->hasWarnings());
    }

    /**
     * The invoice text is built from what was written on it and what was billed, so that a listing
     * says what the money was for.
     *
     * @return void
     * @link \Bookkeeping\Provider\Eurofaktura\JsonParser::parseSalesInvoiceList()
     */
    public function testTheTextIsBuiltFromTheIntroductionAndTheItems(): void
    {
        $drafts = $this->parser->parseSalesInvoiceList($this->answer([
            [
                'reference' => '9001',
                'introductionText' => 'Billing for 1/2026',
                'Items' => [
                    ['quantity' => 1, 'productName' => 'Internet access'],
                    ['quantity' => 3, 'productName' => 'Public IP address'],
                ],
            ],
        ]));

        $this->assertSame(
            'Billing for 1/2026, Internet access, 3x Public IP address',
            $drafts[0]->text,
        );
    }

    /**
     * The period asked about having no invoices in it is an ordinary answer, not a failure - the
     * API says so with an error the parser knows to read as an empty list.
     *
     * @return void
     * @link \Bookkeeping\Provider\Eurofaktura\JsonParser::parseSalesInvoiceList()
     */
    public function testNoDocumentsFoundIsAnEmptyResultRatherThanAFailure(): void
    {
        $drafts = $this->parser->parseSalesInvoiceList([
            'response' => ['status' => 'error', 'description' => 'noDocumentsFound'],
        ]);

        $this->assertSame([], $drafts);
    }

    /**
     * Any other error is refused rather than read as an empty list, which would quietly report
     * that a customer owes nothing.
     *
     * @return void
     * @link \Bookkeeping\Provider\Eurofaktura\JsonParser::parseSalesInvoiceList()
     */
    public function testAnErrorIsRefused(): void
    {
        $this->expectException(RuntimeException::class);

        $this->parser->parseSalesInvoiceList([
            'response' => ['status' => 'error', 'description' => 'authenticationFailed'],
        ]);
    }

    /**
     * An answer that is not shaped like one is refused for the same reason.
     *
     * @return void
     * @link \Bookkeeping\Provider\Eurofaktura\JsonParser::parseSalesInvoiceList()
     */
    public function testAnAnswerWithoutAResponseIsRefused(): void
    {
        $this->expectException(RuntimeException::class);

        $this->parser->parseSalesInvoiceList(['something' => 'else']);
    }
}
