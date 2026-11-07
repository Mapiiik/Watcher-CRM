<?php
declare(strict_types=1);

namespace Scheduler\Test\TestCase\Command;

use App\Test\Traits\ConfigureTestTrait;
use Cake\Command\VersionCommand;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;
use QueueScheduler\Model\Entity\SchedulerRow;
use QueueScheduler\Model\Table\SchedulerRowsTable;
use Scheduler\Command\SchedulerDisableCommand;
use Scheduler\Command\SchedulerEnableCommand;
use Scheduler\Command\SchedulerImportCommand;
use Scheduler\Command\SchedulerSyncCommand;
use Scheduler\Service\Schedule;

/**
 * The commands that write the schedule. What they write is {@see \Scheduler\Service\Schedule}'s,
 * so these are about what somebody at the console is told and what comes back to a script.
 */
#[UsesClass(SchedulerDisableCommand::class)]
#[UsesClass(SchedulerEnableCommand::class)]
#[UsesClass(SchedulerImportCommand::class)]
#[UsesClass(SchedulerSyncCommand::class)]
#[UsesClass(Schedule::class)]
class SchedulerCommandsTest extends TestCase
{
    use ConfigureTestTrait;
    use ConsoleIntegrationTestTrait;

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
     * The file a test imports.
     *
     * @var string
     */
    private string $file;

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

        $this->file = TMP . 'scheduler-import-' . uniqid() . '.json';
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        if (is_file($this->file)) {
            unlink($this->file);
        }

        $this->restoreConfigure();

        parent::tearDown();
    }

    /**
     * @return \QueueScheduler\Model\Entity\SchedulerRow
     */
    private function example(): SchedulerRow
    {
        /** @var \QueueScheduler\Model\Entity\SchedulerRow */
        return $this->rows->find()->where(['name' => 'Example'])->firstOrFail();
    }

    /**
     * Every start of the application adds what the release brings, and says nothing otherwise.
     *
     * @return void
     * @link \Scheduler\Command\SchedulerSyncCommand::execute()
     */
    public function testSyncAddsTheDefaultsOnce(): void
    {
        $this->exec('scheduler sync');
        $this->assertExitSuccess();
        $count = $this->rows->find()->count();

        $this->assertSame(count(Schedule::defaults()), $count);
        $this->assertGreaterThan(0, $count);

        $this->exec('scheduler sync');
        $this->assertExitSuccess();
        $this->assertOutputEmpty();
        $this->assertSame($count, $this->rows->find()->count());
    }

    /**
     * A file writes its rows and says what it did with each.
     *
     * @return void
     * @link \Scheduler\Command\SchedulerImportCommand::execute()
     */
    public function testImportWritesTheRowsOfAFile(): void
    {
        file_put_contents($this->file, json_encode([
            'Example' => ['command' => VersionCommand::class, 'frequency' => '0 1 * * *'],
        ], JSON_THROW_ON_ERROR));

        $this->exec('scheduler import ' . $this->file);

        $this->assertExitSuccess();
        $this->assertOutputContains('Added to the schedule: Example');
        $this->assertFalse($this->example()->enabled);
    }

    /**
     * A row the file may not touch is mentioned, not silently passed over.
     *
     * @return void
     * @link \Scheduler\Command\SchedulerImportCommand::execute()
     */
    public function testImportSaysWhichRowsItLeftAlone(): void
    {
        (new Schedule())->addMissing([
            'Example' => ['command' => VersionCommand::class, 'frequency' => '0 1 * * *', 'enabled' => true],
        ]);
        file_put_contents($this->file, json_encode([
            'Example' => ['command' => VersionCommand::class, 'frequency' => '0 2 * * *'],
        ], JSON_THROW_ON_ERROR));

        $this->exec('scheduler import ' . $this->file);

        $this->assertExitSuccess();
        $this->assertErrorContains('Left alone, it is switched on: Example');
    }

    /**
     * A file that is not there, or not JSON, ends in an error a script can see.
     *
     * @return void
     * @link \Scheduler\Command\SchedulerImportCommand::execute()
     */
    public function testImportRefusesWhatItCannotRead(): void
    {
        $this->exec('scheduler import ' . $this->file);
        $this->assertExitError();
        $this->assertErrorContains('cannot be read');

        file_put_contents($this->file, 'not json');

        $this->exec('scheduler import ' . $this->file);
        $this->assertExitError();
        $this->assertErrorContains('is not JSON');
    }

    /**
     * Rows go on and off by name, several at a time, and on says when it runs.
     *
     * @return void
     * @link \Scheduler\Command\SchedulerEnableCommand::execute()
     */
    public function testEnableAndDisable(): void
    {
        (new Schedule())->addMissing([
            'Example' => ['command' => VersionCommand::class, 'frequency' => '0 1 * * *'],
            'Another one' => ['command' => VersionCommand::class, 'frequency' => '0 2 * * *'],
        ]);

        $this->exec('scheduler enable Example "Another one"');

        $this->assertExitSuccess();
        $this->assertOutputContains('Example: on, next run at');
        $this->assertOutputContains('Another one: on, next run at');
        $this->assertTrue($this->example()->enabled);

        $this->exec('scheduler disable Example');

        $this->assertExitSuccess();
        $this->assertOutputContains('Example: off');
        $this->assertFalse($this->example()->enabled);
    }

    /**
     * A name that matches nothing fails, so a script that switches a round of rows finds out.
     *
     * @return void
     * @link \Scheduler\Command\SchedulerEnableCommand::execute()
     */
    public function testEnableFailsOnANameItDoesNotKnow(): void
    {
        (new Schedule())->addMissing(['Example' => ['command' => VersionCommand::class, 'frequency' => '0 1 * * *']]);

        $this->exec('scheduler enable "Nothing of that name" Example');

        $this->assertExitError();
        $this->assertErrorContains('There is no row of that name: Nothing of that name');
        $this->assertTrue($this->example()->enabled);
    }
}
