<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\TasksController;
use App\Model\Entity\Task;
use App\Test\Traits\ControllerTestTrait;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Maps\Marker;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\TasksController Test Case
 */
#[UsesClass(TasksController::class)]
class TasksControllerTest extends TestCase
{
    // saving a task mails whoever watches it; without this the message is left behind for
    // whichever test asks about mail next
    use ControllerTestTrait;
    use EmailTrait;
    use IntegrationTestTrait;

    /**
     * Customer owning the task from the Tasks fixture.
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
     * Address the user the fixture task is assigned to can be reached at.
     *
     * @var string
     */
    private const USER_ADDRESS = 'operator@example.com';

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
        'app.Emails',
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
     * An account that does not take work on is not offered as somebody to hand a task to.
     *
     * Being able to sign in is a different question from being somebody a task can belong to: an
     * integration signs in too, and offering it here is only ever a way to lose a task.
     *
     * @return void
     * @link \App\Controller\TasksController::add()
     */
    public function testAddDoesNotOfferAnAccountThatHoldsNoTasks(): void
    {
        $users = $this->getTableLocator()->get('AppUsers');
        /** @var \App\Model\Entity\AppUser $integration */
        $integration = $users->get($this->firstId('AppUsers'));

        // the switch only goes off once no task names the account, which is the rule the form
        // itself enforces - so the tasks are moved away first, exactly as it asks
        $this->getTableLocator()->get('Tasks')
            ->updateAll(['user_id' => null], ['user_id' => $integration->id]);
        $users->saveOrFail($users->patchEntity($integration, ['holds_tasks' => false]));

        $this->login();
        $this->get('/tasks/add');

        $this->assertResponseOk();

        /** @var iterable<array<string, mixed>> $offered */
        $offered = $this->viewVariable('users');
        $offeredIds = [];
        foreach ($offered as $option) {
            $offeredIds[] = $option['value'];
        }

        $this->assertNotContains($integration->id, $offeredIds);
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

    /**
     * Added under its customer and the contract, the record is filed under them without the form saying so.
     *
     * The form under a customer and the contract leaves those fields out - the route already says which record it is,
     * and the controller fills them in. Posting them in the body instead, as a test reaching the
     * flat route does, asks a different question and leaves this one unasked.
     *
     * @return void
     * @link \App\Controller\TasksController::add()
     */
    public function testAddUnderTheRouteFilesItUnderTheRoute(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $before = $this->idsIn('Tasks');
        $this->post('/customers/' . self::CUSTOMER_ID . '/contracts/' . self::CONTRACT_ID . '/tasks/add', [
            'task_state_id' => $this->firstId('TaskStates'),
            'task_type_id' => $this->firstId('TaskTypes'),
            'subject' => 'Nested task',
        ]);

        $this->assertRedirect();
        $added = $this->addedRecord('Tasks', $before);
        $this->assertSame(self::CUSTOMER_ID, $added->get('customer_id'));
        $this->assertSame(self::CONTRACT_ID, $added->get('contract_id'));
    }

    /**
     * A change to somebody else's task tells them about it, and says it is a change rather than a
     * task they have not seen before.
     *
     * The person acting is logged in without an id of their own, so the task counts as being
     * somebody else's - which is what the notification hangs on.
     *
     * @return void
     * @link \App\Controller\TasksController::edit()
     */
    public function testEditNotifiesTheUserTheTaskIsAssignedTo(): void
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
        $this->assertMailCount(1);
        $this->assertMailSentTo(self::USER_ADDRESS);
        $this->assertMailSubjectContains('You have changes in task');
        $this->assertMailContainsHtml('Antenna replacement');
    }

