<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\TasksController;
use App\Test\Traits\ControllerTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\TasksController Test Case
 */
#[UsesClass(TasksController::class)]
class TasksControllerTest extends TestCase
{
    use ControllerTestTrait;
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
     * The listing renders with the search filled in, which builds a different query than the plain
     * listing does and is therefore worth requesting on its own.
     *
     * @return void
     * @link \App\Controller\TasksController::index()
     */
    public function testIndexWithSearch(): void
    {
        $this->login();
        $this->get('/tasks?show_completed=1&search=Lorem');

        $this->assertResponseOk();
    }

    /**
     * The detail of a task renders.
     *
     * @return void
     * @link \App\Controller\TasksController::view()
     */
    public function testView(): void
    {
        $this->login();
        $this->get('/tasks/view/' . $this->firstId('Tasks'));

        $this->assertResponseOk();
    }

    /**
     * The form for a new task renders.
     *
     * @return void
     * @link \App\Controller\TasksController::add()
     */
    public function testAdd(): void
    {
        $this->login();
        $this->get('/tasks/add');

        $this->assertResponseOk();
    }

    /**
     * The form of an existing task renders.
     *
     * @return void
     * @link \App\Controller\TasksController::edit()
     */
    public function testEdit(): void
    {
        $this->login();
        $this->get('/tasks/edit/' . $this->firstId('Tasks'));

        $this->assertResponseOk();
    }

    /**
     * The delete action runs and redirects. Whether the task really goes depends on what else still
     * references it, which is the application rules' business rather than this test's.
     *
     * @return void
     * @link \App\Controller\TasksController::delete()
     */
    public function testDelete(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/tasks/delete/' . $this->firstId('Tasks'));

        $this->assertRedirect();
    }
}
