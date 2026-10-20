<?php
declare(strict_types=1);

namespace Radius\Test\TestCase\Updater;

use App\Model\Table\BillingsTable;
use App\Model\Table\ServiceOverridesTable;
use Cake\Core\Configure;
use Cake\I18n\Date;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;
use Radius\Model\Entity\Account;
use Radius\Model\Entity\Radusergroup;
use Radius\Test\Traits\TariffsTestTrait;
use Radius\Updater\AccountsUpdater;
use Radius\Updater\ExpectedUserGroups;

/**
 * Radius\Updater\AccountsUpdater Test Case
 */
#[UsesClass(AccountsUpdater::class)]
#[UsesClass(ExpectedUserGroups::class)]
class AccountsUpdaterTest extends TestCase
{
    use TariffsTestTrait;

    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
        'app.AccountingProfiles',
        'app.Customers',
        'app.Countries',
        'app.Addresses',
        'app.Commissions',
        'app.ContractStates',
        'app.ServiceTypes',
        'app.Contracts',
        'app.ConnectionProfiles',
        'app.Services',
        'app.Billings',
        'app.ServiceOverrides',
        'plugin.Radius.Radusergroup',
    ];

    /**
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Configure::write('Radius.defaultUserGroup', '');

        $this->fetchTable(BillingsTable::class)->deleteAll(['1 = 1']);
        $this->fetchTable(ServiceOverridesTable::class)->deleteAll(['1 = 1']);
    }

    /**
     * @return void
     * @link \Radius\Updater\AccountsUpdater::autoRadusergroupData()
     */
    public function testTheGroupIsTheOneTheTariffNames(): void
    {
        $this->billed(self::CONTRACT_ID, self::CUSTOMER_ID, $this->tariff('slow'), Date::today()->subYears(1));
        $this->overridden(self::CONTRACT_ID, $this->tariff('fast'), Date::today(), Date::today());

        $groups = (new AccountsUpdater())->autoRadusergroupData($this->account());

        $this->assertSame(['fast'], array_map(fn(Radusergroup $group): string => $group->groupname, $groups));
    }

    /**
     * With no tariff and no default group, the account keeps the groups it has.
     *
     * @return void
     * @link \Radius\Updater\AccountsUpdater::autoRadusergroupData()
     */
    public function testWithoutATariffTheCurrentGroupsAreKept(): void
    {
        $account = $this->account();
        $account->radusergroup = [new Radusergroup(['username' => 'test', 'groupname' => 'kept'])];

        $groups = (new AccountsUpdater())->autoRadusergroupData($account);

        $this->assertSame(['kept'], array_map(fn(Radusergroup $group): string => $group->groupname, $groups));
    }

    /**
     * @return \Radius\Model\Entity\Account
     */
    private function account(): Account
    {
        return new Account(['username' => 'test', 'contract_id' => self::CONTRACT_ID, 'radusergroup' => []]);
    }
}
