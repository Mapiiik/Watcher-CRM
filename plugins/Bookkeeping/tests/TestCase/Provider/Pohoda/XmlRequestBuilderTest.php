<?php
declare(strict_types=1);

namespace Bookkeeping\Test\TestCase\Provider\Pohoda;

use App\Model\Entity\Address;
use App\Model\Entity\Country;
use App\Model\Entity\Customer;
use App\Model\Entity\Email;
use App\Model\Entity\Phone;
use App\Model\Enum\AddressType;
use Bookkeeping\Provider\Pohoda\PohodaProvider;
use Bookkeeping\Provider\Pohoda\XmlRequestBuilder;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Settings\Utility\Settings;
use SimpleXMLElement;

/**
 * Bookkeeping\Provider\Pohoda\XmlRequestBuilder Test Case
 *
 * What the address book import says decides which partner card a customer is written onto: the
 * external ID in its filter is what finds the card again, and a different one on the next run
 * would add every customer a second time. The builder turns customers into XML without reaching
 * mServer, so the tests read the XML it hands back.
 *
 * The customers are put together here rather than loaded from fixtures: which address and which
 * contact they carry is the point of the test that uses them.
 */
class XmlRequestBuilderTest extends TestCase
{
    /**
     * Fixtures
     *
     * Only the settings, which the accounting unit and the code prefix are pinned in.
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'plugin.Settings.Settings',
    ];

    /**
     * Test subject
     *
     * @var \Bookkeeping\Provider\Pohoda\XmlRequestBuilder
     */
    private XmlRequestBuilder $builder;

    /**
     * setUp method
     *
     * The customer number is the series plus the customer's own number, and the series comes
     * from the environment - which a developer's machine has and CI does not.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        Configure::write('Customers.series', 117000);
        Settings::set(PohodaProvider::SETTINGS_ROOT . '.api.accounting_unit', '12345678');
        Settings::set(PohodaProvider::SETTINGS_ROOT . '.customers.code_prefix', '');

        $this->builder = new XmlRequestBuilder();
    }

    /**
     * A customer with an address of the given type, one contact of each kind for billing and one
     * for everything else.
     *
     * @param \App\Model\Enum\AddressType $type The type of the customer's only address.
     * @return \App\Model\Entity\Customer
     */
    private function customer(AddressType $type = AddressType::Billing): Customer
    {
        return new Customer([
            'nid' => 512,
            'company' => 'Example Networks s.r.o.',
            'identity_number' => '87654321',
            'vat_number' => 'CZ87654321',
            'sync_to_accounting' => true,
            'addresses' => [
                new Address([
                    'type' => $type,
                    'company' => 'Example Networks s.r.o.',
                    'first_name' => 'Jane',
                    'last_name' => 'Doe',
                    'street' => 'Main Street',
                    'number' => '12',
                    'city' => 'Springfield',
                    'zip' => '12345',
                    'country' => new Country(['code' => 'CZ']),
                ]),
            ],
            'emails' => [
                new Email(['email' => 'office@example.com', 'use_for_billing' => false]),
                new Email(['email' => 'billing@example.com', 'use_for_billing' => true]),
            ],
            'phones' => [
                new Phone(['phone' => '+420 600 000 001', 'use_for_billing' => false]),
                new Phone(['phone' => '+420 600 000 002', 'use_for_billing' => true]),
            ],
        ]);
    }

    /**
     * The request for the given customers, with the namespaces its XPath reads by.
     *
     * @param list<\App\Model\Entity\Customer> $customers Customers to import.
     * @return \SimpleXMLElement
     */
    private function request(array $customers): SimpleXMLElement
    {
        $xml = new SimpleXMLElement($this->builder->buildPartnersRequest($customers));

        $xml->registerXPathNamespace('dat', 'http://www.stormware.cz/schema/version_2/data.xsd');
        $xml->registerXPathNamespace('adb', 'http://www.stormware.cz/schema/version_2/addressbook.xsd');
        $xml->registerXPathNamespace('ftr', 'http://www.stormware.cz/schema/version_2/filter.xsd');
        $xml->registerXPathNamespace('typ', 'http://www.stormware.cz/schema/version_2/type.xsd');

        return $xml;
    }

    /**
     * The single value at the path, or null where there is none.
     *
     * @param \SimpleXMLElement $xml Request.
     * @param string $path XPath.
     * @return string|null
     */
    private function value(SimpleXMLElement $xml, string $path): ?string
    {
        $nodes = $xml->xpath($path) ?: [];

        return isset($nodes[0]) ? (string)$nodes[0] : null;
    }

    /**
     * The request is for the accounting unit named in the settings - mServer answers for the
     * one it is asked about, and a different one would fill another company's address book.
     *
     * @return void
     * @link \Bookkeeping\Provider\Pohoda\XmlRequestBuilder::buildPartnersRequest()
     */
    public function testTheRequestIsForTheAccountingUnit(): void
    {
        $xml = $this->request([$this->customer()]);

        $this->assertSame('12345678', (string)$xml['ico']);
    }

    /**
     * The customer is added, or updated where their card is already there, found by the external
     * ID - which is what keeps a second run from adding them a second time.
     *
     * @return void
     * @link \Bookkeeping\Provider\Pohoda\XmlRequestBuilder::buildPartnersRequest()
     */
    public function testTheCustomerIsAddedOrUpdatedByTheirExternalId(): void
    {
        $xml = $this->request([$this->customer()]);
        $add = '//adb:addressbook/adb:actionType/adb:add';

        $this->assertSame('true', $this->value($xml, $add . '/@update'));
        $this->assertSame('117512', $this->value($xml, $add . '/ftr:filter/ftr:extId/typ:ids'));
        $this->assertSame(
            PohodaProvider::EXTERNAL_SYSTEM,
            $this->value($xml, $add . '/ftr:filter/ftr:extId/typ:exSystemName'),
        );
    }

