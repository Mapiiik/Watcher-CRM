<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\RemovedIpNetworksController;
use App\Test\Traits\ControllerTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\RemovedIpNetworksController Test Case
 *
 * Smoke tests: every action is requested once and has to answer. They are deliberately shallow -
 * their job is to notice an action that stopped answering at all, which is how the query building
 * bugs in this application have shown up.
 */
#[UsesClass(RemovedIpNetworksController::class)]
class RemovedIpNetworksControllerTest extends TestCase
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
        'app.RemovedIpNetworks',
    ];

    /**
     * The listing renders.
     *
     * @return void
     * @link \App\Controller\RemovedIpNetworksController::index()
     */
    public function testIndex(): void
    {
        $this->login();
        $this->get('/removed-ip-networks');

        $this->assertResponseOk();
    }

    /**
     * The listing renders with the search filled in, which builds a different query than the plain
     * listing does and is therefore worth requesting on its own.
     *
     * @return void
     * @link \App\Controller\RemovedIpNetworksController::index()
     */
    public function testIndexWithSearch(): void
    {
        $this->login();
        $this->get('/removed-ip-networks?search=Lorem');

        $this->assertResponseOk();
    }

    /**
     * The detail of a record renders.
     *
     * @return void
     * @link \App\Controller\RemovedIpNetworksController::view()
     */
    public function testView(): void
    {
        $this->login();
        $this->get('/removed-ip-networks/view/' . $this->firstId('RemovedIpNetworks'));

        $this->assertResponseOk();
    }

    /**
     * The form for a new record renders.
     *
     * @return void
     * @link \App\Controller\RemovedIpNetworksController::add()
     */
    public function testAdd(): void
    {
        $this->login();
        $this->get('/removed-ip-networks/add');

        $this->assertResponseOk();
    }

    /**
     * The form of an existing record renders.
     *
     * @return void
     * @link \App\Controller\RemovedIpNetworksController::edit()
     */
    public function testEdit(): void
    {
        $this->login();
        $this->get('/removed-ip-networks/edit/' . $this->firstId('RemovedIpNetworks'));

        $this->assertResponseOk();
    }

    /**
     * The delete action runs and redirects. Whether the record really goes depends on what else
     * still references it, which is the application rules' business rather than this test's.
     *
     * @return void
     * @link \App\Controller\RemovedIpNetworksController::delete()
     */
    public function testDelete(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/removed-ip-networks/delete/' . $this->firstId('RemovedIpNetworks'));

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
     * @link \App\Controller\RemovedIpNetworksController::add()
     */
    public function testAddUnderTheRouteFilesItUnderTheRoute(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $before = $this->idsIn('RemovedIpNetworks');
        $this->post('/customers/' . self::CUSTOMER_ID . '/contracts/' . self::CONTRACT_ID . '/removed-ip-networks/add', [
            'ip_network' => '10.99.3.0/24',
            'type_of_use' => 0,
            'removed' => '2026-08-05',
        ]);

        $this->assertRedirect();
        $added = $this->addedRecord('RemovedIpNetworks', $before);
        $this->assertSame(self::CUSTOMER_ID, $added->get('customer_id'));
        $this->assertSame(self::CONTRACT_ID, $added->get('contract_id'));
    }

    /**
     * A change made on the form reaches the record.
     *
     * @return void
     * @link \App\Controller\RemovedIpNetworksController::edit()
     */
    public function testEditStoresTheChange(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $removedIpNetworkId = $this->firstId('RemovedIpNetworks');
        $this->post('/removed-ip-networks/edit/' . $removedIpNetworkId, ['note' => 'Freed on request.']);

        $this->assertRedirect();
        $this->assertSame(
            'Freed on request.',
            $this->getTableLocator()->get('RemovedIpNetworks')->get($removedIpNetworkId)->note,
        );
    }
}
