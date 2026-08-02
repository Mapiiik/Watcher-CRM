<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\OverviewsController;
use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\OverviewsController Test Case
 */
#[UsesClass(OverviewsController::class)]
class OverviewsControllerTest extends TestCase
{
    use IntegrationTestTrait;

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
        'app.Emails',
        'app.Phones',
        'app.Commissions',
        'app.ContractStates',
        'app.ServiceTypes',
        'app.Contracts',
        'app.Labels',
        'app.CustomerLabels',
        'app.Queues',
        'app.Services',
        'app.Billings',
    ];

    /**
     * login method
     *
     * @return void
     */
    protected function login(): void
    {
        /** @var \App\Model\Table\AppUsersTable $usersTable */
        $usersTable = $this->getTableLocator()->get(Configure::read('Users.table', 'Users'));

        $user = $usersTable->newEmptyEntity();
        $user->username = 'tester';
        $user->role = 'admin';
        $user->active = true;

        $this->session(['Auth' => $user]);
    }

    /**
     * Test index method
     *
     * @return void
     * @link \App\Controller\OverviewsController::index()
     */
    public function testIndex(): void
    {
        $this->login();

        $this->get('/overviews');

        $this->assertResponseOk();
        // the whole table of historical connections is reachable from here, there is
        // nowhere else to get at it outside a single customer or contract
        $this->assertResponseContains('/historical-connections');
    }

    /**
     * Test overview of contracts method
     *
     * The listing is sorted by columns of the customers while it eager loads the billings of the
     * contracts - the combination the `subquery` strategy of CakePHP 5.4 turns into an `ORDER BY`
     * over a column that is neither grouped nor aggregated, hence the `select` strategy there.
     *
     * @return void
     * @link \App\Controller\OverviewsController::overviewOfContracts()
     */
    public function testOverviewOfContracts(): void
    {
        $this->login();

        $this->get('/overviews/overview-of-contracts');

        $this->assertResponseOk();

        /** @var iterable<\App\Model\Entity\Contract> $contracts */
        $contracts = $this->viewVariable('contracts');
        $contracts = iterator_to_array($contracts, false);

        $this->assertNotEmpty($contracts);
        // the eager loaded branches the listing renders
        $this->assertNotEmpty($contracts[0]->billings);
        $this->assertNotNull($contracts[0]->billings[0]->service);
        $this->assertNotNull($contracts[0]->billings[0]->service->queue);
        $this->assertNotNull($contracts[0]->customer->emails);
        $this->assertNotNull($contracts[0]->customer->phones);
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\OverviewsController::view()
     */
    public function testView(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test add method
     *
     * @return void
     * @link \App\Controller\OverviewsController::add()
     */
    public function testAdd(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test edit method
     *
     * @return void
     * @link \App\Controller\OverviewsController::edit()
     */
    public function testEdit(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test delete method
     *
     * @return void
     * @link \App\Controller\OverviewsController::delete()
     */
    public function testDelete(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