    /**
     * The listing can be narrowed to what wants attention: a deadline near or past, or an
     * urgent mark whatever the date says.
     *
     * The fixture task is finished and dated years back, so the tasks this asks about are
     * written here - a deadline counted from today cannot be put in a fixture.
     *
     * @return void
     * @link \App\Controller\TasksController::index()
     */
    public function testIndexNarrowedToPressingTasks(): void
    {
        $pressing = $this->openTask(['critical_date' => Date::today()->addDays(2)]);
        $urgent = $this->openTask(['priority' => Task::PRIORITY_URGENT]);
        $quiet = $this->openTask(['critical_date' => Date::today()->addDays(400)]);
        // an estimate is a plan: gone by, it is news; still ahead, it is not
        $slipped = $this->openTask(['estimated_date' => Date::today()->subDays(3)]);
        $planned = $this->openTask(['estimated_date' => Date::today()->addDays(30)]);

        $this->login();
        $this->get('/tasks?pressing=1&stale=0&show_completed=0&user_id=');

        $this->assertResponseOk();

        $numbers = $this->listedTaskIds();
        $this->assertContains($pressing->id, $numbers);
        $this->assertContains($urgent->id, $numbers, 'urgent counts whatever its date says');
        $this->assertNotContains($quiet->id, $numbers);
        $this->assertContains($slipped->id, $numbers, 'the plan has slipped');
        $this->assertNotContains($planned->id, $numbers, 'a plan for later is not news yet');
    }

    /**
     * The listing can be narrowed to what has lain untouched. Nothing brings a forgotten
     * task back on its own, so this is what stands in for that.
     *
     * @return void
     * @link \App\Controller\TasksController::index()
     */
    public function testIndexNarrowedToStaleTasks(): void
    {
        $stale = $this->openTask([]);
        $fresh = $this->openTask([]);

        // the timestamp behavior writes `modified` on save, so it is set aside afterwards
        $this->getTableLocator()->get('Tasks')->updateAll(
            ['modified' => DateTime::now()->subDays(90)],
            ['id' => $stale->id],
        );

        $this->login();
        $this->get('/tasks?pressing=0&stale=1&show_completed=0&user_id=');

        $this->assertResponseOk();

        $numbers = $this->listedTaskIds();
        $this->assertContains($stale->id, $numbers);
        $this->assertNotContains($fresh->id, $numbers);
    }

    /**
     * The two filters reach the form as booleans even where the request named neither.
     * `toBool()` answers `null` to a parameter that is not there, and the checkbox that
     * reads it back is declared as a plain bool.
     *
     * @return void
     * @link \App\Controller\TasksController::index()
     */
    public function testTheAttentionFiltersAreAlwaysBooleans(): void
    {
        $this->login();
        $this->get('/tasks');

        $this->assertResponseOk();

        /** @var \Cake\Form\Form $filterForm */
        $filterForm = $this->viewVariable('filterForm');

        $this->assertFalse($filterForm->getData('pressing'));
        $this->assertFalse($filterForm->getData('stale'));
    }

    /**
     * An unfinished task, in a state that counts as unfinished.
     *
     * @param array<string, mixed> $data What this task differs by.
     * @return \App\Model\Entity\Task
     */
    private function openTask(array $data): Task
    {
        $states = $this->getTableLocator()->get('TaskStates');
        $open = $states->find()->where(['completed' => false])->first();
        if ($open === null) {
            $open = $states->saveOrFail($states->newEntity([
                'name' => 'Open',
                'color' => '#ffffff',
                'completed' => false,
                'priority' => 1,
            ]));
        }

        $tasks = $this->getTableLocator()->get('Tasks');

        /** @var \App\Model\Entity\Task $task */
        $task = $tasks->saveOrFail($tasks->newEntity($data + [
            'task_type_id' => $this->firstId('TaskTypes'),
            'task_state_id' => $open->get('id'),
            'subject' => 'Written by the test',
            'priority' => Task::PRIORITY_NORMAL,
            // the fixture task type asks for both
            'customer_id' => self::CUSTOMER_ID,
            'contract_id' => self::CONTRACT_ID,
        ]));

        return $task;
    }

    /**
     * The ids of the tasks the listing came back with.
     *
     * @return list<string>
     */
    private function listedTaskIds(): array
    {
        $ids = [];
        /** @var iterable<\App\Model\Entity\Task> $tasks */
        $tasks = $this->viewVariable('tasks');
        foreach ($tasks as $task) {
            $ids[] = $task->id;
        }

        return $ids;
    }

