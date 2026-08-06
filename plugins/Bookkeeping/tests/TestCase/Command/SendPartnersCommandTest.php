<?php
declare(strict_types=1);

namespace Bookkeeping\Test\TestCase\Command;

use Bookkeeping\Command\SendPartnersCommand;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Bookkeeping\Command\SendPartnersCommand Test Case
 *
 * A run hands the customers to the accounting system, which a test is no place to reach. What can
 * be answered without it is the name and the options a cron entry names the command by, and the one
 * branch that returns before any of it: a run with nobody to send.
 */
#[UsesClass(SendPartnersCommand::class)]
class SendPartnersCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * Fixtures
     *
     * The customers are deliberately left out: with one, the run reaches the accounting system.
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.AppUsers',
    ];

    /**
     * The name a cron entry calls the command by.
     *
     * @return void
     * @link \Bookkeeping\Command\SendPartnersCommand::defaultName()
     */
    public function testDefaultName(): void
    {
        $this->assertSame('send_partners', SendPartnersCommand::defaultName());
    }

    /**
     * The command says what it does in the list of commands, which is where an operator looks.
     *
     * @return void
     * @link \Bookkeeping\Command\SendPartnersCommand::getDescription()
     */
    public function testGetDescription(): void
    {
        $this->assertNotEmpty(SendPartnersCommand::getDescription());

        $this->exec('send_partners --help');

        $this->assertExitSuccess();
        $this->assertOutputContains(SendPartnersCommand::getDescription());
    }

    /**
     * Every option is still there. They are what narrows a run down to one customer or one band of
     * customer numbers, so a renamed one widens a scheduled run into sending more than it was meant
     * to.
     *
     * @return void
     * @link \Bookkeeping\Command\SendPartnersCommand::buildOptionParser()
     */
    public function testBuildOptionParser(): void
    {
        $this->exec('send_partners --help');

        $this->assertExitSuccess();
        $this->assertOutputContains('--customer-id');
        $this->assertOutputContains('--min-customer-number');
        $this->assertOutputContains('--max-customer-number');
    }

    /**
     * Nobody to send is a quiet run rather than a failed one, and one that reaches for nothing.
     *
     * @return void
     * @link \Bookkeeping\Command\SendPartnersCommand::execute()
     */
    public function testExecuteWithNobodyToSend(): void
    {
        $this->exec('send_partners');

        $this->assertExitSuccess();
    }
}
