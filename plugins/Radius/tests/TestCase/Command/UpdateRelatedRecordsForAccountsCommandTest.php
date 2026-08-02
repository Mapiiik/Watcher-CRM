<?php
declare(strict_types=1);

namespace Radius\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;
use Radius\Command\UpdateRelatedRecordsForAccountsCommand;

/**
 * Radius\Command\UpdateRelatedRecordsForAccountsCommand Test Case
 *
 * The command brings the checks, replies and groups of every RADIUS account back in line with the
 * contract behind it. It runs from cron, so what its options are called is part of its contract.
 */
#[UsesClass(UpdateRelatedRecordsForAccountsCommand::class)]
class UpdateRelatedRecordsForAccountsCommandTest extends TestCase
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
        'app.Countries',
        'app.Addresses',
        'app.Commissions',
        'app.ContractStates',
        'app.ServiceTypes',
        'app.Queues',
        'app.Services',
        'app.Contracts',
        'app.Billings',
        'plugin.Radius.Accounts',
        'plugin.Radius.Radcheck',
        'plugin.Radius.Radreply',
        'plugin.Radius.Radusergroup',
        'plugin.Radius.Radacct',
        'plugin.Radius.Radpostauth',
    ];

    /**
     * Every option a cron entry might name is still there. Which records get touched is chosen by
     * option, so a renamed one silently narrows what a scheduled run does.
     *
     * @return void
     * @link \Radius\Command\UpdateRelatedRecordsForAccountsCommand::buildOptionParser()
     */
    public function testBuildOptionParser(): void
    {
        $this->exec('radius accounts update_related_records --help');

        $this->assertExitSuccess();
        $this->assertOutputContains('--state');
        $this->assertOutputContains('--radcheck');
        $this->assertOutputContains('--radreply');
        $this->assertOutputContains('--radusergroup');
        $this->assertOutputContains('--reconnect_modified_accounts');
        $this->assertOutputContains('--send_change_log_by_email');
    }

    /**
     * A run over the accounts goes through and leaves with a success.
     *
     * @return void
     * @link \Radius\Command\UpdateRelatedRecordsForAccountsCommand::execute()
     */
    public function testExecute(): void
    {
        $this->exec('radius accounts update_related_records');

        $this->assertExitSuccess();
    }
}
