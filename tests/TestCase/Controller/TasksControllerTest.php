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
     * A task filled in on the form is really stored.
     *
     * Rendering the form proves the page is there; this proves the way through it works. The rules
     * of this table ask the task type what it requires, and those rules only ever run on a request
     * that carries data.
     *
     * @return void
     * @link \App\Controller\TasksController::add()
     */
    public function testAddStoresATask(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        // the fixtures write the identity column with the values they carry, which leaves the
        // identity itself where it started
        $this->advanceIdentity('tasks', 'nid');

        /** @var \App\Model\Entity\Task $existing */
        $existing = $this->getTableLocator()->get('Tasks')->get($this->firstId('Tasks'));

        $this->post('/tasks/add', [
            'subject' => 'Fault call-out',
            'task_type_id' => $existing->task_type_id,
            'task_state_id' => $existing->task_state_id,
            // the task type in the fixtures requires both of these, which is what the rules
            // asking it are for
            'customer_id' => $existing->customer_id,
            'contract_id' => $this->firstId('Contracts'),
        ]);

        $this->assertRedirect();
        /** @var \App\Model\Entity\Task $stored */
        $stored = $this->getTableLocator()->get('Tasks')
            ->find()
            ->where(['subject' => 'Fault call-out'])
            ->firstOrFail();
        $this->assertSame($existing->task_type_id, $stored->task_type_id);
    }

    /**
     * A task type that requires a customer refuses a task without one, and the operator is given
     * the form back rather than a redirect suggesting it went through.
     *
     * @return void
     * @link \App\Controller\TasksController::add()
     */
    public function testAddRefusesATaskMissingWhatTheTypeRequires(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->advanceIdentity('tasks', 'nid');

        $tasks = $this->getTableLocator()->get('Tasks');
        /** @var \App\Model\Entity\Task $existing */
        $existing = $tasks->get($this->firstId('Tasks'));
        $before = $tasks->find()->count();

        $this->post('/tasks/add', [
            'subject' => 'Signal check',
            'task_type_id' => $existing->task_type_id,
            'task_state_id' => $existing->task_state_id,
        ]);

        $this->assertResponseOk();
        $this->assertSame($before, $tasks->find()->count());
    }

    /**
     * A change made on the form reaches the record.
     *
     * The contract goes with it because the fixture leaves the task without one while its type
     * requires one - the record as the fixture writes it cannot be saved again at all, whatever is
     * being changed about it.
     *
     * @return void
     * @link \App\Controller\TasksController::edit()
     */
    public function testEditStoresTheChange(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $taskId = $this->firstId('Tasks');
        $this->post('/tasks/edit/' . $taskId, [
            'subject' => 'Antenna replacement',
            'contract_id' => $this->firstId('Contracts'),
        ]);

        $this->assertRedirect();
        $this->assertSame(
            'Antenna replacement',
            $this->getTableLocator()->get('Tasks')->get($taskId)->subject,
        );
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
