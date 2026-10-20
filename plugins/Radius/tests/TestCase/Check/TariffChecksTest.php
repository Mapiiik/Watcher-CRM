<?php
declare(strict_types=1);

namespace Radius\Test\TestCase\Check;

use App\Check\CheckScope;
use App\Model\Table\BillingsTable;
use App\Model\Table\ContractsTable;
use App\Model\Table\ServiceOverridesTable;
use Cake\Database\Connection;
use Cake\Database\Driver\Postgres;
use Cake\I18n\Date;
use Cake\Log\Engine\ArrayLog;
use Cake\Log\Log;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;
use Radius\Check\AbstractRadiusCheck;
use Radius\Check\AccountFinding;
use Radius\Check\RadiusAccountWithoutTariffCheck;
use Radius\Check\RadiusGroupMismatchCheck;
use Radius\Check\TariffComparison;
use Radius\Model\Enum\AccountType;
use Radius\Model\Table\AccountsTable;
use Radius\Model\Table\RadusergroupTable;
use Radius\Test\Traits\TariffsTestTrait;

/**
 * The pair of checks that hold the active RADIUS accounts against the tariffs.
 *
 * Tested together, because an account either has a tariff to be compared with or has none.
 */
#[UsesClass(RadiusGroupMismatchCheck::class)]
#[UsesClass(RadiusAccountWithoutTariffCheck::class)]
#[UsesClass(TariffComparison::class)]
class TariffChecksTest extends TestCase
{
    use TariffsTestTrait;

    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    private const OTHER_CONTRACT_ID = '9c0d5e5c-2a6b-4f8e-9a3d-1b7c4e2f6a90';

