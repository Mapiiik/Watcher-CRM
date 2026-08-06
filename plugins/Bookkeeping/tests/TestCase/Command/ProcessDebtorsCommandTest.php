<?php
declare(strict_types=1);

namespace Bookkeeping\Test\TestCase\Command;

use Bookkeeping\Command\ProcessDebtorsCommand;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Bookkeeping\Command\ProcessDebtorsCommand Test Case
 *
 * A run reads who is overdue from the accounting system and can go on to block them, which a test
 * is no place to reach or to have happen. What is covered here is what can be answered without it:
 * the name, the description and the options a cron entry names the command by. They are what tells
 * a run to only notify from one that blocks, so a renamed one turns a warning into a disconnection.
 */
#[UsesClass(ProcessDebtorsCommand::class)]
class ProcessDebtorsCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * The name a cron entry calls the command by.
     *
     * @return void
     * @link \Bookkeeping\Command\ProcessDebtorsCommand::defaultName()
     */
    public function testDefaultName(): void
    {
        $this->assertSame('process_debtors', ProcessDebtorsCommand::defaultName());
    }

    /**
     * The command says what it does in the list of commands, which is where an operator looks.
     *
     * @return void
     * @link \Bookkeeping\Command\ProcessDebtorsCommand::getDescription()
     */
    public function testGetDescription(): void
    {
        $this->assertNotEmpty(ProcessDebtorsCommand::getDescription());

        $this->exec('process_debtors --help');

        $this->assertExitSuccess();
        $this->assertOutputContains(ProcessDebtorsCommand::getDescription());
    }

    /**
     * Every option is still there.
     *
     * @return void
     * @link \Bookkeeping\Command\ProcessDebtorsCommand::buildOptionParser()
     */
    public function testBuildOptionParser(): void
    {
        $this->exec('process_debtors --help');

        $this->assertExitSuccess();
        $this->assertOutputContains('--only_notify');
        $this->assertOutputContains('--only_block');
        $this->assertOutputContains('--blocking_update');
    }
}
