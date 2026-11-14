<?php
declare(strict_types=1);

namespace Scheduler\Test\TestCase\Dashboard;

use App\Test\Traits\ControllerTestTrait;
use Cake\Cache\Cache;
use Cake\Command\VersionCommand;
use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use Queue\Model\Table\QueuedJobsTable;
use QueueScheduler\Command\RunCommand;
use QueueScheduler\Model\Table\SchedulerRowsTable;
use Scheduler\Dashboard\Card\SchedulerCard;
use Scheduler\Model\Enum\SchedulerState;
use Scheduler\Service\Schedule;
use Scheduler\Service\SchedulerHealth;

/**
 * Scheduler\Dashboard\Card\SchedulerCard Test Case
 *
 * Asked the way the dashboard asks for it, on its own URL, which is the same in both applications.
 */
#[CoversClass(SchedulerCard::class)]
#[UsesClass(SchedulerHealth::class)]
#[UsesClass(Schedule::class)]
class SchedulerCardTest extends TestCase
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
        'plugin.Queue.QueuedJobs',
        'plugin.QueueScheduler.SchedulerRows',
    ];

    /**
     * @var \QueueScheduler\Model\Table\SchedulerRowsTable
     */
    private SchedulerRowsTable $rows;

    /**
     * @var \Queue\Model\Table\QueuedJobsTable
     */
    private QueuedJobsTable $jobs;

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        /** @var \QueueScheduler\Model\Table\SchedulerRowsTable $rows */
        $rows = $this->getTableLocator()->get('QueueScheduler.SchedulerRows');
        $this->rows = $rows;
        $this->rows->deleteAll([]);

        /** @var \Queue\Model\Table\QueuedJobsTable $jobs */
        $jobs = $this->getTableLocator()->get('Queue.QueuedJobs');
        $this->jobs = $jobs;
        $this->jobs->deleteAll([]);

        Cache::clear();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        Cache::clear();

        parent::tearDown();
    }

    /**
     * The card as the dashboard fetched it.
     *
     * @return array<string, mixed>
     */
    private function cardData(): array
    {
        /** @var \Scheduler\Dashboard\Card\SchedulerCard $card */
        $card = $this->viewVariable('card');

        return $card->data();
    }

    /**
     * All well: when the dispatcher last ran, and the way to the pages of the queue.
     *
     * @return void
     */
    public function testAllWellSaysWhenTheDispatcherLastRan(): void
    {
        Cache::write(RunCommand::HEARTBEAT_KEY, time() - 30);
        $this->login();

        $this->get('/dashboard/card/scheduler');

        $this->assertResponseOk();
        $this->assertSame(SchedulerState::Running, $this->cardData()['state']);
        $this->assertResponseContains('The dispatcher last ran at');
        $this->assertResponseContains('href="/admin/queue"');
        $this->assertResponseContains('href="/admin/queue-scheduler"');
        $this->assertResponseNotContains('message warning');
    }

    /**
     * Right after a deployment the cache is empty, and the card must not raise an alarm over it.
     *
     * @return void
     */
    public function testNoHeartbeatRaisesNoAlarm(): void
    {
        $this->login();

        $this->get('/dashboard/card/scheduler');

        $this->assertResponseOk();
        $this->assertSame(SchedulerState::Unknown, $this->cardData()['state']);
        $this->assertResponseContains('<p class="dashboard-hint">');
        $this->assertResponseContains('has not reported since the cache was last cleared');
        $this->assertResponseNotContains('message warning');
    }

    /**
     * A job that never ends, a row that did not run and a dispatcher gone quiet, all said at once.
     *
     * @return void
     */
    public function testTheProblemsAreListedUnderAWarning(): void
    {
        $job = $this->jobs->createJob('QueueScheduler.CommandExecute', ['class' => VersionCommand::class, 'args' => []]);
        $this->jobs->updateAll(['fetched' => DateTime::now()->subHours(3), 'attempts' => 1], ['id' => $job->id]);
        (new Schedule())->addMissing(['Example' => ['command' => VersionCommand::class, 'frequency' => '0 1 * * *']]);
        $this->rows->updateAll(['enabled' => true, 'next_run' => DateTime::now()->subHours(4)], ['name' => 'Example']);
        Cache::write(RunCommand::HEARTBEAT_KEY, time() - 3600);
        $this->login();

        $this->get('/dashboard/card/scheduler');

        $this->assertResponseOk();
        $data = $this->cardData();
        $this->assertSame(SchedulerState::Attention, $data['state']);
        $this->assertCount(3, $data['problems']);
        $this->assertResponseContains('message warning');
        $this->assertResponseContains('Job ' . $job->id . ' ');
        $this->assertResponseContains('Row Example was due');
        $this->assertResponseContains('The dispatcher last ran at');
    }

    /**
     * Roles that are not administrators.
     *
     * @return array<string, array{string}>
     */
    public static function otherRoles(): array
    {
        return [
            'manager' => ['manager'],
            'network manager' => ['network-manager'],
            'user' => ['user'],
        ];
    }

    /**
     * Nobody but an administrator may open the pages of the queue, so nobody else gets the card.
     *
     * @param string $role The role asking.
     * @return void
     */
    #[DataProvider('otherRoles')]
    public function testOnlyAdministratorsGetTheCard(string $role): void
    {
        $this->login($role);

        $this->get('/dashboard/card/scheduler');

        $this->assertResponseCode(404);
    }
}
