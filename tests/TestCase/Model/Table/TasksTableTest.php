<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\TasksTable;
use App\Test\Traits\TableTestTrait;
use App\Test\Traits\WatcherNmsAnswersTrait;
use Cake\Datasource\EntityInterface;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;
use Override;

/**
 * App\Model\Table\TasksTable Test Case
 */
class TasksTableTest extends TestCase
{
    use EmailTrait;
    use TableTestTrait;
    use WatcherNmsAnswersTrait;

    /**
     * Test subject
     *
     * @var \App\Model\Table\TasksTable
     */
    protected $Tasks;

    /**
     * The customer the fixtures carry.
     *
     * @var string
     */
    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    /**
     * The user the fixtures carry, and the address they can be reached at.
     *
     * @var string
     */
    private const HOLDER_ID = '11edb519-be76-4d66-aea0-34188d31eae1';
    private const HOLDER_ADDRESS = 'operator@example.com';

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
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Tasks') ? [] : ['className' => TasksTable::class];
        $this->Tasks = $this->getTableLocator()->get('Tasks', $config);

        // an email about a task links to it, and a link wants routes; a request and the console
        // both bring their own, a bare test case does not
        $this->loadRoutes();

        $this->withWatcherNms();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        /** @phpstan-ignore unset.possiblyHookedProperty */
        unset($this->Tasks);

        $this->withoutWatcherNms();

