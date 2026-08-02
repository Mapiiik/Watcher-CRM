<?php
declare(strict_types=1);

namespace Bookkeeping\Test\TestCase\Command;

use Bookkeeping\Command\LoadInvoicesCommand;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Bookkeeping\Command\LoadInvoicesCommand Test Case
 *
 * Running the command reaches the accounting system, so what is covered here is the part that can
 * be answered without it: the description and the options a cron entry names it by.
 */
#[UsesClass(LoadInvoicesCommand::class)]
class LoadInvoicesCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * The command says what it does in the list of commands, which is where an operator looks.
     *
     * @return void
     * @link \Bookkeeping\Command\LoadInvoicesCommand::getDescription()
     */
    public function testGetDescription(): void
    {
        $this->assertNotEmpty(LoadInvoicesCommand::getDescription());

        $this->exec('load_invoices --help');

        $this->assertExitSuccess();
        $this->assertOutputContains(LoadInvoicesCommand::getDescription());
    }

    /**
     * Both options are still there. Which invoices are fetched is chosen by option, so a renamed
     * one silently changes what a scheduled run brings back.
     *
     * @return void
     * @link \Bookkeeping\Command\LoadInvoicesCommand::buildOptionParser()
     */
    public function testBuildOptionParser(): void
    {
        $this->exec('load_invoices --help');

        $this->assertExitSuccess();
        $this->assertOutputContains('--last_changes');
        $this->assertOutputContains('--mode');
    }

    /**
     * Not covered: the command talks to the accounting system, and a test is no place to find out
     * whether it answers. What it does with what comes back belongs to a test of the loader.
     *
     * @return void
     * @link \Bookkeeping\Command\LoadInvoicesCommand::execute()
     */
    public function testExecute(): void
    {
        $this->markTestSkipped('Running the command reaches the accounting system.');
    }
}
