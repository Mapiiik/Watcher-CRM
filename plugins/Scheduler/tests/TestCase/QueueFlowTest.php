<?php
declare(strict_types=1);

namespace Scheduler\Test\TestCase;

use App\Test\Traits\ConfigureTestTrait;
use Cake\Chronos\Chronos;
use Cake\Command\VersionCommand;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Event\EventManager;
use Cake\I18n\DateTime;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;
use Queue\Model\Entity\QueuedJob;
use Queue\Model\Table\QueuedJobsTable;
use QueueScheduler\Model\Entity\SchedulerRow;
use QueueScheduler\Model\Table\SchedulerRowsTable;
use Scheduler\Event\FailedJobListener;
use Scheduler\Service\Schedule;
use Scheduler\Test\TestApp\FailingCommand;

/**
 * A row on its way through the real dispatcher and the real worker.
 *
 * What these pin is what the settings of the plugin promise, more than what the code does: a job
 * runs at most once, its output is kept, and whoever is on call hears of a failure once.
 */
#[UsesClass(FailedJobListener::class)]
#[UsesClass(Schedule::class)]
class QueueFlowTest extends TestCase
{
    use ConfigureTestTrait;
    use ConsoleIntegrationTestTrait;
    use EmailTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'plugin.Queue.QueuedJobs',
        'plugin.Queue.QueueProcesses',
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
     * What the clock said before a test moved it.
     *
     * @var \Cake\Chronos\Chronos|null
     */
    private ?Chronos $clock;

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->clock = Chronos::getTestNow();

        /** @var \QueueScheduler\Model\Table\SchedulerRowsTable $rows */
        $rows = $this->getTableLocator()->get('QueueScheduler.SchedulerRows');
        $this->rows = $rows;
        $this->rows->deleteAll([]);

        /** @var \Queue\Model\Table\QueuedJobsTable $jobs */
        $jobs = $this->getTableLocator()->get('Queue.QueuedJobs');
        $this->jobs = $jobs;
        $this->jobs->deleteAll([]);

        $this->withConfigure(['Report.errorEmails' => ['oncall@example.com']]);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        Chronos::setTestNow($this->clock);
        $this->restoreConfigure();

