<?php
declare(strict_types=1);

namespace Scheduler\Test\TestCase\Model\Behavior;

use Cake\Command\VersionCommand;
use Cake\Event\EventManager;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;
use QueueScheduler\Model\Entity\SchedulerRow;
use QueueScheduler\Model\Table\SchedulerRowsTable;
use QueueScheduler\Scheduler\Scheduler;
use Scheduler\Event\SchedulerRowsListener;
use Scheduler\Model\Behavior\RecalculateNextRunBehavior;

/**
 * Scheduler\Model\Behavior\RecalculateNextRunBehavior Test Case
 *
 * A row left off keeps the time it was last due, and whatever is due in the past runs on the next
 * tick. That is how a job that writes to the customers would write to them twice in a day.
 */
#[UsesClass(RecalculateNextRunBehavior::class)]
#[UsesClass(SchedulerRowsListener::class)]
class RecalculateNextRunBehaviorTest extends TestCase
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
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        EventManager::instance()->on(new SchedulerRowsListener());
        $this->getTableLocator()->clear();

        /** @var \QueueScheduler\Model\Table\SchedulerRowsTable $rows */
        $rows = $this->getTableLocator()->get('QueueScheduler.SchedulerRows');
        $this->rows = $rows;
        $this->rows->deleteAll([]);
    }

    /**
     * A row that was last due yesterday.
     *
     * @param bool $enabled Whether it is on.
     * @return \QueueScheduler\Model\Entity\SchedulerRow
     */
    private function rowDueYesterday(bool $enabled): SchedulerRow
    {
        /** @var \QueueScheduler\Model\Entity\SchedulerRow $row */
        $row = $this->rows->saveOrFail($this->rows->newEntity([
            'name' => 'Example',
            'type' => SchedulerRow::TYPE_CAKE_COMMAND,
            'content' => VersionCommand::class,
            'frequency' => '0 13 * * *',
            'allow_concurrent' => false,
            'enabled' => $enabled,
        ]));

        $this->rows->updateAll(['next_run' => DateTime::now()->subDays(1)], ['id' => $row->id]);

        /** @var \QueueScheduler\Model\Entity\SchedulerRow */
        return $this->rows->get($row->id);
    }

    /**
     * The row waits for its next time instead of counting as due.
     *
     * @return void
     * @link \Scheduler\Model\Behavior\RecalculateNextRunBehavior::beforeSave()
     */
    public function testARowSwitchedOnWaitsForItsNextTime(): void
    {
        $row = $this->rowDueYesterday(false);

        $row->enabled = true;
        $this->rows->saveOrFail($row);

        /** @var \QueueScheduler\Model\Entity\SchedulerRow $saved */
        $saved = $this->rows->get($row->id);

        $this->assertTrue($saved->next_run?->isFuture());
        $this->assertSame(0, (new Scheduler())->events()->count());
    }

    /**
     * Without this, the row of the test above does count as due, which is what it guards against.
     *
     * @return void
     * @link \Scheduler\Model\Behavior\RecalculateNextRunBehavior::beforeSave()
     */
    public function testARowThatStaysOnKeepsTheTimeItIsWaitingFor(): void
    {
        $row = $this->rowDueYesterday(true);

        $row->name = 'Renamed';
        $this->rows->saveOrFail($row);

        /** @var \QueueScheduler\Model\Entity\SchedulerRow $saved */
        $saved = $this->rows->get($row->id);

        $this->assertTrue($saved->next_run?->isPast());
        $this->assertSame(1, (new Scheduler())->events()->count());
    }
}
