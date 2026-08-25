<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api;

use App\Controller\Api\TasksController;
use App\Test\Traits\ControllerTestTrait;
use Cake\Datasource\EntityInterface;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\Api\TasksController Test Case
 *
 * The cuts another application may ask for. Every one of them stands for a finder the task
 * listing already uses, so what is tested here is that the right cut is reached for and that the
 * answer keeps its shape - not the choosing itself, which the table's own tests cover.
 */
#[UsesClass(TasksController::class)]
class TasksControllerTest extends TestCase
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
    ];

    /**
     * With nothing asked of it, the search hands over what there is and says how much that was.
     *
     * @return void
     * @link \App\Controller\Api\TasksController::search()
     */
    public function testSearchWithoutACutHandsOverEverything(): void
    {
        $this->login('api');
        $this->get('/api/tasks/search.json');

        $this->assertResponseOk();
        $this->assertSame(1, $this->viewVariable('total'));
        $this->assertCount(1, (array)$this->viewVariable('tasks'));
    }

    /**
     * A place of the network is one of the cuts, because that is what the other application
     * files its own records under.
     *
     * @return void
     * @link \App\Controller\Api\TasksController::search()
     */
    public function testSearchNarrowsToOnePlaceOfTheNetwork(): void
    {
        $place = Text::uuid();
        $this->taskAt($place, 'At the mast');

        $this->login('api');
        $this->get('/api/tasks/search.json?access_point_id=' . $place);

        $this->assertResponseOk();
        $this->assertSame(1, $this->viewVariable('total'));
        $this->assertSame('At the mast', $this->firstTask()->get('subject'));

        // and a place nothing is filed at is answered with nought rather than with everything
        $this->get('/api/tasks/search.json?access_point_id=' . Text::uuid());

        $this->assertResponseOk();
        $this->assertSame(0, $this->viewVariable('total'));
    }

    /**
     * The two applications share no identifiers for people, so whose a task is gets asked by the
     * one thing they do share.
     *
     * @return void
     * @link \App\Controller\Api\TasksController::search()
     */
    public function testSearchFindsWhoseATaskIsByTheirUsername(): void
    {
        $holder = $this->getTableLocator()->get('AppUsers')->find()->firstOrFail();
        // The fixture hands its own task to this same person, which would leave the count saying
        // nothing about the cut. Nobody holds it now.
        $this->getTableLocator()->get('Tasks')->updateAll(['user_id' => null], []);

        $this->taskAt(Text::uuid(), 'Held by somebody', $holder->get('id'));

        $this->login('api');
        $this->get('/api/tasks/search.json?user=' . urlencode((string)$holder->get('username')));

        $this->assertResponseOk();
        $this->assertSame(1, $this->viewVariable('total'));
        $this->assertSame('Held by somebody', $this->firstTask()->get('subject'));
    }

    /**
     * A name this application has never heard of is an answer, not a fault - and the answer is
     * that nobody here holds anything.
     *
     * @return void
     * @link \App\Controller\Api\TasksController::search()
     */
    public function testSearchAnswersForANameItDoesNotKnowWithNothing(): void
    {
        $this->login('api');
        $this->get('/api/tasks/search.json?user=nobody.at.all');

        $this->assertResponseOk();
        $this->assertSame(0, $this->viewVariable('total'));
        $this->assertSame([], $this->viewVariable('tasks'));
    }

    /**
     * The count is taken before the limit, so that whoever draws the first few of them can say
     * how many they are drawing them out of.
     *
     * @return void
     * @link \App\Controller\Api\TasksController::search()
     */
    public function testSearchCountsBeyondWhatItHandsOver(): void
    {
        $place = Text::uuid();
        $this->taskAt($place, 'One');
        $this->taskAt($place, 'Two');
        $this->taskAt($place, 'Three');

        $this->login('api');
        $this->get('/api/tasks/search.json?access_point_id=' . $place . '&limit=2');

        $this->assertResponseOk();
        $this->assertSame(3, $this->viewVariable('total'));
        $this->assertCount(2, (array)$this->viewVariable('tasks'));
    }

    /**
     * Unfinished above finished, whichever order they were written in.
     *
     * @return void
     * @link \App\Controller\Api\TasksController::search()
     */
    public function testSearchPutsTheUnfinishedFirst(): void
    {
        $place = Text::uuid();
        // written finished first, so the order asked for cannot be the order they were made in
        $this->taskAt($place, 'Long since seen to', null, $this->stateThatIs(true, -50));
        $this->taskAt($place, 'Still to do', null, $this->stateThatIs(false, 0));

        $this->login('api');
        $this->get('/api/tasks/search.json?access_point_id=' . $place);

        $this->assertResponseOk();
        $this->assertSame('Still to do', $this->firstTask()->get('subject'));
    }

    /**
     * Only the unfinished, which is what a card of somebody else's work is drawn from.
     *
     * @return void
     * @link \App\Controller\Api\TasksController::search()
     */
    public function testSearchCanLeaveOutWhatIsFinished(): void
    {
        $place = Text::uuid();
        $this->taskAt($place, 'Long since seen to', null, $this->stateThatIs(true, -50));
        $this->taskAt($place, 'Still to do', null, $this->stateThatIs(false, 0));

        $this->login('api');
        $this->get('/api/tasks/search.json?access_point_id=' . $place . '&active=1');

        $this->assertResponseOk();
        $this->assertSame(1, $this->viewVariable('total'));
        $this->assertSame('Still to do', $this->firstTask()->get('subject'));
    }

    /**
     * Something that is not the number of a place at all is turned away rather than asked of the
     * database, which would answer with a fault of its own.
     *
     * @return void
     * @link \App\Controller\Api\TasksController::search()
     */
    public function testSearchTurnsAwayWhatIsNotTheNumberOfAPlace(): void
    {
        $this->login('api');
        $this->get('/api/tasks/search.json?access_point_id=not-a-number');

        $this->assertResponseError();
    }

    /**
     * What the account this API is reached with may not do.
     *
     * Writing is left shut on purpose - see {@see \App\Controller\Api\AppController}. This is here
     * so that opening it is a deliberate act with a test to change, rather than something that
     * happens quietly.
     *
     * @return void
     * @link \App\Controller\Api\TasksController::add()
     */
    public function testWritingIsNotOfferedToTheAccountTheApiIsReachedWith(): void
    {
        $this->login('api');
        $this->post('/api/tasks.json', ['subject' => 'Written from outside']);

        // Refused rather than broken: the account is not granted the action.
        $this->assertResponseCode(403);
    }

    /**
     * A task state of the kind the test needs.
     *
     * The fixtures carry one state and it counts as finished, so whichever kind a test is about
     * it says so rather than taking what happens to be there.
     *
     * @param bool $completed Whether a task in it is done with.
     * @param int $priority Where it sorts against the others.
     * @return string
     */
    private function stateThatIs(bool $completed, int $priority): string
    {
        $taskStates = $this->getTableLocator()->get('TaskStates');

        return (string)$taskStates->saveOrFail($taskStates->newEntity([
            'name' => $completed ? 'Seen to' : 'Waiting',
            'color' => '#f4f4f4',
            'priority' => $priority,
            'completed' => $completed,
        ]))->get('id');
    }

    /**
     * A task filed at a place, of whatever type the fixtures happen to carry.
     *
     * @param string $accessPointId The place it is to be done at.
     * @param string $subject What it says it is about.
     * @param string|null $userId Whoever is holding it, if anybody.
     * @param string|null $taskStateId The state it is in; the fixtures' own where not said.
     * @return void
     */
    private function taskAt(
        string $accessPointId,
        string $subject,
        ?string $userId = null,
        ?string $taskStateId = null,
    ): void {
        $tasks = $this->getTableLocator()->get('Tasks');
        $contract = $this->getTableLocator()->get('Contracts')->find()->firstOrFail();
        // Read from the catalogues rather than off an existing task: after the first call there
        // are tasks of this test's own making, and one of those could come back instead.
        $taskType = $this->getTableLocator()->get('TaskTypes')->find()->firstOrFail();
        $anyState = $this->getTableLocator()->get('TaskStates')->find()->firstOrFail();

        // the fixtures write the identity column with the values they carry, which leaves the
        // identity itself where it started
        $this->advanceIdentity('Tasks', 'nid');

        $tasks->saveOrFail($tasks->newEntity([
            'task_state_id' => $taskStateId ?? $anyState->get('id'),
            'task_type_id' => $taskType->get('id'),
            // the type the fixtures carry insists on both of these
            'customer_id' => $contract->get('customer_id'),
            'contract_id' => $contract->get('id'),
            'access_point_id' => $accessPointId,
            'user_id' => $userId,
            'subject' => $subject,
            'priority' => 0,
        ]));
    }

    /**
     * The first of whatever the search handed over.
     *
     * @return \Cake\Datasource\EntityInterface
     */
    private function firstTask(): EntityInterface
    {
        /** @var array<\Cake\Datasource\EntityInterface> $tasks */
        $tasks = (array)$this->viewVariable('tasks');

        return $tasks[0];
    }
}