    private const OTHER_CUSTOMER_ID = 'a6c8f0e2-9b3d-4c1e-8f7a-2d4b6c8e0f13';

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
        'plugin.Radius.Accounts',
        'plugin.Radius.Radusergroup',
    ];

    /**
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        // every case writes the accounts and tariffs it is about
        $this->fetchTable(AccountsTable::class)->deleteAll(['1 = 1']);
        $this->fetchTable(RadusergroupTable::class)->deleteAll(['1 = 1']);
        $this->fetchTable(BillingsTable::class)->deleteAll(['1 = 1']);
        $this->fetchTable(ServiceOverridesTable::class)->deleteAll(['1 = 1']);
    }

    /**
     * @return void
     * @link \Radius\Check\RadiusGroupMismatchCheck::find()
     */
    public function testAnAccountInAnotherGroupThanItsTariffIsReported(): void
    {
        $this->billed(self::CONTRACT_ID, self::CUSTOMER_ID, $this->tariff('slow'), Date::today()->subYears(1));
        $this->account('alice', ['fast']);

        $findings = $this->findings(new RadiusGroupMismatchCheck($this->contracts()));

        $this->assertCount(1, $findings);
        $this->assertSame('alice', $findings[0]->account->username);
        $this->assertSame('slow', $findings[0]->expected_groupname);
        $this->assertSame(['fast'], $findings[0]->groupnames());
        $this->assertSame([], $this->findings(new RadiusAccountWithoutTariffCheck($this->contracts())));
    }

    /**
     * @return void
     * @link \Radius\Check\RadiusGroupMismatchCheck::find()
     */
    public function testAnAccountInTheGroupOfItsTariffIsNotReported(): void
    {
        $this->billed(self::CONTRACT_ID, self::CUSTOMER_ID, $this->tariff('slow'), Date::today()->subYears(1));
        $this->account('alice', ['slow']);

        $this->assertSame([], $this->findings(new RadiusGroupMismatchCheck($this->contracts())));
    }

    /**
     * A second group beside the right one is still not what the tariff says.
     *
     * @return void
     * @link \Radius\Check\RadiusGroupMismatchCheck::find()
     */
    public function testAnAccountInAnotherGroupBesideItsOwnIsReported(): void
    {
        $this->billed(self::CONTRACT_ID, self::CUSTOMER_ID, $this->tariff('slow'), Date::today()->subYears(1));
        $this->account('alice', ['slow', 'fast']);

        $this->assertCount(1, $this->findings(new RadiusGroupMismatchCheck($this->contracts())));
    }

    /**
     * An override in force is what the account has to follow, as the updater does.
     *
     * @return void
     * @link \Radius\Check\RadiusGroupMismatchCheck::find()
     */
    public function testAnOverrideInForceIsWhatTheAccountIsHeldAgainst(): void
    {
        $this->billed(self::CONTRACT_ID, self::CUSTOMER_ID, $this->tariff('slow'), Date::today()->subYears(1));
        $this->overridden(self::CONTRACT_ID, $this->tariff('fast'), Date::today(), Date::today());
        $this->account('alice', ['fast']);

        $this->assertSame([], $this->findings(new RadiusGroupMismatchCheck($this->contracts())));
    }

    /**
     * @return void
     * @link \Radius\Check\RadiusAccountWithoutTariffCheck::find()
     */
    public function testAnAccountWithNoTariffAtAllIsReported(): void
    {
        $this->account('alice', ['slow']);

        $findings = $this->findings(new RadiusAccountWithoutTariffCheck($this->contracts()));

        $this->assertCount(1, $findings);
        $this->assertNull($findings[0]->tariff_from);
        $this->assertSame([], $this->findings(new RadiusGroupMismatchCheck($this->contracts())));
    }

    /**
     * A tariff starting further ahead than the updater looks is still reported, with its day.
     *
     * @return void
     * @link \Radius\Check\RadiusAccountWithoutTariffCheck::find()
     */
    public function testAnAccountWhoseTariffStartsLaterIsReportedWithTheDay(): void
    {
        $from = Date::today()->addMonths(3);
        $this->billed(self::CONTRACT_ID, self::CUSTOMER_ID, $this->tariff('slow'), $from);
        $this->account('alice', ['slow']);

        $findings = $this->findings(new RadiusAccountWithoutTariffCheck($this->contracts()));

        $this->assertCount(1, $findings);
        $this->assertEquals($from, $findings[0]->tariff_from);
    }

    /**
     * @return void
     * @link \Radius\Check\TariffComparison::mismatched()
     */
    public function testAnInactiveAccountIsNeverReported(): void
    {
        $this->account('alice', ['fast'], active: false);
        $this->account('bob', ['fast'], active: false, contract_id: self::OTHER_CONTRACT_ID);
        $this->billed(self::CONTRACT_ID, self::CUSTOMER_ID, $this->tariff('slow'), Date::today()->subYears(1));

        $this->assertSame([], $this->findings(new RadiusGroupMismatchCheck($this->contracts())));
        $this->assertSame([], $this->findings(new RadiusAccountWithoutTariffCheck($this->contracts())));
    }

    /**
     * Asked about one contract or one customer, the check answers about that one only.
     *
     * @return void
     * @link \Radius\Check\TariffComparison::withoutTariff()
     */
    public function testTheCheckKeepsToWhatItWasAskedAbout(): void
    {
        $this->account('alice', ['slow']);

        $this->assertCount(1, $this->withoutTariffOn(contract_id: self::CONTRACT_ID));
        $this->assertSame([], $this->withoutTariffOn(contract_id: self::OTHER_CONTRACT_ID));
        $this->assertCount(1, $this->withoutTariffOn(customer_id: self::CUSTOMER_ID));
        $this->assertSame([], $this->withoutTariffOn(customer_id: self::OTHER_CUSTOMER_ID));
    }

    /**
     * The finding is the contract, so two accounts on one contract count once and are both listed.
     *
     * @return void
     * @link \Radius\Check\AbstractRadiusCheck::find()
     */
    public function testAccountsOnOneContractAreListedUnderIt(): void
    {
        $this->account('alice', ['slow']);
        $this->account('bob', ['slow']);

        $check = new RadiusAccountWithoutTariffCheck($this->contracts());

        $this->assertSame(1, $check->count());
        $this->assertCount(2, $this->findings($check));
    }

    /**
     * Both checks read one comparison, so the RADIUS database is asked once.
     *
     * @return void
     * @link \Radius\Check\TariffComparison
     */
    public function testTheChecksShareTheComparison(): void
    {
        $this->account('alice', ['slow']);

        $comparison = new TariffComparison();
        $scope = new CheckScope();

        $this->assertSame(
            $comparison->withoutTariff(),
            (new RadiusAccountWithoutTariffCheck($this->contracts(), $scope, $comparison))->find()->first()
                ?->get('radius_findings'),
        );
    }

    /**
     * A RADIUS database that cannot be reached gives no findings and a warning, so the pages
     * asking about it still draw.
     *
     * @return void
     * @link \Radius\Check\TariffComparison
     */
    public function testAnUnreachableRadiusDatabaseGivesNoFindings(): void
    {
        $this->fetchTable(AccountsTable::class)->setConnection(new Connection([
            'driver' => Postgres::class,
            'host' => '127.0.0.1',
            'port' => 1,
            'database' => 'radius',
            'username' => 'radius',
            'password' => 'radius',
        ]));

        Log::drop('radius_down');
        Log::setConfig('radius_down', ['className' => ArrayLog::class, 'levels' => ['warning']]);

        try {
            $this->assertSame([], $this->findings(new RadiusGroupMismatchCheck($this->contracts())));
            $this->assertSame([], $this->findings(new RadiusAccountWithoutTariffCheck($this->contracts())));

            /** @var \Cake\Log\Engine\ArrayLog $log */
            $log = Log::engine('radius_down');
            $this->assertStringContainsString(
                'The RADIUS accounts could not be held against the tariffs.',
                implode("\n", $log->read()),
            );
        } finally {
            Log::drop('radius_down');
        }
    }

    /**
     * An active account with the given groups on a contract of the customer under test.
     *
     * @param string $username Username.
     * @param list<string> $groupnames RADIUS groups.
     * @param bool $active Whether the account is active.
     * @param string $contract_id Contract id.
     * @return void
     */
    private function account(
        string $username,
        array $groupnames,
        bool $active = true,
        string $contract_id = self::CONTRACT_ID,
    ): void {
        $accounts = $this->fetchTable(AccountsTable::class);

        $accounts->saveOrFail(
            $accounts->newEntity(
                [
                    'username' => $username,
                    'password' => 'secret',
                    'type' => AccountType::PPPoE,
                    'active' => $active,
                    'customer_id' => self::CUSTOMER_ID,
                    'contract_id' => $contract_id,
                    'radusergroup' => array_map(
                        fn(string $groupname): array => ['groupname' => $groupname, 'priority' => 0],
                        $groupnames,
                    ),
                ],
                ['validate' => false, 'accessibleFields' => ['*' => true]],
            ),
            ['checkRules' => false],
        );
    }

    /**
     * What the check for accounts without a tariff reports about one contract or one customer.
     *
     * @param string|null $contract_id Contract to ask about.
     * @param string|null $customer_id Customer to ask about.
     * @return list<\Radius\Check\AccountFinding>
     */
    private function withoutTariffOn(?string $contract_id = null, ?string $customer_id = null): array
    {
        return $this->findings(new RadiusAccountWithoutTariffCheck(
            $this->contracts(),
            new CheckScope(contract_id: $contract_id, customer_id: $customer_id),
        ));
    }

    /**
     * The accounts the check reports, across the contracts it lists.
     *
     * @param \Radius\Check\AbstractRadiusCheck $check The check.
     * @return list<\Radius\Check\AccountFinding>
     */
    private function findings(AbstractRadiusCheck $check): array
    {
        $findings = [];
        foreach ($check->find()->all() as $contract) {
            /** @var list<\Radius\Check\AccountFinding> $found */
            $found = $contract->get('radius_findings');
            array_push($findings, ...$found);
        }

        usort(
            $findings,
            fn(AccountFinding $a, AccountFinding $b): int => $a->account->username <=> $b->account->username,
        );

        return $findings;
    }

    /**
     * @return \App\Model\Table\ContractsTable
     */
    private function contracts(): ContractsTable
    {
        return $this->fetchTable(ContractsTable::class);
    }
}
