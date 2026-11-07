<?php
declare(strict_types=1);

namespace Scheduler\Test\TestCase\Service;

use Cake\Command\VersionCommand;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;
use QueueScheduler\Model\Entity\SchedulerRow;
use QueueScheduler\Model\Table\SchedulerRowsTable;
use Scheduler\Service\Schedule;

/**
 * Scheduler\Service\Schedule Test Case
 *
 * The descriptions in the code reach every installation with every start, so what matters is what
 * they leave alone: an installation's own changes, and a row somebody has not decided to run.
 */
#[UsesClass(Schedule::class)]
class ScheduleTest extends TestCase
{
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
     * @var \Scheduler\Service\Schedule
     */
    private Schedule $schedule;

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
        $this->schedule = new Schedule();
    }

    /**
     * @param string $name The name of the row.
     * @return \QueueScheduler\Model\Entity\SchedulerRow
     */
    private function row(string $name): SchedulerRow
    {
        /** @var \QueueScheduler\Model\Entity\SchedulerRow */
        return $this->rows->find()->where(['name' => $name])->firstOrFail();
    }

    /**
     * A description becomes a row that runs the command as it says, and that is off.
     *
     * @return void
     * @link \Scheduler\Service\Schedule::addMissing()
     */
    public function testAddMissingWritesWhatTheDescriptionSays(): void
    {
        $added = $this->schedule->addMissing([
            'Example' => [
                'command' => VersionCommand::class,
                'args' => ['--verbose'],
                'frequency' => '55 12 * * 1-5',
                'priority' => 9,
                'window' => ['from' => '08:00', 'to' => '18:00'],
            ],
        ]);

        $row = $this->row('Example');

        $this->assertSame(['Example'], $added);
        $this->assertSame(SchedulerRow::TYPE_CAKE_COMMAND, $row->type);
        $this->assertSame(['class' => VersionCommand::class, 'args' => ['--verbose']], $row->job_data);
        $this->assertSame(['priority' => 9], $row->job_config);
        $this->assertSame('55 12 * * 1-5', $row->frequency);
        $this->assertFalse($row->allow_concurrent);
        $this->assertFalse($row->enabled);
        $this->assertTrue($row->isWithinWindow($row->next_run?->setTime(12, 0) ?? $this->fail('No next run')));
        $this->assertFalse($row->isWithinWindow($row->next_run->setTime(20, 0)));
    }

    /**
     * A description may start its row, for a job that is safe everywhere.
     *
     * @return void
     * @link \Scheduler\Service\Schedule::addMissing()
     */
    public function testAddMissingStartsARowThatSaysSo(): void
    {
        $this->schedule->addMissing([
            'Example' => ['command' => VersionCommand::class, 'frequency' => '@daily', 'enabled' => true],
        ]);

        $this->assertTrue($this->row('Example')->enabled);
    }

    /**
     * What an installation has changed survives every later start.
     *
     * @return void
     * @link \Scheduler\Service\Schedule::addMissing()
     */
    public function testAddMissingLeavesARowThatExists(): void
    {
        $description = ['Example' => ['command' => VersionCommand::class, 'frequency' => '0 1 * * *']];
        $this->schedule->addMissing($description);

        $row = $this->row('Example');
        $row->frequency = '0 2 * * *';
        $this->rows->saveOrFail($row);

        $this->assertSame([], $this->schedule->addMissing($description));
        $this->assertSame('0 2 * * *', $this->row('Example')->frequency);
    }

    /**
     * A row that is off takes what the file says, and stays off whatever the file says.
     *
     * @return void
     * @link \Scheduler\Service\Schedule::import()
     */
    public function testImportChangesARowThatIsOffAndSwitchesNothingOn(): void
    {
        $this->schedule->addMissing(['Example' => ['command' => VersionCommand::class, 'frequency' => '0 1 * * *']]);

        $result = $this->schedule->import([
            'Example' => ['command' => VersionCommand::class, 'frequency' => '0 2 * * *', 'enabled' => true],
            'Another' => ['command' => VersionCommand::class, 'frequency' => '0 3 * * *', 'enabled' => true],
        ]);

        $this->assertSame(['added' => ['Another'], 'changed' => ['Example'], 'skipped' => []], $result);
        $this->assertSame('0 2 * * *', $this->row('Example')->frequency);
        $this->assertFalse($this->row('Example')->enabled);
        $this->assertFalse($this->row('Another')->enabled);
    }

    /**
     * A row that is running is out of reach of a file written earlier.
     *
     * @return void
     * @link \Scheduler\Service\Schedule::import()
     */
    public function testImportLeavesARowThatIsOn(): void
    {
        $this->schedule->addMissing([
            'Example' => ['command' => VersionCommand::class, 'frequency' => '0 1 * * *', 'enabled' => true],
        ]);

        $result = $this->schedule->import([
            'Example' => ['command' => VersionCommand::class, 'frequency' => '0 2 * * *'],
        ]);

        $this->assertSame(['Example'], $result['skipped']);
        $this->assertSame('0 1 * * *', $this->row('Example')->frequency);
        $this->assertTrue($this->row('Example')->enabled);
    }

    /**
     * A row is switched by its name, and a name nobody knows is said to be one.
     *
     * @return void
     * @link \Scheduler\Service\Schedule::switchRow()
     */
    public function testSwitchRow(): void
    {
        $this->schedule->addMissing(['Example' => ['command' => VersionCommand::class, 'frequency' => '@daily']]);

        $this->assertTrue($this->schedule->switchRow('Example', true)?->enabled);
        $this->assertTrue($this->row('Example')->enabled);
        $this->assertFalse($this->schedule->switchRow('Example', false)?->enabled);
        $this->assertNull($this->schedule->switchRow('Nothing of that name', true));
    }
}