    /**
     * The card is written with the same external ID it is looked up by, so the run after this one
     * finds it.
     *
     * @return void
     * @link \Bookkeeping\Provider\Pohoda\XmlRequestBuilder::buildPartnersRequest()
     */
    public function testTheCardCarriesTheExternalIdItIsFoundBy(): void
    {
        $xml = $this->request([$this->customer()]);
        $identity = '//adb:addressbookHeader/adb:identity';

        $this->assertSame('117512', $this->value($xml, $identity . '/typ:extId/typ:ids'));
        $this->assertSame(
            PohodaProvider::EXTERNAL_SYSTEM,
            $this->value($xml, $identity . '/typ:extId/typ:exSystemName'),
        );
    }

    /**
     * The code prefix goes in front of the customer number, in the filter and on the card alike.
     *
     * @return void
     * @link \Bookkeeping\Provider\Pohoda\PohodaProvider::partnerExtId()
     */
    public function testTheCodePrefixGoesInFrontOfTheCustomerNumber(): void
    {
        Settings::set(PohodaProvider::SETTINGS_ROOT . '.customers.code_prefix', 'CRM-');

        $xml = $this->request([$this->customer()]);

        $this->assertSame('CRM-117512', $this->value($xml, '//ftr:filter/ftr:extId/typ:ids'));
        $this->assertSame('CRM-117512', $this->value($xml, '//adb:identity/typ:extId/typ:ids'));
    }

    /**
     * The card carries the billing address and the customer's identification - it is what the
     * invoices linked to it take their partner from.
     *
     * @return void
     * @link \Bookkeeping\Provider\Pohoda\XmlRequestBuilder::buildPartnersRequest()
     */
    public function testTheCardCarriesTheBillingAddress(): void
    {
        $xml = $this->request([$this->customer()]);
        $address = '//adb:identity/typ:address';

        $this->assertSame('Example Networks s.r.o.', $this->value($xml, $address . '/typ:company'));
        $this->assertSame('Jane Doe', $this->value($xml, $address . '/typ:name'));
        $this->assertSame('Main Street 12', $this->value($xml, $address . '/typ:street'));
        $this->assertSame('Springfield', $this->value($xml, $address . '/typ:city'));
        $this->assertSame('12345', $this->value($xml, $address . '/typ:zip'));
        $this->assertSame('CZ', $this->value($xml, $address . '/typ:country/typ:ids'));
        $this->assertSame('87654321', $this->value($xml, $address . '/typ:ico'));
        $this->assertSame('CZ87654321', $this->value($xml, $address . '/typ:dic'));
    }

    /**
     * A customer without a billing address is written with the one billing falls back to, as
     * their invoices are.
     *
     * @return void
     * @link \Bookkeeping\Provider\Pohoda\XmlRequestBuilder::buildPartnersRequest()
     */
    public function testACustomerWithoutABillingAddressFallsBackAsTheirInvoicesDo(): void
    {
        $xml = $this->request([$this->customer(AddressType::Permanent)]);

        $this->assertSame('Springfield', $this->value($xml, '//adb:identity/typ:address/typ:city'));
    }

    /**
     * The contacts on the card are the ones meant for billing, where the customer has named any.
     *
     * @return void
     * @link \Bookkeeping\Provider\Pohoda\XmlRequestBuilder::buildPartnersRequest()
     */
    public function testTheCardCarriesTheBillingContacts(): void
    {
        $xml = $this->request([$this->customer()]);

        $this->assertSame('billing@example.com', $this->value($xml, '//adb:addressbookHeader/adb:email'));
        $this->assertSame('+420 600 000 002', $this->value($xml, '//adb:addressbookHeader/adb:phone'));
    }

    /**
     * A customer who has named no contact for billing still gets the one they have.
     *
     * @return void
     * @link \Bookkeeping\Provider\Pohoda\XmlRequestBuilder::buildPartnersRequest()
     */
    public function testWithoutBillingContactsTheOthersAreUsed(): void
    {
        $customer = $this->customer();
        $customer->emails = [new Email(['email' => 'office@example.com', 'use_for_billing' => false])];
        $customer->phones = [new Phone(['phone' => '+420 600 000 001', 'use_for_billing' => false])];

        $xml = $this->request([$customer]);

        $this->assertSame('office@example.com', $this->value($xml, '//adb:addressbookHeader/adb:email'));
        $this->assertSame('+420 600 000 001', $this->value($xml, '//adb:addressbookHeader/adb:phone'));
    }

    /**
     * Each customer is an item of their own, identified by the customer number - which is how
     * the response is read back customer by customer.
     *
     * @return void
     * @link \Bookkeeping\Provider\Pohoda\XmlRequestBuilder::buildPartnersRequest()
     */
    public function testEachCustomerIsAnItemIdentifiedByTheirNumber(): void
    {
        $other = $this->customer();
        $other->nid = 513;

        $xml = $this->request([$this->customer(), $other]);

        $ids = array_map(
            fn(SimpleXMLElement $item): string => (string)$item['id'],
            $xml->xpath('//dat:dataPackItem') ?: [],
        );

        $this->assertSame(['117512', '117513'], $ids);
    }
}
