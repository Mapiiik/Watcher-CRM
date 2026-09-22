<?php
declare(strict_types=1);

namespace App\Test\TestCase\Command;

use App\Command\AvailableConnectionsSyncCommand;
use App\Model\Entity\AvailableConnection;
use App\Model\Enum\AccessTechnology;
use App\Model\Enum\AvailableConnectionOrigin;
use Cake\Cache\Cache;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Core\Configure;
use Cake\I18n\Date;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Command\AvailableConnectionsSyncCommand Test Case
 *
 * The fixtures have one contract at an address the registry knows, on a wireless profile.
 */
#[UsesClass(AvailableConnectionsSyncCommand::class)]
class AvailableConnectionsSyncCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

    /**
     * @var list<string>
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
        'app.ConnectionProfiles',
        'app.Services',
        'app.Contracts',
        'app.Billings',
        'app.AvailableConnections',
    ];

    /**
     * The registry is not asked: the address as it stands on the contract has to do.
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        Configure::write('Addresses.url', '');
        Cache::clear('addresses_api');
    }

    /**
     * The contract's point is recorded once, and a second run finds nothing to do.
     *
     * @return void
     * @link \App\Command\AvailableConnectionsSyncCommand::execute()
     */
    public function testAContractsPointIsRecordedOnce(): void
    {
        $this->exec('available_connections sync');
        $this->assertExitSuccess();
        $this->assertOutputContains('1 new, 0 updated.');

        $record = $this->recordOfTheContract();
        $this->assertSame(AvailableConnectionOrigin::Contract, $record->origin);
        $this->assertSame(AccessTechnology::FwaUnlicensed, $record->access_technology);
        $this->assertSame(self::CONTRACT_ID, $record->contract_id);

        $this->exec('available_connections sync');
        $this->assertOutputContains('0 new, 0 updated.');
    }

    /**
     * A dry run says what it would do and does nothing.
     *
     * @return void
     * @link \App\Command\AvailableConnectionsSyncCommand::execute()
     */
    public function testADryRunWritesNothing(): void
    {
        $this->exec('available_connections sync --dry-run');

        $this->assertOutputContains('1 new, 0 updated.');
        $this->assertSame(1, $this->getTableLocator()->get('AvailableConnections')->find()->count());
    }

    /**
     * What the synchronisation wrote it keeps raising, but never lowers.
     *
     * @return void
     * @link \App\Command\AvailableConnectionsSyncCommand::execute()
     */
    public function testSpeedsAreRaisedNeverLowered(): void
    {
        $this->exec('available_connections sync');
        $table = $this->getTableLocator()->get('AvailableConnections');

        $record = $this->recordOfTheContract();
        $record->speed_down_max = 500;
        $record->speed_up_max = 0;
        $table->saveOrFail($record, ['checkRules' => false, 'validate' => false]);

        $this->exec('available_connections sync');
        $this->assertOutputContains('0 new, 1 updated.');

        $record = $this->recordOfTheContract();
        $this->assertSame(500, $record->speed_down_max);
        $this->assertSame(1, $record->speed_up_max);
    }

    /**
     * A record the operator took over, or retired, is theirs.
     *
     * @return void
     * @link \App\Command\AvailableConnectionsSyncCommand::execute()
     */
    public function testWhatTheOperatorTookOverIsLeftAlone(): void
    {
        $this->exec('available_connections sync');
        $table = $this->getTableLocator()->get('AvailableConnections');

        $record = $this->recordOfTheContract();
        $record->origin = AvailableConnectionOrigin::Manual;
        $record->speed_up_max = 0;
        $table->saveOrFail($record, ['checkRules' => false, 'validate' => false]);

        $this->exec('available_connections sync');
        $this->assertSame(0, $this->recordOfTheContract()->speed_up_max);

        $record = $this->recordOfTheContract();
        $record->origin = AvailableConnectionOrigin::Contract;
        $record->retired = new Date('2026-01-01');
        $table->saveOrFail($record, ['checkRules' => false, 'validate' => false]);

        $this->exec('available_connections sync');
        $this->assertOutputContains('0 new, 0 updated.');
        $this->assertSame(0, $this->recordOfTheContract()->speed_up_max);
    }

    /**
     * A record that lost its contract gets one back from the contracts still at the point, and is
     * not recorded a second time.
     *
     * @return void
     * @link \App\Command\AvailableConnectionsSyncCommand::execute()
     */
    public function testALostContractIsFoundAgain(): void
    {
        $this->exec('available_connections sync');

        $this->getTableLocator()->get('Contracts')->getConnection()->execute(
            'UPDATE available_connections SET contract_id = NULL WHERE contract_id = ?',
            [self::CONTRACT_ID],
        );
        $this->exec('available_connections sync');

        $this->assertSame(self::CONTRACT_ID, $this->recordOfTheContract()->contract_id);
        $this->assertSame(2, $this->getTableLocator()->get('AvailableConnections')->find()->count());
    }

    /**
     * @return \App\Model\Entity\AvailableConnection
     */
    private function recordOfTheContract(): AvailableConnection
    {
        /** @var \App\Model\Entity\AvailableConnection */
        return $this->getTableLocator()->get('AvailableConnections')->find()
            ->where(['address_registry_reference' => 'Lorem ipsum dolor sit amet'])
            ->firstOrFail();
    }
}
