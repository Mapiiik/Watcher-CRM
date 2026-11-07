<?php
declare(strict_types=1);

namespace Bookkeeping\Test\TestCase\Command;

use App\Test\Traits\ConfigureTestTrait;
use Bookkeeping\Command\SendIssuedInvoicesCommand;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Datasource\EntityInterface;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\TestCase;
use Cake\Utility\Filesystem;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;
use Settings\Utility\Settings;

/**
 * Bookkeeping\Command\SendIssuedInvoicesCommand Test Case
 *
 * The command mails out the invoices that have not gone out yet. The fixture invoice already has,
 * so a test about the sending takes that back first.
 */
#[UsesClass(SendIssuedInvoicesCommand::class)]
class SendIssuedInvoicesCommandTest extends TestCase
{
    use ConfigureTestTrait;
    use ConsoleIntegrationTestTrait;
    use EmailTrait;

    /**
     * The number of the invoice being sent. The file of its paper is named after it.
     */
    private const NUMBER = 'F2026001';

    /**
     * Where the customer of the fixture invoice gets their bills.
     */
    private const CUSTOMER_EMAIL = 'customer@example.com';

    /**
     * Where the papers of this test lie.
     */
    private string $dataRoot;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
        'app.AccountingProfiles',
        'app.Customers',
        'app.Emails',
        'plugin.Bookkeeping.Invoices',
        'plugin.Settings.Settings',
    ];

    /**
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->dataRoot = TMP . 'issued-invoices-' . uniqid();
        mkdir($this->dataRoot . DS . 'invoices', 0777, true);
        $this->withConfigure(['Data.root' => $this->dataRoot, 'Report.errorEmails' => []]);
        Settings::set('bookkeeping.accounting.default_provider', 'pohoda');
    }

    /**
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        $this->restoreConfigure();
        (new Filesystem())->deleteDir($this->dataRoot);

        parent::tearDown();
    }

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
        $this->assertNoMailSent();
    }

    /**
     * An invoice goes out with its paper, says so, and the next run leaves it alone. A customer
     * would otherwise get the same bill every time cron comes round.
     *
     * @return void
     * @link \Bookkeeping\Command\SendIssuedInvoicesCommand::execute()
     */
    public function testExecuteSendsAnInvoiceOnce(): void
    {
        $this->anInvoiceToSend();
        $this->itsPaper();

        $this->exec('send_issued_invoices');

        $this->assertExitSuccess();
        $this->assertMailCount(1);
        $this->assertMailSentTo(self::CUSTOMER_EMAIL);
        $this->assertMailContainsAttachment('Faktura_' . self::NUMBER . '.pdf');
        $this->assertNotNull($this->theInvoice()->get('email_sent'));

        $this->exec('send_issued_invoices');

        $this->assertExitSuccess();
        $this->assertMailCount(1);
    }

    /**
     * Without its paper an invoice does not go out, and stays to be sent. Reading it as sent would
     * leave the customer with no bill and nothing to say so.
     *
     * @return void
     * @link \Bookkeeping\Command\SendIssuedInvoicesCommand::execute()
     */
    public function testAnInvoiceThatCouldNotGoOutIsTriedAgain(): void
    {
        $this->anInvoiceToSend();

        $this->exec('send_issued_invoices');

        $this->assertErrorContains('Error sending email message with issued invoice ID');
        $this->assertNoMailSent();
        $this->assertNull($this->theInvoice()->get('email_sent'));
        $this->assertTrue($this->theInvoice()->get('send_by_email'));

        $this->itsPaper();
        $this->exec('send_issued_invoices');

        $this->assertMailCount(1);
        $this->assertNotNull($this->theInvoice()->get('email_sent'));
    }

    /**
     * A customer with nowhere to send it to is taken off the list, so the invoice does not come up
     * in every run from now on.
     *
     * @return void
     * @link \Bookkeeping\Command\SendIssuedInvoicesCommand::execute()
     */
    public function testAnInvoiceWithNowhereToGoIsNotTriedAgain(): void
    {
        $this->anInvoiceToSend();
        $this->itsPaper();
        $this->getTableLocator()->get('Emails')->updateAll(['use_for_billing' => false], ['1 = 1']);

        $this->exec('send_issued_invoices');

        $this->assertExitSuccess();
        $this->assertNoMailSent();
        $this->assertFalse($this->theInvoice()->get('send_by_email'));
        $this->assertNull($this->theInvoice()->get('email_sent'));
    }

    /**
     * Takes back that the fixture invoice has gone out.
     *
     * @return void
     */
    private function anInvoiceToSend(): void
    {
        $this->getTableLocator()->get('Bookkeeping.Invoices')
            ->updateAll(['email_sent' => null, 'number' => self::NUMBER], ['1 = 1']);
    }

    /**
     * Puts the paper of the invoice where the accounting system leaves it.
     *
     * @return void
     */
    private function itsPaper(): void
    {
        file_put_contents(
            $this->dataRoot . DS . 'invoices' . DS . 'Faktura_' . self::NUMBER . '.pdf',
            'a paper',
        );
    }

    /**
     * The invoice as it is stored now.
     *
     * @return \Cake\Datasource\EntityInterface
     */
    private function theInvoice(): EntityInterface
    {
        return $this->getTableLocator()->get('Bookkeeping.Invoices')->find()->firstOrFail();
    }
}
