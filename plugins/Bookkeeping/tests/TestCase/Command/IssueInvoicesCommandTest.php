<?php
declare(strict_types=1);

namespace Bookkeeping\Test\TestCase\Command;

use Bookkeeping\Command\IssueInvoicesCommand;
use Bookkeeping\Model\Enum\InvoicingSchedule;
use Cake\Chronos\Chronos;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;
use Settings\Utility\Settings;

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
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'plugin.Settings.Settings',
    ];

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
     * On a day it is not due, the run issues nothing.
     *
     * The schedule is what keeps a run that fires every night from invoicing every night, and it is
     * checked before anything is issued - so this is the one thing about running the command that
     * can be asked without invoices being raised, handed to the accounting system and mailed out.
     *
     * @return void
     * @link \Bookkeeping\Command\IssueInvoicesCommand::execute()
     */
    public function testExecuteIssuesNothingOnADayItIsNotDue(): void
    {
        $nowBefore = Chronos::getTestNow();
        // the middle of a month is neither the first day nor the last
        Chronos::setTestNow(new Chronos('2026-08-14 03:00:00'));

        try {
            $this->exec('issue_invoices');

            $this->assertExitSuccess();
            $this->assertOutputContains('skipping');
        } finally {
            Chronos::setTestNow($nowBefore);
        }
    }

    /**
     * A cron entry that names the command and nothing else has to invoice the way the rest of the
     * application believes it does, so the schedule it falls back on is the one the installation
     * has settled on - not one built into the command.
     *
     * Asked on a day the stored schedule does not fire on, so the run skips and nothing is issued,
     * handed to the accounting system or mailed out.
     *
     * @return void
     * @link \Bookkeeping\Command\IssueInvoicesCommand::buildOptionParser()
     */
    public function testTheScheduleFallsBackToWhatTheInstallationSays(): void
    {
        Settings::set(InvoicingSchedule::SETTINGS_PATH, InvoicingSchedule::PREV_MONTH_ON_FIRST->value);

        $nowBefore = Chronos::getTestNow();
        // the last day of a month: due under the other schedule, and not under this one
        Chronos::setTestNow(new Chronos('2026-08-31 03:00:00'));

        try {
            $this->exec('issue_invoices');

            $this->assertExitSuccess();
            $this->assertOutputContains('Not the first day of month');
        } finally {
            Chronos::setTestNow($nowBefore);
        }
    }

    /**
     * And the other way round, so that it is the setting being read rather than a default that
     * happens to agree with it.
     *
     * @return void
     * @link \Bookkeeping\Command\IssueInvoicesCommand::buildOptionParser()
     */
    public function testTheOtherScheduleIsFallenBackOnJustTheSame(): void
    {
        Settings::set(InvoicingSchedule::SETTINGS_PATH, InvoicingSchedule::CURRENT_MONTH_ON_LAST->value);

        $nowBefore = Chronos::getTestNow();
        // the first day of a month: due under the other schedule, and not under this one
        Chronos::setTestNow(new Chronos('2026-09-01 03:00:00'));

        try {
            $this->exec('issue_invoices');

            $this->assertExitSuccess();
            $this->assertOutputContains('Not the last day of month');
        } finally {
            Chronos::setTestNow($nowBefore);
        }
    }

    /**
     * The answers offered are the ways of invoicing there are.
     *
     * @return void
     * @link \Bookkeeping\Command\IssueInvoicesCommand::buildOptionParser()
     */
    public function testTheScheduleOffersTheWaysOfInvoicingThereAre(): void
    {
        $this->exec('issue_invoices --help');

        $this->assertExitSuccess();

        foreach (InvoicingSchedule::cases() as $schedule) {
            $this->assertOutputContains($schedule->value);
        }
    }
}
