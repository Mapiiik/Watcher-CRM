<?php
declare(strict_types=1);

namespace Bookkeeping\Test\TestCase\Command;

use App\Model\Entity\Customer;
use Bookkeeping\Command\ProcessDebtorsCommand;
use Bookkeeping\Debtors\Debtor;
use Bookkeeping\Model\Entity\Invoice;
use Bookkeeping\Service\BookkeepingService;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\I18n\Date;
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
     * An invoice whose PDF cannot be had costs its own message that one attachment and nothing
     * more. Thrown any further it would end the run, and the blocking has already happened by then:
     * everybody after this customer would be cut off without a word, the same way every night.
     *
     * @return void
     * @link \Bookkeeping\Command\ProcessDebtorsCommand::getAttachments()
     */
    public function testAnInvoiceWithoutItsPdfDoesNotStopTheOthers(): void
    {
        $customer = new Customer(['number' => '2024001']);
        $due = new Date('2026-10-01');
        $owed = Decimal::create('100');
        $debtor = new Debtor([
            new Invoice(['number' => '1', 'customer' => $customer, 'due_date' => $due, 'debt' => $owed]),
            new Invoice(['number' => '2', 'customer' => $customer, 'due_date' => $due, 'debt' => $owed]),
            new Invoice(['number' => '3', 'customer' => $customer, 'due_date' => $due, 'debt' => $owed]),
        ]);

        $there = TMP . 'Faktura_2.pdf';
        file_put_contents($there, '%PDF');

        $bookkeeping = $this->createStub(BookkeepingService::class);
        $bookkeeping->method('getInvoicePdfPath')->willReturnCallback(
            fn(Invoice $invoice): string => match ($invoice->number) {
                '1' => throw new RuntimeException('Not there.'),
                '2' => $there,
                default => TMP . 'no-such-invoice.pdf',
            },
        );

        $command = new ProcessDebtorsCommand();
        (new ReflectionProperty(ProcessDebtorsCommand::class, 'bookkeeping'))->setValue($command, $bookkeeping);

        $attachments = (new ReflectionMethod(ProcessDebtorsCommand::class, 'getAttachments'))
            ->invoke($command, $debtor);

        unlink($there);

        $this->assertSame(['Faktura_2.pdf'], array_keys($attachments));
        $this->assertSame(
            ['1', '3'],
            (new ReflectionProperty(ProcessDebtorsCommand::class, 'invoicesWithoutPdf'))->getValue($command),
        );
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