    /**
     * A task hangs on the map where its contract is installed, in its state's colour.
     *
     * @return void
     * @link \App\Controller\TasksController::map()
     */
    public function testMapDrawsAnOpenTaskWhereItsContractIs(): void
    {
        // The helper makes the open state, and gives it this colour, because the fixtures carry
        // only a finished one.
        $this->openTask([]);

        $this->login();
        $this->get('/tasks/map');

        $this->assertResponseOk();

        /** @var array<string, \Maps\Marker> $markers */
        $markers = $this->viewVariable('mapMarkers');
        $this->assertCount(1, $markers);

        $marker = reset($markers);
        $this->assertInstanceOf(Marker::class, $marker);
        // The installation address the fixtures place at 1, 1.
        $this->assertSame(1.0, $marker->position->lat);
        $this->assertSame(1.0, $marker->position->lng);
        $this->assertSame('#ffffff', $marker->color);
        $this->assertStringContainsString('Written by the test', $marker->content);
    }

    /**
     * A task whose address was never located is left off rather than drawn at nought.
     *
     * @return void
     * @link \App\Controller\TasksController::map()
     */
    public function testMapLeavesOffATaskWithNoPlace(): void
    {
        $addresses = $this->getTableLocator()->get('Addresses');
        $address = $addresses->get('ab4bab00-9fe8-48b1-beef-3832a4f933a8');
        $address->set('gps_y', null);
        $address->set('gps_x', null);
        $addresses->saveOrFail($address);

        $this->openTask([]);

        $this->login();
        $this->get('/tasks/map');

        $this->assertResponseOk();
        $this->assertSame([], $this->viewVariable('mapMarkers'));
    }

    /**
     * The finished ones are not what a round is planned around.
     *
     * @return void
     * @link \App\Controller\TasksController::map()
     */
    public function testMapLeavesOffACompletedTask(): void
    {
        $this->login();
        $this->get('/tasks/map');

        $this->assertResponseOk();
        $this->assertSame([], $this->viewVariable('mapMarkers'));
    }

    /**
     * The map can be narrowed the way the listing can, so a round is planned around one kind of
     * task rather than around all of them at once.
     *
     * @return void
     * @link \App\Controller\TasksController::map()
     */
    public function testMapNarrowsToOneTaskType(): void
    {
        // The one the fixtures carry is written first, because the helper takes whichever type it
        // finds and there must still be only one to find.
        $first = $this->openTask([]);

        $types = $this->getTableLocator()->get('TaskTypes');
        $other = $types->saveOrFail($types->newEntity([
            'name' => 'Something else',
            'customer_required' => true,
            'contract_required' => true,
        ]));
        $this->openTask(['task_type_id' => $other->get('id')]);

        $this->assertNotSame($first->get('task_type_id'), $other->get('id'));

        $this->login();
        $this->get('/tasks/map');

        $this->assertResponseOk();
        $this->assertCount(2, (array)$this->viewVariable('mapMarkers'), 'Both are drawn unasked.');

        $this->get('/tasks/map?task_type_id=' . $other->get('id'));

        $this->assertResponseOk();
        $this->assertCount(1, (array)$this->viewVariable('mapMarkers'));
    }

    /**
     * A finished state is not offered on the map, because the map draws what is still waiting and
     * picking one could only ever answer with an empty map.
     *
     * @return void
     * @link \App\Controller\TasksController::map()
     */
    public function testMapOffersOnlyTheStatesItCanDraw(): void
    {
        $states = $this->getTableLocator()->get('TaskStates');
        $waiting = $states->saveOrFail($states->newEntity([
            'name' => 'Waiting',
            'color' => '#ff8800',
            'completed' => false,
            'priority' => 1,
        ]));
        $finished = $states->saveOrFail($states->newEntity([
            'name' => 'Done',
            'color' => '#cccccc',
            'completed' => true,
            'priority' => 1,
        ]));

        $this->login();
        $this->get('/tasks/map');

        $this->assertResponseOk();

        $offered = array_keys((array)$this->viewVariable('taskStates')->toArray());
        $this->assertContains($waiting->get('id'), $offered);
        $this->assertNotContains($finished->get('id'), $offered);
    }
}
