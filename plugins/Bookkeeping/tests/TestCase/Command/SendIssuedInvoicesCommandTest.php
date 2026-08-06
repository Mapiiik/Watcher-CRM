<?php
declare(strict_types=1);

namespace Bookkeeping\Test\TestCase\Command;

use Bookkeeping\Command\SendIssuedInvoicesCommand;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Bookkeeping\Command\SendIssuedInvoicesCommand Test Case
 *
 * The command mails out the invoices that have not gone out yet. The fixture invoice already has,
 * so a run over it sends nothing - which is what makes the run safe to make here at all.
 */
#[UsesClass(SendIssuedInvoicesCommand::class)]
class SendIssuedInvoicesCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.AppUsers',
        'app.AccountingProfiles',
        'app.Customers',
        'app.Emails',
        'plugin.Bookkeeping.Invoices',
    ];

    /**
     * The name a cron entry calls the command by.
     *
     * @return void
     * @link \Bookkeeping\Command\SendIssuedInvoicesCommand::defaultName()
     */
    public function testDefaultName(): void
    {
        $this->assertSame('send_issued_invoices', SendIssuedInvoicesCommand::defaultName());
    }

    /**
     * The command says what it does in the list of commands, which is where an operator looks.
     *
     * @return void
     * @link \Bookkeeping\Command\SendIssuedInvoicesCommand::getDescription()
     */
    public function testGetDescription(): void
    {
        $this->assertNotEmpty(SendIssuedInvoicesCommand::getDescription());

        $this->exec('send_issued_invoices --help');

        $this->assertExitSuccess();
        $this->assertOutputContains(SendIssuedInvoicesCommand::getDescription());
    }

    /**
     * The option a cron entry names is still there. It caps how many invoices go out in one run,
     * so losing it would turn a paced send into all of them at once.
     *
     * @return void
     * @link \Bookkeeping\Command\SendIssuedInvoicesCommand::buildOptionParser()
     */
    public function testBuildOptionParser(): void
    {
        $this->exec('send_issued_invoices --help');

        $this->assertExitSuccess();
        $this->assertOutputContains('--limit');
        $this->assertOutputContains(SendIssuedInvoicesCommand::getDescription());
    }

    /**
     * An invoice that has already been mailed is not mailed again, so the run reports it is sending
     * and leaves with nothing sent.
     *
     * @return void
     * @link \Bookkeeping\Command\SendIssuedInvoicesCommand::execute()
     */
    public function testExecuteLeavesAnAlreadySentInvoice(): void
    {
        $invoices = $this->getTableLocator()->get('Bookkeeping.Invoices');
        $sentAt = $invoices->find()->firstOrFail()->get('email_sent');

        $this->exec('send_issued_invoices');

        $this->assertExitSuccess();
        $this->assertEquals($sentAt, $invoices->find()->firstOrFail()->get('email_sent'));
    }
}
