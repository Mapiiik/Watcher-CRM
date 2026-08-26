<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\AppUsersTable;
use App\Test\Traits\TableTestTrait;
use Cake\TestSuite\TestCase;
use Override;

/**
 * App\Model\Table\AppUsersTable Test Case
 */
class AppUsersTableTest extends TestCase
{
    use TableTestTrait;

    /**
     * Test subject
     *
     * @var \App\Model\Table\AppUsersTable
     */
    protected $AppUsers;

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
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('AppUsers') ? [] : ['className' => AppUsersTable::class];
        $this->AppUsers = $this->getTableLocator()->get('AppUsers', $config);
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
        unset($this->AppUsers);

        parent::tearDown();
    }

    /**
     * A new record with nothing filled in is refused - see the trait for why that is the question
     * worth asking here.
     *
     * @return void
     * @link \App\Model\Table\PaymentPurposesTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->assertEmptyRecordIsRefused($this->AppUsers);
    }

    /**
     * An account tasks still name cannot stop being one that holds them.
     *
     * The answer to "whose work was this" lives in the task, and it is read through the account. An
     * account switched off while tasks still point at it leaves that answer half told, so the switch
     * waits until the tasks have been moved.
     *
     * @return void
     * @link \App\Model\Table\AppUsersTable::buildRules()
     */
    public function testHoldsTasksCannotBeTurnedOffWhileTasksNameTheAccount(): void
    {
        /** @var \App\Model\Entity\AppUser $user */
        $user = $this->AppUsers->find()->orderBy(['id'])->firstOrFail();

        $saved = $this->AppUsers->save($this->AppUsers->patchEntity($user, ['holds_tasks' => false]));

        $this->assertFalse($saved);
        $this->assertArrayHasKey('holdsNoTasks', $user->getError('holds_tasks'));
    }

    /**
     * Once no task names it any more, the switch goes off.
     *
     * This is the other half of the rule above, and it is what the message asks for: move the tasks
     * elsewhere first.
     *
     * @return void
     * @link \App\Model\Table\AppUsersTable::buildRules()
     */
    public function testHoldsTasksGoesOffOnceNoTaskNamesTheAccount(): void
    {
        /** @var \App\Model\Entity\AppUser $user */
        $user = $this->AppUsers->find()->orderBy(['id'])->firstOrFail();

        $tasks = $this->getTableLocator()->get('Tasks');
        $tasks->updateAll(['user_id' => null], ['user_id' => $user->id]);

        $saved = $this->AppUsers->save($this->AppUsers->patchEntity($user, ['holds_tasks' => false]));

        $this->assertNotFalse($saved);
        $this->assertFalse($this->AppUsers->get($user->id)->holds_tasks);
    }

    /**
     * Saving an account for any other reason is not the rule's business.
     *
     * Signing in writes the time back to the account, so a rule that asked about tasks on every
     * save would lock out everybody who holds one.
     *
     * @return void
     * @link \App\Model\Table\AppUsersTable::buildRules()
     */
    public function testAnAccountThatHoldsTasksCanStillBeSavedForOtherReasons(): void
    {
        /** @var \App\Model\Entity\AppUser $user */
        $user = $this->AppUsers->find()->orderBy(['id'])->firstOrFail();

        $saved = $this->AppUsers->save($this->AppUsers->patchEntity($user, ['first_name' => 'Renamed']));

        $this->assertNotFalse($saved);
    }

    /**
     * An account tasks name cannot be deleted.
     *
     * The database refuses this as well, its foreign key being `NO ACTION` - but it refuses by
     * raising, which reaches the operator as an error page. Asked as a rule, the delete simply
     * comes back false and the controller says so.
     *
     * @return void
     * @link \App\Model\Table\AppUsersTable::buildRules()
     */
    public function testAnAccountTasksNameCannotBeDeleted(): void
    {
        /** @var \App\Model\Entity\AppUser $user */
        $user = $this->AppUsers->find()->orderBy(['id'])->firstOrFail();

        $this->assertFalse($this->AppUsers->delete($user));
        $this->assertNotEmpty($this->AppUsers->get($user->id));
    }

    /**
     * Standing on somebody else's task counts as much as holding one of your own.
     *
     * It is the account being named that the rule is about, not which of the two ways it is
     * named - so the switch waits either way.
     *
     * @return void
     * @link \App\Model\Table\AppUsersTable::buildRules()
     */
    public function testHoldsTasksCannotBeTurnedOffWhileTheAccountIsOnSomebodyElsesTask(): void
    {
        /** @var \App\Model\Entity\AppUser $user */
        $user = $this->AppUsers->find()->orderBy(['id'])->firstOrFail();

        // nothing is held any more, so what answers below is the list rather than the column
        $this->putOnEverySomebodyElsesTask($user->id);

        $saved = $this->AppUsers->save($this->AppUsers->patchEntity($user, ['holds_tasks' => false]));

        $this->assertFalse($saved);
        $this->assertArrayHasKey('holdsNoTasks', $user->getError('holds_tasks'));
    }

    /**
     * Nor can such an account be deleted, for the same reason and by the same two locks.
     *
     * @return void
     * @link \App\Model\Table\AppUsersTable::buildRules()
     */
    public function testAnAccountOnSomebodyElsesTaskCannotBeDeleted(): void
    {
        /** @var \App\Model\Entity\AppUser $user */
        $user = $this->AppUsers->find()->orderBy(['id'])->firstOrFail();

        $this->putOnEverySomebodyElsesTask($user->id);

        $this->assertFalse($this->AppUsers->delete($user));
        $this->assertNotEmpty($this->AppUsers->get($user->id));
    }

    /**
     * Hand every task away and put the account on them instead, so that the only thing left
     * naming it is the list.
     *
     * @param string $user_id The account to move over.
     * @return void
     */
    private function putOnEverySomebodyElsesTask(string $user_id): void
    {
        $tasks = $this->getTableLocator()->get('Tasks');
        $tasks->updateAll(['user_id' => null], []);

        $links = $this->getTableLocator()->get('TaskCollaborators');
        foreach ($tasks->find()->all() as $task) {
            $links->saveOrFail($links->newEntity([
                'task_id' => $task->get('id'),
                'user_id' => $user_id,
            ]));
        }
    }
}
