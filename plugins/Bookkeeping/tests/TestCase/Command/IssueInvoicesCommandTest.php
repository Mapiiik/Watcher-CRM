<?php
declare(strict_types=1);

namespace Bookkeeping\Test\TestCase\Command;

use Bookkeeping\Command\IssueInvoicesCommand;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Bookkeeping\Command\IssueInvoicesCommand Test Case
 *
 * Replaces the test case left behind for `GenerateInvoicesCommand`, which the command was renamed
 * away from - the stubs there named methods of a class that no longer exists.
 *
 * Running the command issues real invoices, hands them to the accounting system and mails them out,
 * so what is covered here is the part that can be answered without any of that: the name, the
 * description and the options a cron entry names it by.
 */
#[UsesClass(IssueInvoicesCommand::class)]
class IssueInvoicesCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * The name a cron entry calls the command by.
     *
     * @return void
     * @link \Bookkeeping\Command\IssueInvoicesCommand::defaultName()
     */
    public function testDefaultName(): void
    {
        $this->assertSame('issue_invoices', IssueInvoicesCommand::defaultName());
    }

    /**
     * The command says what it does in the list of commands, which is where an operator looks.
     *
     * @return void
     * @link \Bookkeeping\Command\IssueInvoicesCommand::getDescription()
     */
    public function testGetDescription(): void
    {
        $this->assertNotEmpty(IssueInvoicesCommand::getDescription());

        $this->exec('issue_invoices --help');

        $this->assertExitSuccess();
        $this->assertOutputContains(IssueInvoicesCommand::getDescription());
    }

    /**
     * Every option is still there. They are what narrows a run down to one month, one accounting
     * profile or one band of customer numbers, so a renamed one widens a scheduled run into
     * invoicing more than it was meant to.
     *
     * @return void
     * @link \Bookkeeping\Command\IssueInvoicesCommand::buildOptionParser()
     */
    public function testBuildOptionParser(): void
    {
        $this->exec('issue_invoices --help');

        $this->assertExitSuccess();
        $this->assertOutputContains('--month');
        $this->assertOutputContains('--schedule');
        $this->assertOutputContains('--force');
        $this->assertOutputContains('--accounting-profile-id');
        $this->assertOutputContains('--min-customer-number');
        $this->assertOutputContains('--max-customer-number');
    }

    /**
     * Not covered: a run issues invoices for real, hands them to the accounting system and mails
     * them. What it decides to invoice belongs to a test of the generator rather than of the
     * command that schedules it.
     *
     * @return void
     * @link \Bookkeeping\Command\IssueInvoicesCommand::execute()
     */
    public function testExecute(): void
    {
        $this->markTestSkipped('Running the command issues invoices, and reaches the accounting system and mail.');
    }
}
