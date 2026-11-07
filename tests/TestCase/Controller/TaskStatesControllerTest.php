<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\TaskStatesController;
use App\Model\Table\TasksTable;
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
        'app.Users',
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
     * A state gathers tasks for years, so its card lists only so many and says so. The listing it
     * leads to holds all of them, finished ones and those of other people included, whatever
     * filter it was left with.
     *
     * @return void
     * @link \App\Controller\TaskStatesController::viewContain()
     */
    public function testViewListsOnlySoManyTasksAndLeadsToAllOfThem(): void
    {
        $tasks = $this->getTableLocator()->get('Tasks');
        /** @var \App\Model\Entity\Task $task */
        $task = $tasks->find()->firstOrFail();
        $tasks->getConnection()->execute(
            'INSERT INTO tasks (task_type_id, task_state_id, subject, text, priority)
                SELECT task_type_id, task_state_id, subject, text, priority
                FROM tasks, generate_series(1, ?)',
            [TasksTable::SHOWN_ON_A_CARD],
        );
        $tasks->updateAll(['user_id' => null], ['1 = 1']);

        $users = $this->getTableLocator()->get('Users');
        $users->updateAll(['role' => 'admin'], ['1 = 1']);
        $this->session(['Auth' => $users->find()->firstOrFail()]);

        $this->get('/task-states/view/' . $task->task_state_id);
        $this->assertResponseOk();

        /** @var \App\Model\Entity\TaskState $state */
        $state = $this->viewVariable('taskState');
        $this->assertCount(TasksTable::SHOWN_ON_A_CARD, $state->tasks);
        $this->assertResponseContains('Only the first ' . TasksTable::SHOWN_ON_A_CARD . ' are shown.');

        preg_match('/href="([^"]*task_state_ids[^"]*)"/', $this->_getBodyAsString(), $link);
        $listing = html_entity_decode($link[1] ?? '');
        $this->assertNotSame('', $listing);

        $this->session(['Config.Tasks.filter' => [
            'search' => 'Nothing like this',
            'pressing' => 1,
            'stale' => 1,
            'task_type_ids' => ['6ca9ad0a-7fd2-4b4a-8c0e-1d0f5f4d7e11'],
            'access_point_id' => 'c2a1f4b8-3e6d-4f7a-9b21-5d8e0c6a7f33',
        ]]);
        $this->get($listing);
        $this->assertResponseOk();

        /** @var \Cake\Datasource\Paging\PaginatedInterface<int, \App\Model\Entity\Task> $listed */
        $listed = $this->viewVariable('tasks');
        $this->assertSame(TasksTable::SHOWN_ON_A_CARD + 1, $listed->totalCount());
    }

    /**
     * A state with fewer tasks than a card holds does not say they were cut.
     *
     * @return void
     * @link \App\Controller\TaskStatesController::viewContain()
     */
    public function testViewOfAFewTasksDoesNotSayTheyWereCut(): void
    {
        /** @var \App\Model\Entity\Task $task */
        $task = $this->getTableLocator()->get('Tasks')->find()->firstOrFail();

        $this->login();
        $this->get('/task-states/view/' . $task->task_state_id);

        $this->assertResponseOk();
        $this->assertResponseNotContains('Only the first');
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
