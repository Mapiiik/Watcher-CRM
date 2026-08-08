<?php
declare(strict_types=1);

namespace Bookkeeping\Test\TestCase\Command;

use App\Test\Traits\ConfigureTestTrait;
use Bookkeeping\Command\LoadInvoicesCommand;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\TestCase;
use Override;
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
    use ConfigureTestTrait;
    use ConsoleIntegrationTestTrait;
    use EmailTrait;

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        // A run that cannot start reports it by mail, so a test of that path needs somebody to
        // report it to - said here rather than left to whatever the environment was built with.
        $this->withConfigure(['Report.emails' => ['nobody@example.com']]);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        $this->restoreConfigure();

        parent::tearDown();
    }

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
     * A run is told how far back to sync from, and one it cannot read that from does not start.
     *
     * Going ahead with a date it could not make out would mean asking the accounting system for a
     * span nobody chose - either a sliver, and invoices quietly missed, or everything there has
     * ever been. The reach for the accounting system itself is what a test is no place for, and
     * what the loader makes of what comes back belongs to a test of the loader.
     *
     * @return void
     * @link \Bookkeeping\Command\LoadInvoicesCommand::execute()
     */
    public function testExecuteWillNotStartFromADateItCannotRead(): void
    {
        $this->exec('load_invoices --last_changes yesterday-ish');

        $this->assertExitError();
        $this->assertMailCount(1);
    }
}
