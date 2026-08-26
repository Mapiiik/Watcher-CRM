<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\TaskStatesController;
use App\Test\Traits\ControllerTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\TaskStatesController Test Case
 *
 * Smoke tests: every action is requested once and has to answer. They are deliberately shallow -
 * their job is to notice an action that stopped answering at all, which is how the query building
 * bugs in this application have shown up.
 */
#[UsesClass(TaskStatesController::class)]
class TaskStatesControllerTest extends TestCase
{
    use ControllerTestTrait;
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
        'app.Commissions',
        'app.ContractStates',
        'app.ServiceTypes',
        'app.Contracts',
        'app.TaskStates',
        'app.TaskTypes',
        'app.Tasks',
        'app.TaskCollaborators',
    ];

    /**
     * The listing renders.
     *
     * @return void
     * @link \App\Controller\TaskStatesController::index()
     */
    public function testIndex(): void
    {
        $this->login();
        $this->get('/task-states');

        $this->assertResponseOk();
    }

    /**
     * The listing renders with the search filled in, which builds a different query than the plain
     * listing does and is therefore worth requesting on its own.
     *
     * @return void
     * @link \App\Controller\TaskStatesController::index()
     */
    public function testIndexWithSearch(): void
    {
        $this->login();
        $this->get('/task-states?search=Lorem');

        $this->assertResponseOk();
    }

    /**
     * The detail of a record renders.
     *
     * @return void
     * @link \App\Controller\TaskStatesController::view()
     */
    public function testView(): void
    {
        $this->login();
        $this->get('/task-states/view/' . $this->firstId('TaskStates'));

        $this->assertResponseOk();
    }

    /**
     * The tasks in the state are listed on its card - the same table the task type card carries,
     * each leaving out the column that would say the same thing on every row.
     *
     * @return void
     * @link \App\Controller\TaskStatesController::view()
     */
    public function testViewListsTheTasksInTheState(): void
    {
        /** @var \App\Model\Entity\Task $task */
        $task = $this->getTableLocator()->get('Tasks')->find()->firstOrFail();

        $this->login();
        $this->get('/task-states/view/' . $task->task_state_id);

        $this->assertResponseOk();
        // by identifier rather than by number: a task is numbered from one, and a page carries
        // plenty of small numbers that say nothing about tasks
        $this->assertResponseContains($task->id);
    }

    /**
     * The form for a new record renders.
     *
     * @return void
     * @link \App\Controller\TaskStatesController::add()
     */
    public function testAdd(): void
    {
        $this->login();
        $this->get('/task-states/add');

        $this->assertResponseOk();
    }

    /**
     * The form of an existing record renders.
     *
     * @return void
     * @link \App\Controller\TaskStatesController::edit()
     */
    public function testEdit(): void
    {
        $this->login();
        $this->get('/task-states/edit/' . $this->firstId('TaskStates'));

        $this->assertResponseOk();
    }

    /**
     * The delete action runs and redirects. Whether the record really goes depends on what else
     * still references it, which is the application rules' business rather than this test's.
     *
     * @return void
     * @link \App\Controller\TaskStatesController::delete()
     */
    public function testDelete(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/task-states/delete/' . $this->firstId('TaskStates'));

        $this->assertRedirect();
    }

    /**
     * A state filled in on the form is really stored. Rendering the form proves the page is there;
     * marshalling, validation, the application rules and the save only ever run on a request that
     * carries data.
     *
     * @return void
     * @link \App\Controller\TaskStatesController::add()
     */
    public function testAddStoresAState(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/task-states/add', [
            'name' => 'Waiting for the customer',
            'color' => '#336699',
            'priority' => '30',
            'completed' => '0',
        ]);

        $this->assertRedirect();
        /** @var \App\Model\Entity\TaskState $stored */
        $stored = $this->getTableLocator()->get('TaskStates')
            ->find()
            ->where(['name' => 'Waiting for the customer'])
            ->firstOrFail();
        $this->assertSame(30, $stored->priority);
    }

    /**
     * A state without a priority is not stored, and the operator is given the form back rather than
     * a redirect that would suggest it went through.
     *
     * @return void
     * @link \App\Controller\TaskStatesController::add()
     */
    public function testAddRefusesAStateWithoutAPriority(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $taskStates = $this->getTableLocator()->get('TaskStates');
        $before = $taskStates->find()->count();

        $this->post('/task-states/add', [
            'name' => 'Waiting for the customer',
            'priority' => '',
        ]);

        $this->assertResponseOk();
        $this->assertSame($before, $taskStates->find()->count());
    }

    /**
     * A change made on the form reaches the record.
     *
     * @return void
     * @link \App\Controller\TaskStatesController::edit()
     */
    public function testEditStoresTheChange(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $taskStateId = $this->firstId('TaskStates');
        $this->post('/task-states/edit/' . $taskStateId, ['name' => 'Renamed state']);

        $this->assertRedirect();
        $this->assertSame(
            'Renamed state',
            $this->getTableLocator()->get('TaskStates')->get($taskStateId)->name,
        );
    }
}