        parent::tearDown();
    }

    /**
     * A new record with nothing filled in is refused - see the trait for why that is the question
     * worth asking here.
     *
     * @return void
     * @link \App\Model\Table\TasksTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->assertEmptyRecordIsRefused($this->Tasks);
    }

    /**
     * The rules refuse a record whose references point nowhere - see the trait for why that is
     * the question worth asking here.
     *
     * @return void
     * @link \App\Model\Table\TasksTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->assertDanglingReferencesAreRefused($this->Tasks);
    }

    /**
     * A type that insists on a customer refuses a task that names none, and takes the same task
     * once it does.
     *
     * @return void
     * @link \Tasks\Model\Rule\RequiredLinkRule::__invoke()
     */
    public function testATypeDemandingACustomerRefusesATaskWithoutOne(): void
    {
        $type = $this->taskType(['customer_required' => true, 'contract_required' => false]);

        $refused = $this->Tasks->newEntity($this->task($type) + ['customer_id' => null]);
        $this->assertFalse((bool)$this->Tasks->save($refused));
        $this->assertArrayHasKey('isRequiredCustomerFilled', $refused->getError('customer_id'));

        $taken = $this->Tasks->newEntity($this->task($type) + ['customer_id' => self::CUSTOMER_ID]);
        $this->assertNotFalse($this->Tasks->save($taken), 'The link it asked for is there.');
    }

    /**
     * The same for a contract, which is the second pair this application names.
     *
     * @return void
     * @link \Tasks\Model\Rule\RequiredLinkRule::__invoke()
     */
    public function testATypeDemandingAContractRefusesATaskWithoutOne(): void
    {
        $type = $this->taskType(['customer_required' => false, 'contract_required' => true]);

        $refused = $this->Tasks->newEntity($this->task($type) + ['contract_id' => null]);
        $this->assertFalse((bool)$this->Tasks->save($refused));
        $this->assertArrayHasKey('isRequiredContractFilled', $refused->getError('contract_id'));
    }

    /**
     * A type that insists on nothing takes a task that names nothing, which is the branch that
     * would quietly refuse everything if the flag were read the wrong way round.
     *
     * @return void
     * @link \Tasks\Model\Rule\RequiredLinkRule::__invoke()
     */
    public function testATypeDemandingNothingTakesATaskWithNoLinks(): void
    {
        $type = $this->taskType(['customer_required' => false, 'contract_required' => false]);

        $task = $this->Tasks->newEntity($this->task($type));
        $this->assertNotFalse($this->Tasks->save($task));
    }

    /**
     * The third pair is the place of the network, which this application holds without a table of
     * its own - so the type insisting on it is worth asking after separately.
     *
     * @return void
     * @link \Tasks\Model\Rule\RequiredLinkRule::__invoke()
     */
    public function testATypeDemandingAnAccessPointRefusesATaskWithoutOne(): void
    {
        $type = $this->taskType(['access_point_required' => true]);

        $refused = $this->Tasks->newEntity($this->task($type));
        $this->assertFalse((bool)$this->Tasks->save($refused));
        $this->assertArrayHasKey('isRequiredAccessPointFilled', $refused->getError('access_point_id'));

        $this->answerWithTheOneAccessPoint();

        $taken = $this->Tasks->newEntity($this->task($type) + ['access_point_id' => self::ACCESS_POINT_ID]);
        $this->assertNotFalse($this->Tasks->save($taken), 'The link it asked for is there.');
    }

    /**
     * A place the network does not keep is refused, which is what `existsIn` would say of a
     * reference of ours.
     *
     * @return void
     * @link \App\Model\Rule\ExistingAccessPointRule::__invoke()
     */
    public function testATaskNamingAPlaceTheNetworkDoesNotKeepIsRefused(): void
    {
        $this->answerWithTheOneAccessPoint();

        $task = $this->Tasks->newEntity($this->task($this->taskType([])) + ['access_point_id' => Text::uuid()]);

        $this->assertFalse((bool)$this->Tasks->save($task));
        $this->assertArrayHasKey('accessPointIsThere', $task->getError('access_point_id'));
    }

    /**
     * The same task with a place the network does keep goes through, which is the branch that
     * would refuse everything if the answer were read the wrong way round.
     *
     * @return void
     * @link \App\Model\Rule\ExistingAccessPointRule::__invoke()
     */
    public function testATaskNamingAPlaceTheNetworkKeepsIsTaken(): void
    {
        $this->answerWithTheOneAccessPoint();

        $task = $this->Tasks->newEntity(
            $this->task($this->taskType([])) + ['access_point_id' => self::ACCESS_POINT_ID],
        );

        $this->assertNotFalse($this->Tasks->save($task));
    }

    /**
     * A place nobody could look up is taken on trust. An operator whose Watcher NMS is down is not
     * to be stopped from writing down the job they have in front of them, and the list a form picks
     * from comes from the same reading - so there is no way for them to have named a new place
     * anyway.
     *
     * @return void
     * @link \App\Model\Rule\ExistingAccessPointRule::__invoke()
     */
    public function testAPlaceIsTakenOnTrustWhileWatcherNmsSaysNothing(): void
    {
        $this->answerWithAFailure();

        $task = $this->Tasks->newEntity($this->task($this->taskType([])) + ['access_point_id' => Text::uuid()]);

        $this->assertNotFalse($this->Tasks->save($task));
    }

    /**
     * The same where there is no Watcher NMS to ask at all, which is not a failure but an
     * installation that was never given one.
     *
     * @return void
     * @link \App\Model\Rule\ExistingAccessPointRule::__invoke()
     */
    public function testAPlaceIsTakenOnTrustWhereThereIsNoWatcherNms(): void
    {
        $this->withConfigure(['Nms.url' => '', 'Nms.key' => '']);

        $task = $this->Tasks->newEntity($this->task($this->taskType([])) + ['access_point_id' => Text::uuid()]);

        $this->assertNotFalse($this->Tasks->save($task));
    }

    /**
     * Moving a task of a reporting type into a closed state tells the report addresses.
     *
     * This is the whole point of the flag: an installation that is done still has to be invoiced,
     * and whoever does that never sees the task.
     *
     * @return void
     * @link \Tasks\Model\Table\TasksTable::afterSaveCommit()
     */
    public function testClosingATaskOfAReportingTypeTellsTheReportAddresses(): void
    {
        $this->withConfigure(['Report.emails' => ['billing@example.com']]);

        $task = $this->openTaskOfAReportingType();
        $this->assertNotFalse($this->Tasks->save($this->close($task)));

        $this->assertMailCount(1);
        $this->assertMailSentTo('billing@example.com');
        $this->assertMailSubjectContains('Task completed');
    }

    /**
     * The same move on a type that did not ask for it tells nobody, which is the branch that
     * would bury everybody if the flag were read the wrong way round.
     *
     * @return void
     * @link \Tasks\Model\Table\TasksTable::afterSaveCommit()
     */
    public function testClosingATaskOfAnOrdinaryTypeTellsNobody(): void
    {
        $this->withConfigure(['Report.emails' => ['billing@example.com']]);

        $task = $this->openTask($this->taskType(['report_on_completion' => false]));
        $this->assertNotFalse($this->Tasks->save($this->close($task)));

        $this->assertNoMailSent();
    }

    /**
     * A task that was already closed is not reported again. What is news is the move into a
     * closed state, not being in one - otherwise every later correction would send the report
     * once more.
     *
     * @return void
     * @link \Tasks\Model\Table\TasksTable::afterSaveCommit()
     */
    public function testSavingATaskThatIsAlreadyClosedTellsNobodyAgain(): void
    {
        $this->withConfigure(['Report.emails' => ['billing@example.com']]);

        $task = $this->openTaskOfAReportingType();
        $this->Tasks->saveOrFail($this->close($task));

        // saved again, still closed, and this time named the same state on purpose
        $task->set('task_state_id', $this->stateId(completed: true));
        $task->set('subject', 'Corrected afterwards');
        $this->Tasks->saveOrFail($task);

        $this->assertMailCount(1, 'Only the move into the closed state is news.');
    }

    /**
     * Changing anything else about a closed task tells nobody either.
     *
     * @return void
     * @link \Tasks\Model\Table\TasksTable::afterSaveCommit()
     */
    public function testChangingSomethingOtherThanTheStateTellsNobody(): void
    {
        $this->withConfigure(['Report.emails' => ['billing@example.com']]);

        $task = $this->openTaskOfAReportingType();

        $task->set('subject', 'Renamed while still open');
        $this->Tasks->saveOrFail($task);

        $this->assertNoMailSent();
    }

    /**
     * A task filed straight into a closed state is reported too. The work is finished either way
     * and whatever follows it has to know, whether or not anybody ever saw the task open.
     *
     * @return void
     * @link \Tasks\Model\Table\TasksTable::afterSaveCommit()
     */
    public function testATaskFiledStraightIntoAClosedStateIsReported(): void
    {
        $this->withConfigure(['Report.emails' => ['billing@example.com']]);

        $type = $this->taskType(['report_on_completion' => true]);
        $task = $this->Tasks->newEntity(
            ['task_state_id' => $this->stateId(completed: true)] + $this->task($type),
        );

        $this->assertNotFalse($this->Tasks->save($task));
        $this->assertMailCount(1);
    }

    /**
     * A deployment that has nobody configured to be told saves the task and sends nothing, rather
     * than failing the save over a report nobody asked for.
     *
     * @return void
     * @link \Tasks\Service\CompletedTaskReport::send()
     */
    public function testNothingIsSentWhereNobodyIsConfigured(): void
    {
        $this->withConfigure(['Report.emails' => []]);

        $task = $this->openTaskOfAReportingType();

        $this->assertNotFalse($this->Tasks->save($this->close($task)), 'The task still saves.');
        $this->assertNoMailSent();
    }

    /**
     * A task saved with nobody signed in still tells the person holding it.
     *
     * Who acted is read from the footprint of the save, and the footprint is only written where
     * there is a request with an identity. A command or an integration leaves it untouched, and
     * then there is nobody to leave out - the alternative would be reading whatever the column
     * happened to hold from some earlier save and quietly swallowing the notice.
     *
     * @return void
     * @link \Tasks\Model\Table\TasksTable::isSomebodyElses()
     */
    public function testATaskSavedWithNobodySignedInStillTellsItsHolder(): void
    {
        $task = $this->Tasks->newEntity(
            ['user_id' => self::HOLDER_ID] + $this->task($this->taskType([])),
        );

        $this->assertNotFalse($this->Tasks->save($task));

        $this->assertMailCount(1);
        $this->assertMailSentTo(self::HOLDER_ADDRESS);
        $this->assertMailSubjectContains('You have a new task');
    }

    /**
     * A task saved by the very person holding it tells them nothing - they are looking at it.
     *
     * @return void
     * @link \Tasks\Model\Table\TasksTable::isSomebodyElses()
     */
    public function testATaskSavedByItsOwnHolderTellsThemNothing(): void
    {
        // what the footprint would have written had this gone through a signed-in request
        $task = $this->Tasks->newEntity(
            ['user_id' => self::HOLDER_ID, 'created_by' => self::HOLDER_ID] + $this->task($this->taskType([])),
        );

        $this->assertNotFalse($this->Tasks->save($task));
        $this->assertNoMailSent();
    }

    /**
     * A task nobody holds tells nobody, which is the branch that would fail on a null address.
     *
     * @return void
     * @link \Tasks\Model\Table\TasksTable::isSomebodyElses()
     */
    public function testATaskNobodyHoldsTellsNobody(): void
    {
        $task = $this->Tasks->newEntity($this->task($this->taskType([])));

        $this->assertNotFalse($this->Tasks->save($task));
        $this->assertNoMailSent();
    }

    /**
     * An open task of a type that asks to be reported when it closes.
     */
    private function openTaskOfAReportingType(): EntityInterface
    {
        return $this->openTask($this->taskType(['report_on_completion' => true]));
    }

    /**
     * An open task already in the database, so that closing it is a move rather than a creation.
     *
     * @param \Cake\Datasource\EntityInterface $type The type it is filed under.
     * @return \Cake\Datasource\EntityInterface
     */
    private function openTask(EntityInterface $type): EntityInterface
    {
        return $this->Tasks->saveOrFail($this->Tasks->newEntity($this->task($type)));
    }

    /**
     * The same task, moved into a closed state.
     *
     * @param \Cake\Datasource\EntityInterface $task The task to close.
     * @return \Cake\Datasource\EntityInterface
     */
    private function close(EntityInterface $task): EntityInterface
    {
        $task->set('task_state_id', $this->stateId(completed: true));

        return $task;
    }

    /**
     * A task type asking for exactly what it is given.
     *
     * @param array<string, mixed> $flags What this type insists on.
     */
    private function taskType(array $flags): EntityInterface
    {
        $types = $this->getTableLocator()->get('TaskTypes');

        return $types->saveOrFail($types->newEntity($flags + ['name' => 'Written by the test']));
    }

    /**
     * The least a task needs to be saved at all, so that what refuses it is the rule under test.
     *
     * @param \Cake\Datasource\EntityInterface $type The type it is filed under.
     * @return array<string, mixed>
     */
    private function task(EntityInterface $type): array
    {
        return [
            'task_type_id' => $type->get('id'),
            // a task starts open; asked for by name because there is more than one state and an
            // unordered `first()` over them would pick whichever the database felt like
            'task_state_id' => $this->stateId(completed: false),
            'subject' => 'Written by the test',
            'priority' => 1,
        ];
    }

    /**
     * The one state that is, or is not, a closed one.
     *
     * @param bool $completed Whether the state wanted is one that closes a task.
     * @return string
     */
    private function stateId(bool $completed): string
    {
        return (string)$this->getTableLocator()->get('TaskStates')
            ->find()
            ->where(['completed' => $completed])
            ->firstOrFail()
            ->get('id');
    }
}
