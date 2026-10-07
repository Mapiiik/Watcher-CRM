<?php
declare(strict_types=1);

namespace Bookkeeping\Test\TestCase\Provider\Pohoda;

use App\Model\Entity\AccountingProfile;
use App\Model\Entity\Address;
use App\Model\Entity\Customer;
use App\Model\Enum\AddressType;
use Bookkeeping\Model\ValueObject\InvoiceDraft;
use Bookkeeping\Provider\Pohoda\PohodaProvider;
use Bookkeeping\Provider\Pohoda\XmlExporter;
use Cake\Core\Configure;
use Cake\I18n\Date;
use Cake\TestSuite\TestCase;
use PhpCollective\DecimalObject\Decimal;
use Settings\Utility\Settings;
use SimpleXMLElement;

/**
 * Bookkeeping\Provider\Pohoda\XmlExporter Test Case
 *
 * Who an invoice is for is either the customer's card in the address book or the address written
 * out on the invoice, and which of the two is a setting. Until it is turned on the invoice has to
 * go out exactly as it always has; once it is, an invoice pointing at a card that is not there
 * does not go in at all. The tests read the file the exporter writes.
 */
class XmlExporterTest extends TestCase
{
    /**
     * Fixtures
     *
     * Only the settings, which the link to the address book is switched in.
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'plugin.Settings.Settings',
    ];

    /**
     * Where the exporter writes.
     *
     * @var string
     */
    private string $filePath;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        Configure::write('Customers.series', 117000);
        Settings::set(PohodaProvider::SETTINGS_ROOT . '.api.accounting_unit', '12345678');
        Settings::set(PohodaProvider::SETTINGS_ROOT . '.customers.code_prefix', '');

        $this->filePath = TMP . uniqid('pohoda-export-test-', true) . '.xml';
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        if (is_file($this->filePath)) {
            unlink($this->filePath);
        }

        parent::tearDown();
    }

    /**
     * The partner identity of an invoice for a customer whose synchronization is as given.
     *
     * @param bool $syncToAccounting Whether the customer's card is ours to write.
     * @return \SimpleXMLElement
     */
    private function partnerIdentity(bool $syncToAccounting = true): SimpleXMLElement
    {
        $draft = new InvoiceDraft();
        $draft->number = '2026090001';
        $draft->variableSymbol = '117512';
        $draft->customerNumber = '117512';
        $draft->creationDate = new Date('2026-09-30');
        $draft->dueDate = new Date('2026-10-14');
        $draft->text = 'Internet access 9/2026';
        $draft->total = Decimal::create('121.00', 2);
        $draft->debt = Decimal::create('121.00', 2);
        $draft->customer = new Customer([
            'nid' => 512,
            'identity_number' => '87654321',
            'sync_to_accounting' => $syncToAccounting,
            'addresses' => [
                new Address([
                    'type' => AddressType::Billing,
                    'company' => 'Example Networks s.r.o.',
                    'city' => 'Springfield',
                ]),
            ],
        ]);

        (new XmlExporter())->export(
            [$draft],
            new Date('2026-09-01'),
            new AccountingProfile(['vat_rate' => 21.0, 'reverse_charge' => false]),
            $this->filePath,
        );

        $xml = simplexml_load_file($this->filePath);
        $this->assertInstanceOf(SimpleXMLElement::class, $xml);

        $xml->registerXPathNamespace('inv', 'http://www.stormware.cz/schema/version_2/invoice.xsd');
        $identity = $xml->xpath('//inv:invoiceHeader/inv:partnerIdentity') ?: [];
        $this->assertCount(1, $identity);

        $identity[0]->registerXPathNamespace('typ', 'http://www.stormware.cz/schema/version_2/type.xsd');

        return $identity[0];
    }

    /**
     * Until the link is turned on, the invoice carries the address written out, as it always has.
     *
     * @return void
     * @link \Bookkeeping\Provider\Pohoda\XmlExporter::export()
     */
    public function testWithoutTheLinkTheInvoiceCarriesTheAddress(): void
    {
        Settings::set(PohodaProvider::SETTINGS_ROOT . '.customers.use_buyer_code', false);

        $identity = $this->partnerIdentity();

        $this->assertSame([], $identity->xpath('./typ:extId'));
        $this->assertSame('Example Networks s.r.o.', (string)($identity->xpath('./typ:address/typ:company') ?: [''])[0]);
    }

    /**
     * With the link on, the invoice names the customer's card by its external ID alone, and the
     * address comes from the card - as in Stormware's own sample of the link.
     *
     * @return void
     * @link \Bookkeeping\Provider\Pohoda\XmlExporter::export()
     */
    public function testWithTheLinkTheInvoiceNamesTheCard(): void
    {
        Settings::set(PohodaProvider::SETTINGS_ROOT . '.customers.use_buyer_code', true);

        $identity = $this->partnerIdentity();

        $this->assertSame('117512', (string)($identity->xpath('./typ:extId/typ:ids') ?: [''])[0]);
        $this->assertSame(
            PohodaProvider::EXTERNAL_SYSTEM,
            (string)($identity->xpath('./typ:extId/typ:exSystemName') ?: [''])[0],
        );
        $this->assertSame([], $identity->xpath('./typ:address'));
    }

    /**
     * A customer whose card is not ours to write may not carry our external ID, so their invoice
     * keeps the address even with the link on - pointing at a card that is not there would keep
     * the invoice out of the accounting system.
     *
     * @return void
     * @link \Bookkeeping\Provider\Pohoda\XmlExporter::export()
     */
    public function testACustomerNotSynchronizedKeepsTheAddress(): void
    {
        Settings::set(PohodaProvider::SETTINGS_ROOT . '.customers.use_buyer_code', true);

        $identity = $this->partnerIdentity(syncToAccounting: false);

        $this->assertSame([], $identity->xpath('./typ:extId'));
        $this->assertSame('Springfield', (string)($identity->xpath('./typ:address/typ:city') ?: [''])[0]);
    }
}
