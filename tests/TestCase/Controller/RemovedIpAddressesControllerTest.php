<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\RemovedIpAddressesController;
use App\Test\Traits\ControllerTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\RemovedIpAddressesController Test Case
 *
 * Smoke tests: every action is requested once and has to answer. They are deliberately shallow -
 * their job is to notice an action that stopped answering at all, which is how the query building
 * bugs in this application have shown up.
 */
#[UsesClass(RemovedIpAddressesController::class)]
class RemovedIpAddressesControllerTest extends TestCase
{
    use ControllerTestTrait;
    use IntegrationTestTrait;

    /**
     * Customer the nested routes hang off.
     *
     * @var string
     */
    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    /**
     * Contract the nested routes hang off.
     *
     * @var string
     */
    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

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
        'app.Commissions',
        'app.ContractStates',
        'app.ServiceTypes',
        'app.Contracts',
        'app.RemovedIpAddresses',
    ];

    /**
     * The listing renders.
     *
     * @return void
     * @link \App\Controller\RemovedIpAddressesController::index()
     */
    public function testIndex(): void
    {
        $this->login();
        $this->get('/removed-ip-addresses');

        $this->assertResponseOk();
    }

    /**
     * The listing renders with the search filled in, which builds a different query than the plain
     * listing does and is therefore worth requesting on its own.
     *
     * @return void
     * @link \App\Controller\RemovedIpAddressesController::index()
     */
    public function testIndexWithSearch(): void
    {
        $this->login();
        $this->get('/removed-ip-addresses?search=Lorem');

        $this->assertResponseOk();
    }

    /**
     * The detail of a record renders.
     *
     * @return void
     * @link \App\Controller\RemovedIpAddressesController::view()
     */
    public function testView(): void
    {
        $this->login();
        $this->get('/removed-ip-addresses/view/' . $this->firstId('RemovedIpAddresses'));

        $this->assertResponseOk();
    }

    /**
     * The form for a new record renders.
     *
     * @return void
     * @link \App\Controller\RemovedIpAddressesController::add()
     */
    public function testAdd(): void
    {
        $this->login();
        $this->get('/removed-ip-addresses/add');

        $this->assertResponseOk();
    }

    /**
     * The form of an existing record renders.
     *
     * @return void
     * @link \App\Controller\RemovedIpAddressesController::edit()
     */
    public function testEdit(): void
    {
        $this->login();
        $this->get('/removed-ip-addresses/edit/' . $this->firstId('RemovedIpAddresses'));

        $this->assertResponseOk();
    }

    /**
     * The delete action runs and redirects. Whether the record really goes depends on what else
     * still references it, which is the application rules' business rather than this test's.
     *
     * @return void
     * @link \App\Controller\RemovedIpAddressesController::delete()
     */
    public function testDelete(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/removed-ip-addresses/delete/' . $this->firstId('RemovedIpAddresses'));

        $this->assertRedirect();
    }

    /**
     * Added under its customer and the contract, the record is filed under them without the form saying so.
     *
     * The form under a customer and the contract leaves those fields out - the route already says which record it is,
     * and the controller fills them in. Posting them in the body instead, as a test reaching the
     * flat route does, asks a different question and leaves this one unasked.
     *
     * @return void
     * @link \App\Controller\RemovedIpAddressesController::add()
     */
    public function testAddUnderTheRouteFilesItUnderTheRoute(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $before = $this->idsIn('RemovedIpAddresses');
        $this->post('/customers/' . self::CUSTOMER_ID . '/contracts/' . self::CONTRACT_ID . '/removed-ip-addresses/add', [
            'ip_address' => '10.99.2.1',
            'type_of_use' => 0,
            'removed' => '2026-08-05',
        ]);

        $this->assertRedirect();
        $added = $this->addedRecord('RemovedIpAddresses', $before);
        $this->assertSame(self::CUSTOMER_ID, $added->get('customer_id'));
        $this->assertSame(self::CONTRACT_ID, $added->get('contract_id'));
    }
}
