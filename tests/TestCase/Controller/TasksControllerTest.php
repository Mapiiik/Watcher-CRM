<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\TasksController;
use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\TasksController Test Case
 */
#[UsesClass(TasksController::class)]
class TasksControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Customer owning the task from the Tasks fixture.
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
        'app.Commissions',
        'app.ContractStates',
        'app.ServiceTypes',
        'app.Contracts',
        'app.TaskStates',
        'app.TaskTypes',
        'app.Tasks',
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
     * The listing is sorted by `TaskStates.priority`, a column of a joined table, while it eager
     * loads the addresses of the customers - the combination the `subquery` strategy of CakePHP 5.4
     * turns into an `ORDER BY` over a column that is neither grouped nor aggregated, see
     * \App\Model\Table\AppTable::hasMany().
     *
     * @return void
     * @link \App\Controller\TasksController::index()
     */
    public function testIndex(): void
    {
        $this->login();
        // the only fixture task is in a completed state
        $this->get('/tasks?show_completed=1');

        $this->assertResponseOk();

        /** @var iterable<\App\Model\Entity\Task> $tasks */
        $tasks = $this->viewVariable('tasks');
        $customers = [];
        foreach ($tasks as $task) {
            $customers[] = $task->customer;
        }

        $this->assertCount(1, $customers);
        $this->assertSame(self::CUSTOMER_ID, $customers[0]->id);
        $this->assertNotNull($customers[0]->installation_address);
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\TasksController::view()
     */
    public function testView(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test add method
     *
     * @return void
     * @link \App\Controller\TasksController::add()
     */
    public function testAdd(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test edit method
     *
     * @return void
     * @link \App\Controller\TasksController::edit()
     */
    public function testEdit(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test delete method
     *
     * @return void
     * @link \App\Controller\TasksController::delete()
     */
    public function testDelete(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
