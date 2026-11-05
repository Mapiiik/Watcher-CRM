<?php
declare(strict_types=1);

namespace Bookkeeping\Test\TestCase\Command;

use App\Model\Entity\Customer;
use Bookkeeping\Command\ProcessDebtorsCommand;
use Bookkeeping\Debtors\Debtor;
use Bookkeeping\Model\Entity\Invoice;
use Bookkeeping\Service\BookkeepingService;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Console\TestSuite\StubConsoleOutput;
use Cake\Core\Configure;
use Cake\I18n\Date;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\TestCase;
use PhpCollective\DecimalObject\Decimal;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;

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
    use EmailTrait;

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
     * A debtor with one invoice of a given number.
     *
     * @param int $nid What the customer's number is worked out from.
     * @param string $invoice The number of the invoice.
     * @return \Bookkeeping\Debtors\Debtor
     */
    private function debtor(int $nid, string $invoice): Debtor
    {
        return new Debtor([new Invoice([
            'number' => $invoice,
            'customer' => new Customer(['nid' => $nid]),
            'due_date' => new Date('2026-10-01'),
            'debt' => Decimal::create('100'),
        ])]);
    }

    /**
     * A message goes out with its invoices, so a customer whose PDF cannot be had gets none at all,
     * and whoever is on call is told who it was, which invoice it was and what the accounting
     * system said. Nobody after them is held up: the blocking has happened by then, and they would
     * be cut off without a word.
     *
     * @return void
     * @link \Bookkeeping\Command\ProcessDebtorsCommand::writingTo()
     */
    public function testACustomerWhoseInvoiceHasNoPdfIsPassedOverAndReported(): void
    {
        Configure::write('Report.errorEmails', ['operator@example.com']);

        $bookkeeping = $this->createStub(BookkeepingService::class);
        $bookkeeping->method('getInvoicePdfPath')->willThrowException(
            new RuntimeException('Eurofaktura API error: Error nr: 7 #documentNotFound'),
        );

        $command = new ProcessDebtorsCommand();
        (new ReflectionProperty(ProcessDebtorsCommand::class, 'bookkeeping'))->setValue($command, $bookkeeping);

        $attachments = new ReflectionMethod(ProcessDebtorsCommand::class, 'getAttachments');
        $writingTo = new ReflectionMethod(ProcessDebtorsCommand::class, 'writingTo');
        $io = new ConsoleIo(new StubConsoleOutput(), new StubConsoleOutput());
        $args = new Arguments([], [], []);

        $missing = $this->debtor(7001, 'FA-1');
        $reached = false;

        $writingTo->invoke($command, $missing, fn(): mixed => $attachments->invoke($command, $missing), $args, $io);
        $writingTo->invoke($command, $this->debtor(7002, 'FA-2'), function () use (&$reached): void {
            $reached = true;
        }, $args, $io);

        (new ReflectionMethod(ProcessDebtorsCommand::class, 'reportWhoWasPassedOver'))->invoke($command, $io);

        Configure::delete('Report.errorEmails');

        $this->assertTrue($reached, 'The customer after them was held up.');
        $this->assertMailSentTo('operator@example.com');
        $this->assertMailContains($missing->getCustomer()->number);
        $this->assertMailContains('FA-1');
        $this->assertMailContains('Eurofaktura API error: Error nr: 7 #documentNotFound');
    }

    /**
     * Nobody passed over, nothing to report.
     *
     * @return void
     * @link \Bookkeeping\Command\ProcessDebtorsCommand::reportWhoWasPassedOver()
     */
    public function testNobodyPassedOverReportsNothing(): void
    {
        Configure::write('Report.errorEmails', ['operator@example.com']);

        (new ReflectionMethod(ProcessDebtorsCommand::class, 'reportWhoWasPassedOver'))->invoke(
            new ProcessDebtorsCommand(),
            new ConsoleIo(new StubConsoleOutput(), new StubConsoleOutput()),
        );

        Configure::delete('Report.errorEmails');

        $this->assertNoMailSent();
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
