<?php
declare(strict_types=1);

namespace App\Test\TestCase\Command;

use App\Command\AutoAssignContractsToAccessPointsCommand;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Command\AutoAssignContractsToAccessPointsCommand Test Case
 *
 * The command reads where a contract's RADIUS sessions come in from and asks the NMS which access
 * point that is. The sessions are deliberately left out of the fixtures: with one, the command
 * reaches the NMS over the network, and a test is no place to find out whether it answers.
 */
#[UsesClass(AutoAssignContractsToAccessPointsCommand::class)]
class AutoAssignContractsToAccessPointsCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * Contract from the Contracts fixture, which already names an access point.
     *
     * @var string
     */
    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

    /**
     * Access point the fixture contract already names.
     *
     * @var string
     */
    private const ACCESS_POINT_ID = 'feedb343-cea8-423f-a409-de4331354217';

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
        'app.Contracts',
        'app.IpAddresses',
        'plugin.Radius.Accounts',
    ];

    /**
     * The options a cron entry would name are there, under both their long and short forms.
     *
     * @return void
     * @link \App\Command\AutoAssignContractsToAccessPointsCommand::buildOptionParser()
     */
    public function testBuildOptionParser(): void
    {
        $this->exec('auto_assign_contracts_to_access_points --help');

        $this->assertExitSuccess();
        $this->assertOutputContains('--overwrite');
        $this->assertOutputContains('--dry-run');
        $this->assertOutputContains('-o');
        $this->assertOutputContains('-d');
    }

    /**
     * By default only contracts without an access point are considered, so a run over contracts
     * that all have one does nothing.
     *
     * @return void
     * @link \App\Command\AutoAssignContractsToAccessPointsCommand::execute()
     */
    public function testExecuteLeavesContractsThatAlreadyHaveAnAccessPoint(): void
    {
        $this->exec('auto_assign_contracts_to_access_points');

        $this->assertExitSuccess();
        $this->assertSame(
            self::ACCESS_POINT_ID,
            $this->getTableLocator()->get('Contracts')->get(self::CONTRACT_ID)->get('access_point_id'),
        );
    }

    /**
     * With `--overwrite` the contract is considered again, but a RADIUS account that has never
     * opened a session says nothing about where it connects from, so it is reported and skipped
     * rather than guessed at.
     *
     * @return void
     * @link \App\Command\AutoAssignContractsToAccessPointsCommand::execute()
     */
    public function testExecuteReportsAnAccountWithoutASession(): void
    {
        $this->exec('auto_assign_contracts_to_access_points --overwrite --verbose');

        $this->assertExitSuccess();
        $this->assertOutputContains('No NAS IP for RADIUS account');
        $this->assertSame(
            self::ACCESS_POINT_ID,
            $this->getTableLocator()->get('Contracts')->get(self::CONTRACT_ID)->get('access_point_id'),
        );
    }
}
