<?php
declare(strict_types=1);

namespace Scheduler\Test\TestCase;

use Cake\Console\BaseCommand;
use Cake\Console\ConsoleIo;
use Cake\Console\TestSuite\StubConsoleOutput;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Cron\CronExpression;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;
use Scheduler\Service\Schedule;

/**
 * The schedule the application describes.
 *
 * A mistake in it shows on the night it first runs, as a job that fails or never does anything.
 * Running the commands here is not an option, they write to customers and to other systems, so
 * this reads them the way the worker will, up to the point of running.
 */
#[UsesClass(Schedule::class)]
class DefaultScheduleTest extends TestCase
{
    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Configure::load('scheduler');
    }

    /**
     * Every row names a command, with options it has and a time that can come.
     *
     * @return void
     */
    public function testEveryRowCanRun(): void
    {
        $this->assertNotSame([], Schedule::defaults());

        $io = new ConsoleIo(new StubConsoleOutput(), new StubConsoleOutput());

        foreach (Schedule::defaults() as $name => $description) {
            $command = new $description['command']();

            $this->assertInstanceOf(BaseCommand::class, $command, $name);

            // what CommandExecuteTask does before it runs one
            if (!str_contains($command->getName(), ' ')) {
                $command->setName('cake ' . $command::defaultName());
            }

            $command->getOptionParser()->parse($description['args'] ?? [], $io);

            $this->assertTrue(CronExpression::isValidExpression($description['frequency']), $name);
        }
    }

    /**
     * No row starts by itself: the jobs move over from the crontabs of the installations one by
     * one, and a row that started with the release would run twice beside its line.
     *
     * @return void
     */
    public function testNoRowStartsByItselfWhileTheCrontabsAreMovedOver(): void
    {
        foreach (Schedule::defaults() as $name => $description) {
            $this->assertFalse($description['enabled'] ?? false, $name);
        }
    }
}
