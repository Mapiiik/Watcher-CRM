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
                'company' => 'NETAIR, s.r.o.',
                'address_key' => 'cz|16903153',
            ],
        ];
        $this->withConfigure(['BusinessRegister.sources' => ['stub' => StubSource::class]]);
        $this->givenCustomerIdentityNumber('27496139');

        $this->login();
        $this->get('/customers/' . self::CUSTOMER_ID . '/addresses/add');

        $this->assertResponseOk();
        $this->assertResponseContains('name="registered_seat"');
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
        $this->assertResponseNotContains('name="registered_seat"');
    }

    /**
     * The button posts a field of its own, and `Form->button()` locks none - so the form has to
     * say the field is unlocked, or the security check blackholes the very submit that was meant
     * to fill the seat in.
     *
     * The rendered token is what a browser would send back, so that is what is read here rather
     * than posting a token the test built for itself - one of those matches whatever it is given
     * and would pass either way.
     *
     * @return void
     * @link \App\Controller\AddressesController::add()
     * @link \App\Controller\AddressesController::edit()
     */
    public function testTheSeatButtonIsUnlockedForTheFormSecurityCheck(): void
    {
        StubSource::$entries = [
            [
                'reference' => '27496139',
                'company' => 'NETAIR, s.r.o.',
                'address_key' => 'cz|16903153',
            ],
        ];
        $this->withConfigure(['BusinessRegister.sources' => ['stub' => StubSource::class]]);
        $this->givenCustomerIdentityNumber('27496139');

        $this->login();

        $urls = [
            '/customers/' . self::CUSTOMER_ID . '/addresses/add',
            '/addresses/edit/' . $this->firstId('Addresses'),
        ];

        foreach ($urls as $url) {
            $this->get($url);

            $this->assertResponseOk();
            $this->assertResponseContains('name="registered_seat"');
            $this->assertMatchesRegularExpression(
                '/name="_Token\[unlocked\]"[^>]*value="[^"]*registered_seat[^"]*"/',
                (string)$this->_response?->getBody(),
                'The seat button posts a field the form security check would refuse: ' . $url,
            );
        }
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
