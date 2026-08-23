<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\AddressesController;
use App\Test\TestCase\BusinessRegister\Source\StubSource;
use App\Test\Traits\ConfigureTestTrait;
use App\Test\Traits\ControllerTestTrait;
use Cake\Cache\Cache;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\AddressesController Test Case
 */
#[UsesClass(AddressesController::class)]
class AddressesControllerTest extends TestCase
{
    use ConfigureTestTrait;
    use ControllerTestTrait;
    use IntegrationTestTrait;

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        StubSource::reset();
        Cache::clear('business_register');
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    public function tearDown(): void
    {
        $this->restoreConfigure();
        Cache::clear('business_register');
        StubSource::reset();

        parent::tearDown();
    }

    /**
     * Customer the nested routes hang off.
     *
     * @var string
     */
    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.AppUsers',
        'app.AccountingProfiles',
        'app.Customers',
        'app.Countries',
        'app.Addresses',
        'app.ServiceTypes',
        'app.Commissions',
        'app.ContractStates',
        'app.Contracts',
    ];

    /**
     * Test index method
     *
     * @return void
     * @link \App\Controller\AddressesController::index()
     */
    public function testIndex(): void
    {
        $this->login();
        $this->get('/addresses');

        $this->assertResponseOk();
    }

    /**
     * Test index method with the search filled in
     *
     * @return void
     * @link \App\Controller\AddressesController::index()
     */
    public function testIndexWithSearch(): void
    {
        $this->login();
        $this->get('/addresses?search=Lorem');

        $this->assertResponseOk();
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\AddressesController::view()
     */
    public function testView(): void
    {
        $this->login();
        $this->get('/addresses/view/' . $this->firstId('Addresses'));

        $this->assertResponseOk();
    }

    /**
     * Test add method
     *
     * @return void
     * @link \App\Controller\AddressesController::add()
     */
    public function testAdd(): void
    {
        $this->login();
        $this->get('/addresses/add');

        $this->assertResponseOk();
    }

    /**
     * Under a customer whose seat a business register knows, the form offers to fill it in.
     *
     * The seat is rarely where the service is installed, so it is offered rather than assumed -
     * which is what this asks about, not what filling it in then produces. That last step goes to
     * the national address registry over the network and is left alone here for the same reason
     * the address search is.
     *
     * @return void
     * @link \App\Controller\AddressesController::add()
     */
    public function testAddOffersTheRegisteredSeatOfACustomerARegisterKnows(): void
    {
        StubSource::$entries = [
            [
                'reference' => '27496139',
                'name' => 'NETAIR, s.r.o.',
                'addresses' => [
                    ['key' => 'cz|16903153', 'label' => 'č.p. 299, 51243 Jablonec nad Jizerou', 'seat' => true],
                    ['key' => 'cz|16903382', 'label' => 'č.p. 322, 51243 Jablonec nad Jizerou', 'seat' => false],
                ],
            ],
        ];
        $this->withConfigure(['BusinessRegister.sources' => ['stub' => StubSource::class]]);
        $this->givenCustomerIdentityNumber('27496139');

        $this->login();
        $this->get('/customers/' . self::CUSTOMER_ID . '/addresses/add');

        $this->assertResponseOk();
        $this->assertResponseContains('name="business_register_address"');
        $this->assertResponseContains('č.p. 299, 51243 Jablonec nad Jizerou');
        $this->assertResponseContains('č.p. 322, 51243 Jablonec nad Jizerou');
    }

    /**
     * With no register able to name a seat, nothing is offered - an empty button would only
     * promise something it could not deliver.
     *
     * @return void
     * @link \App\Controller\AddressesController::add()
     */
    public function testAddOffersNoSeatWhenNoRegisterNamesOne(): void
    {
        $this->withConfigure(['BusinessRegister.sources' => ['stub' => StubSource::class]]);
        $this->givenCustomerIdentityNumber('27496139');

        $this->login();
        $this->get('/customers/' . self::CUSTOMER_ID . '/addresses/add');

        $this->assertResponseOk();
        $this->assertResponseNotContains('name="business_register_address"');
    }

    /**
     * The form offers the same addresses when an existing one is being changed, so a record put
     * under the wrong one of them can be moved without typing it out.
     *
     * @return void
     * @link \App\Controller\AddressesController::edit()
     */
    public function testEditOffersTheSameAddresses(): void
    {
        StubSource::$entries = [
            [
                'reference' => '27496139',
                'name' => 'NETAIR, s.r.o.',
                'addresses' => [
                    ['key' => 'cz|16903153', 'label' => 'č.p. 299, 51243 Jablonec nad Jizerou', 'seat' => true],
                    ['key' => 'cz|16903382', 'label' => 'č.p. 322, 51243 Jablonec nad Jizerou', 'seat' => false],
                ],
            ],
        ];
        $this->withConfigure(['BusinessRegister.sources' => ['stub' => StubSource::class]]);
        $this->givenCustomerIdentityNumber('27496139');

        $this->login();
        $this->get('/addresses/edit/' . $this->firstId('Addresses'));

        $this->assertResponseOk();
        $this->assertResponseContains('name="business_register_address"');
    }

    /**
     * An address key the register never offered is not one to look up - it is refused rather than
     * fetched, so nothing arrives from a key somebody made up.
     *
     * @return void
     * @link \App\Controller\AddressesController::add()
     */
    public function testAddRefusesAnAddressThatWasNeverOffered(): void
    {
        $this->withConfigure(['BusinessRegister.sources' => ['stub' => StubSource::class]]);
        $this->givenCustomerIdentityNumber('27496139');

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/customers/' . self::CUSTOMER_ID . '/addresses/add', [
            'type' => 0,
            'number_type' => 0,
            'country_id' => $this->firstId('Countries'),
            'street' => 'Typed by hand',
            'business_register_address' => 'cz|99999999',
        ]);

        // the made-up key changed nothing: the address saved as it was typed, rather than the
        // form going off to the registry with a key nobody offered
        $this->assertRedirect();
        $this->assertSame(
            1,
            $this->getTableLocator()->get('Addresses')->find()->where(['street' => 'Typed by hand'])->count(),
        );
    }

    /**
     * Gives the fixture customer an identification number to be looked up by.
     *
     * @param string $identityNumber The number to give them.
     * @return void
     */
    private function givenCustomerIdentityNumber(string $identityNumber): void
    {
        $customers = $this->getTableLocator()->get('Customers');
        $customer = $customers->get(self::CUSTOMER_ID);
        $customer->identity_number = $identityNumber;
        $customers->saveOrFail($customer);
    }

    /**
     * Test edit method
     *
     * @return void
     * @link \App\Controller\AddressesController::edit()
     */
    public function testEdit(): void
    {
        $this->login();
        $this->get('/addresses/edit/' . $this->firstId('Addresses'));

        $this->assertResponseOk();
    }

    /**
     * The map for picking coordinates by hand stands outside the form it fills in.
     *
     * Leaflet writes the base layer switcher into the map as a radio group it names after a
     * counter of its own, so a form enclosing the map submits fields the form protection never
     * signed - and that refuses the whole request, not just the field.
     *
     * @return void
     * @link \App\Controller\AddressesController::edit()
     */
    public function testEditKeepsThePointPickerOutsideTheForm(): void
    {
        $addresses = $this->getTableLocator()->get('Addresses');
        $address = $addresses->get($this->firstId('Addresses'));
        $address->set('manual_coordinate_setting', true);
        $addresses->saveOrFail($address);

        $this->login();
        $this->get('/addresses/edit/' . $address->get('id'));

        $this->assertResponseOk();

        $body = $this->_getBodyAsString();
        $picker = strpos($body, 'data-maps-point-picker');
        $this->assertNotFalse($picker, 'Coordinates set by hand are what the map is drawn for.');

        // whatever happened last before the map was a form closing, not a form opening
        $before = substr($body, 0, $picker);
        $this->assertGreaterThan(strrpos($before, '<form'), strrpos($before, '</form>'));
    }

    /**
     * The delete action runs and redirects. Whether the record really goes depends on what else
     * still references it, which is the application rules' business rather than this test's.
     *
     * @return void
     * @link \App\Controller\AddressesController::delete()
     */
    public function testDelete(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/addresses/delete/' . $this->firstId('Addresses'));

        $this->assertRedirect();
    }

    /**
     * Added under its customer, the record is filed under them without the form saying so.
     *
     * The form under a customer leaves those fields out - the route already says which record it is,
     * and the controller fills them in. Posting them in the body instead, as a test reaching the
     * flat route does, asks a different question and leaves this one unasked.
     *
     * @return void
     * @link \App\Controller\AddressesController::add()
     */
    public function testAddUnderTheRouteFilesItUnderTheRoute(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $before = $this->idsIn('Addresses');
        $this->post('/customers/' . self::CUSTOMER_ID . '/addresses/add', [
            'type' => 0,
            'number_type' => 0,
            'country_id' => $this->firstId('Countries'),
            'street' => 'Nested street',
        ]);

        $this->assertRedirect();
        $added = $this->addedRecord('Addresses', $before);
        $this->assertSame(self::CUSTOMER_ID, $added->get('customer_id'));
    }

    /**
     * A change made on the form reaches the record.
     *
     * The whole form goes back, not only the changed field: who the address belongs to is asked for
     * on every save rather than only on the first one, and the name and the company answer for each
     * other, so a request carrying one field alone is refused.
     *
     * @return void
     * @link \App\Controller\AddressesController::edit()
     */
    public function testEditStoresTheChange(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $addressId = $this->firstId('Addresses');
        $this->post('/addresses/edit/' . $addressId, [
            'type' => 0,
            'number_type' => 0,
            'country_id' => $this->firstId('Countries'),
            'company' => 'NETAIR, s.r.o.',
            'street' => 'Renamed street',
        ]);

        $this->assertRedirect();
        $this->assertSame(
            'Renamed street',
            $this->getTableLocator()->get('Addresses')->get($addressId)->street,
        );
    }
}