        parent::tearDown();
    }

    /**
     * Run a command in an application of its own, as it would be in a process of its own. Each
     * start adds the listeners, and in one process they would add up.
     *
     * @param string $command The command.
     * @return void
     */
    private function runCommand(string $command): void
    {
        EventManager::instance(new EventManager());

        $this->exec($command);
    }

    /**
     * A row that is on and due.
     *
     * @param class-string $command The command to run.
     * @return \QueueScheduler\Model\Entity\SchedulerRow
     */
    private function dueRow(string $command): SchedulerRow
    {
        (new Schedule())->addMissing(['Example' => ['command' => $command, 'frequency' => '0 1 * * *']]);
        $this->rows->updateAll(['enabled' => true, 'next_run' => DateTime::now()->subMinutes(1)], ['name' => 'Example']);

        /** @var \QueueScheduler\Model\Entity\SchedulerRow */
        return $this->rows->find()->where(['name' => 'Example'])->firstOrFail();
    }

    /**
     * The job of the row, as it stands.
     *
     * @return \Queue\Model\Entity\QueuedJob
     */
    private function theJob(): QueuedJob
    {
        /** @var \Queue\Model\Entity\QueuedJob */
        return $this->jobs->find()->firstOrFail();
    }

    /**
     * The dispatcher puts the row in the queue, the worker runs it and stops, the output stays.
     *
     * @return void
     * @link \QueueScheduler\Queue\Task\CommandExecuteTask::run()
     */
    public function testARowIsRunAndItsOutputKept(): void
    {
        $this->dueRow(VersionCommand::class);

        $this->runCommand('scheduler run');
        $this->assertExitSuccess();
        $this->assertNull($this->theJob()->fetched);

        $this->runCommand('queue run');
        $this->assertExitSuccess();

        $job = $this->theJob();

        $this->assertNotNull($job->completed);
        $this->assertStringContainsString('5.', (string)$job->output);
        $this->assertMailCount(0);
    }

    /**
     * A row is not queued again while its job waits, so a long job in front does not pile them up.
     *
     * @return void
     * @link \QueueScheduler\Model\Table\SchedulerRowsTable::run()
     */
    public function testARowWaitingInTheQueueIsNotQueuedAgain(): void
    {
        $this->dueRow(VersionCommand::class);

        $this->runCommand('scheduler run');

        Chronos::setTestNow(Chronos::now()->addDays(1)->addHours(1));
        $this->runCommand('scheduler run');

        $this->assertSame(1, $this->jobs->find()->count());
    }

    /**
     * A day missed is made up for once. The window opens at the time of the row, because a window
     * opening earlier would run it there and then again at its time: two letters in one day.
     *
     * @return void
     * @link \QueueScheduler\Model\Entity\SchedulerRow::isDue()
     */
    public function testAMissedRowWithAWindowRunsOnceTheDayAfter(): void
    {
        Chronos::setTestNow(new Chronos('2026-11-10 09:00:00'));

        (new Schedule())->addMissing([
            'Example' => [
                'command' => VersionCommand::class,
                'frequency' => '55 12 * * *',
                'window' => ['from' => '12:55', 'to' => '18:00'],
            ],
        ]);
        $this->rows->updateAll(['enabled' => true, 'next_run' => new DateTime('2026-11-09 12:55:00')], ['name' => 'Example']);

        $this->runCommand('scheduler run');
        $this->assertSame(0, $this->jobs->find()->count());

        Chronos::setTestNow(new Chronos('2026-11-10 12:55:30'));
        $this->runCommand('scheduler run');
        $this->assertSame(1, $this->jobs->find()->count());
        $this->runCommand('queue run');

        Chronos::setTestNow(new Chronos('2026-11-10 17:00:00'));
        $this->runCommand('scheduler run');
        $this->assertSame(1, $this->jobs->find()->count());
    }

    /**
     * A job that failed stays failed: no worker runs it again until its next time.
     *
     * @return void
     * @link \Scheduler\Event\FailedJobListener::report()
     */
    public function testAFailedJobIsNotRunAgain(): void
    {
        $this->dueRow(FailingCommand::class);

        $this->runCommand('scheduler run');
        $this->runCommand('queue run');
        $this->runCommand('queue run');

        $job = $this->theJob();

        $this->assertSame(QueuedJobsTable::STATUS_ABORTED, $job->status);
        $this->assertSame(1, $job->attempts);
        $this->assertNull($job->completed);
    }

    /**
     * A row that keeps failing is reported the first time, not at every run.
     *
     * @return void
     * @link \Scheduler\Event\FailedJobListener::report()
     */
    public function testARowThatKeepsFailingIsReportedOnce(): void
    {
        $this->dueRow(FailingCommand::class);

        $this->runCommand('scheduler run');
        $this->runCommand('queue run');

        $this->assertMailCount(1);
        $this->assertMailSentTo('oncall@example.com');
        $this->assertMailContains('Example');
        $this->assertMailContains(FailingCommand::class);

        Chronos::setTestNow(Chronos::now()->addDays(1)->addHours(1));
        $this->runCommand('scheduler run');
        $this->runCommand('queue run');

        // the row did run again and fail again, and nobody was told a second time
        $this->assertSame(1, $this->rows->find()->firstOrFail()->get('consecutive_failures'));
        $this->assertSame(QueuedJobsTable::STATUS_ABORTED, $this->theJob()->status);
        $this->assertMailCount(1);
    }

    /**
     * A job nothing scheduled is reported the same, under the name of its task.
     *
     * @return void
     * @link \Scheduler\Event\FailedJobListener::report()
     */
    public function testAJobOutsideTheScheduleIsReportedToo(): void
    {
        $this->jobs->createJob('QueueScheduler.CommandExecute', ['class' => FailingCommand::class, 'args' => []]);

        $this->runCommand('queue run');

        $this->assertMailCount(1);
        $this->assertMailContains('QueueScheduler.CommandExecute');
    }

    /**
     * A report that cannot be sent must not take the worker with it.
     *
     * @return void
     * @link \Scheduler\Event\FailedJobListener::report()
     */
    public function testAReportThatCannotBeSentLeavesTheWorkerRunning(): void
    {
        $this->withConfigure(['Report.errorEmails' => ['not an address']]);
        $this->dueRow(FailingCommand::class);

        $this->runCommand('scheduler run');
        $this->runCommand('queue run');

        $this->assertExitSuccess();
        $this->assertSame(QueuedJobsTable::STATUS_ABORTED, $this->theJob()->status);
    }
}
