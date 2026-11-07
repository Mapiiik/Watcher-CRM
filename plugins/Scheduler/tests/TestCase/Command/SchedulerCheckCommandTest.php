<?php
declare(strict_types=1);

namespace Scheduler\Test\TestCase\Command;

use App\Test\Traits\ConfigureTestTrait;
use Cake\Cache\Cache;
use Cake\Command\VersionCommand;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\I18n\DateTime;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;
use Queue\Model\Table\QueuedJobsTable;
use QueueScheduler\Command\RunCommand;
use QueueScheduler\Model\Table\SchedulerRowsTable;
use Scheduler\Command\SchedulerCheckCommand;
use Scheduler\Service\Schedule;

/**
 * Scheduler\Command\SchedulerCheckCommand Test Case
 *
 * What it looks for is what nothing announces, and the answer to each of those is that somebody is
 * told once a day and not every quarter of an hour.
 */
#[UsesClass(SchedulerCheckCommand::class)]
#[UsesClass(Schedule::class)]
class SchedulerCheckCommandTest extends TestCase
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

        $this->withConfigure(['Report.errorEmails' => ['oncall@example.com']]);
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
        $this->restoreConfigure();

        parent::tearDown();
    }

    /**
     * A job a worker took and never finished.
     *
     * @param int $hours How long ago it was taken.
     * @return int The id of the job.
     */
    private function jobTaken(int $hours): int
    {
        $job = $this->jobs->createJob('QueueScheduler.CommandExecute', ['class' => VersionCommand::class, 'args' => []]);
        $this->jobs->updateAll(['fetched' => DateTime::now()->subHours($hours), 'attempts' => 1], ['id' => $job->id]);

        return $job->id;
    }

    /**
     * A row that is on, last due the given time ago.
     *
     * @param int $hours How long ago it was due.
     * @param bool $enabled Whether it is on.
     * @return void
     */
    private function rowDue(int $hours, bool $enabled = true): void
    {
        (new Schedule())->addMissing(['Example' => ['command' => VersionCommand::class, 'frequency' => '0 1 * * *']]);
        $this->rows->updateAll(['enabled' => $enabled, 'next_run' => DateTime::now()->subHours($hours)], ['name' => 'Example']);
    }

    /**
     * Nothing wrong, nothing said. This is what runs every quarter of an hour.
     *
     * @return void
     * @link \Scheduler\Command\SchedulerCheckCommand::execute()
     */
    public function testNothingToReport(): void
    {
        $this->jobTaken(1);
        $this->rowDue(1);
        $this->rowDue(100, false);
        Cache::write(RunCommand::HEARTBEAT_KEY, time());

        $this->exec('scheduler check');

        $this->assertExitSuccess();
        $this->assertMailCount(0);
    }

    /**
     * A job that has been going for longer than any should is reported, once a day.
     *
     * @return void
     * @link \Scheduler\Command\SchedulerCheckCommand::execute()
     */
    public function testAStuckJobIsReportedOnceADay(): void
    {
        $id = $this->jobTaken(3);

        $this->exec('scheduler check');

        $this->assertExitError();
        $this->assertMailCount(1);
        $this->assertMailSentTo('oncall@example.com');
        $this->assertMailContains('Job ' . $id . ' ');

        $this->exec('scheduler check');

        $this->assertExitError();
        $this->assertErrorContains('Job ' . $id . ' ');
        $this->assertMailCount(1);
    }

    /**
     * A job that ended, for good or bad, is not stuck.
     *
     * @return void
     * @link \Scheduler\Command\SchedulerCheckCommand::execute()
     */
    public function testAJobThatEndedIsNotStuck(): void
    {
        $this->jobs->updateAll(['completed' => DateTime::now()], ['id' => $this->jobTaken(3)]);
        $this->jobs->updateAll(['status' => QueuedJobsTable::STATUS_ABORTED], ['id' => $this->jobTaken(3)]);

        $this->exec('scheduler check');

        $this->assertExitSuccess();
        $this->assertMailCount(0);
    }

    /**
     * A row that should have run hours ago is reported.
     *
     * @return void
     * @link \Scheduler\Command\SchedulerCheckCommand::execute()
     */
    public function testARowLongOverdueIsReported(): void
    {
        $this->rowDue(4);

        $this->exec('scheduler check');

        $this->assertExitError();
        $this->assertMailCount(1);
        $this->assertMailContains('Row Example was due');
    }

    /**
     * A dispatcher that stopped says so through the time of its last pass.
     *
     * @return void
     * @link \Scheduler\Command\SchedulerCheckCommand::execute()
     */
    public function testADispatcherGoneQuietIsReported(): void
    {
        Cache::write(RunCommand::HEARTBEAT_KEY, time() - 3600);

        $this->exec('scheduler check');

        $this->assertExitError();
        $this->assertMailCount(1);
        $this->assertMailContains('The dispatcher last ran at');
    }

    /**
     * The cache is emptied by every deployment, which is no reason to raise an alarm.
     *
     * @return void
     * @link \Scheduler\Command\SchedulerCheckCommand::execute()
     */
    public function testNoHeartbeatIsNoProblem(): void
    {
        $this->exec('scheduler check');

        $this->assertExitSuccess();
        $this->assertMailCount(0);
    }
}
