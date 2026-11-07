<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\TaskTypesController;
use App\Model\Table\TasksTable;
use App\Test\Traits\ControllerTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\TaskTypesController Test Case
 *
 * Smoke tests: every action is requested once and has to answer. They are deliberately shallow -
 * their job is to notice an action that stopped answering at all, which is how the query building
 * bugs in this application have shown up.
 */
#[UsesClass(TaskTypesController::class)]
class TaskTypesControllerTest extends TestCase
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
     * @link \App\Controller\TaskTypesController::index()
     */
    public function testIndex(): void
    {
        $this->login();
        $this->get('/task-types');

        $this->assertResponseOk();
    }

    /**
     * The listing renders with the search filled in, which builds a different query than the plain
     * listing does and is therefore worth requesting on its own.
     *
     * @return void
     * @link \App\Controller\TaskTypesController::index()
     */
    public function testIndexWithSearch(): void
    {
        $this->login();
        $this->get('/task-types?search=Lorem');

        $this->assertResponseOk();
    }

    /**
     * The detail of a record renders.
     *
     * @return void
     * @link \App\Controller\TaskTypesController::view()
     */
    public function testView(): void
    {
        $this->login();
        $this->get('/task-types/view/' . $this->firstId('TaskTypes'));

        $this->assertResponseOk();
    }

    /**
     * The tasks of the type are listed on its card - the same table the task state card carries,
     * each leaving out the column that would say the same thing on every row.
     *
     * @return void
     * @link \App\Controller\TaskTypesController::view()
     */
    public function testViewListsTheTasksOfTheType(): void
    {
        /** @var \App\Model\Entity\Task $task */
        $task = $this->getTableLocator()->get('Tasks')->find()->firstOrFail();

        $this->login();
        $this->get('/task-types/view/' . $task->task_type_id);

        $this->assertResponseOk();
        // by identifier rather than by number: a task is numbered from one, and a page carries
        // plenty of small numbers that say nothing about tasks
        $this->assertResponseContains($task->id);
    }

    /**
     * A type gathers tasks for years, so its card lists only so many and leads to the listing of
     * all of them.
     *
     * @return void
     * @link \App\Controller\TaskTypesController::viewContain()
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

        $this->login();
        $this->get('/task-types/view/' . $task->task_type_id);
        $this->assertResponseOk();

        /** @var \App\Model\Entity\TaskType $type */
        $type = $this->viewVariable('taskType');
        $this->assertCount(TasksTable::SHOWN_ON_A_CARD, $type->tasks);
        $this->assertResponseContains('Only the first ' . TasksTable::SHOWN_ON_A_CARD . ' are shown.');

        preg_match('/href="([^"]*task_type_ids[^"]*)"/', $this->_getBodyAsString(), $link);
        $listing = html_entity_decode($link[1] ?? '');
        $this->assertNotSame('', $listing);

        $this->session(['Config.Tasks.filter' => ['task_state_ids' => ['6ca9ad0a-7fd2-4b4a-8c0e-1d0f5f4d7e11']]]);
        $this->get($listing);
        $this->assertResponseOk();

        /** @var \Cake\Datasource\Paging\PaginatedInterface<int, \App\Model\Entity\Task> $listed */
        $listed = $this->viewVariable('tasks');
        $this->assertSame(TasksTable::SHOWN_ON_A_CARD + 1, $listed->totalCount());
    }

    /**
     * The form for a new record renders.
     *
     * @return void
     * @link \App\Controller\TaskTypesController::add()
     */
    public function testAdd(): void
    {
        $this->login();
        $this->get('/task-types/add');

        $this->assertResponseOk();
    }

    /**
     * The form of an existing record renders.
     *
     * @return void
     * @link \App\Controller\TaskTypesController::edit()
     */
    public function testEdit(): void
    {
        $this->login();
        $this->get('/task-types/edit/' . $this->firstId('TaskTypes'));

        $this->assertResponseOk();
    }

    /**
     * The delete action runs and redirects. Whether the record really goes depends on what else
     * still references it, which is the application rules' business rather than this test's.
     *
     * @return void
     * @link \App\Controller\TaskTypesController::delete()
     */
    public function testDelete(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/task-types/delete/' . $this->firstId('TaskTypes'));

        $this->assertRedirect();
    }

    /**
     * A type filled in on the form is really stored. Rendering the form proves the page is there;
     * marshalling, validation, the application rules and the save only ever run on a request that
     * carries data.
     *
     * @return void
     * @link \App\Controller\TaskTypesController::add()
     */
    public function testAddStoresAType(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/task-types/add', [
            'name' => 'Installation',
            'customer_required' => '1',
            'contract_required' => '1',
        ]);

        $this->assertRedirect();
        /** @var \App\Model\Entity\TaskType $stored */
        $stored = $this->getTableLocator()->get('TaskTypes')
            ->find()
            ->where(['name' => 'Installation'])
            ->firstOrFail();
        $this->assertTrue($stored->customer_required);
    }

    /**
     * A type that leaves the customer switch empty is not stored, and the operator is given the
     * form back rather than a redirect that would suggest it went through.
     *
     * @return void
     * @link \App\Controller\TaskTypesController::add()
     */
    public function testAddRefusesATypeWithAnEmptyCustomerSwitch(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $taskTypes = $this->getTableLocator()->get('TaskTypes');
        $before = $taskTypes->find()->count();

        $this->post('/task-types/add', [
            'name' => 'Installation',
            'customer_required' => '',
        ]);

        $this->assertResponseOk();
        $this->assertSame($before, $taskTypes->find()->count());
    }

    /**
     * A change made on the form reaches the record.
     *
     * @return void
     * @link \App\Controller\TaskTypesController::edit()
     */
    public function testEditStoresTheChange(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $taskTypeId = $this->firstId('TaskTypes');
        $this->post('/task-types/edit/' . $taskTypeId, ['name' => 'Renamed type']);

        $this->assertRedirect();
        $this->assertSame(
            'Renamed type',
            $this->getTableLocator()->get('TaskTypes')->get($taskTypeId)->name,
        );
    }
}
