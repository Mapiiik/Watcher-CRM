<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Dashboard\Card\StatisticsCard;
use App\Model\Enum\ContractPeriodSource;
use App\Test\Traits\ControllerTestTrait;
use Cake\Cache\Cache;
use Cake\Chronos\Chronos;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use Settings\Utility\Settings;

/**
 * The statistics card, counted over a file built for it.
 *
 * Kept apart from the rest of the dashboard's tests because these need the billings and their
 * own versions, while the cards there are tested against a file that bills nothing and whose
 * versions the proposals hold on to.
 */
#[CoversClass(StatisticsCard::class)]
class DashboardStatisticsTest extends TestCase
{
    use ControllerTestTrait;
    use IntegrationTestTrait;

    /**
     * The fixture's first contract.
     */
    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

    /**
     * The fixture's other contract, of the same customer.
     */
    private const OTHER_CONTRACT_ID = '9c0d5e5c-2a6b-4f8e-9a3d-1b7c4e2f6a90';

    /**
     * A service of the fixture, made a connection.
     */
    private const CONNECTION_SERVICE_ID = '5f6a2f47-0a4d-4c05-9bcb-2f0dc0a3f0d2';

    /**
     * A service of the fixture, made a fee by taking away its connection profile.
     */
    private const FEE_SERVICE_ID = 'eaacfeb3-1430-43ce-842e-497c5c95d953';

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
        'app.ContractVersions',
        'app.ConnectionProfiles',
        'app.Services',
        'app.Billings',
        'plugin.Settings.Settings',
    ];

    /**
     * The clock as it was before a test stopped it somewhere else.
     *
     * @var \Cake\Chronos\Chronos|null
     */
    private ?Chronos $was = null;

    /**
     * setUp method
     *
     * The middle of November 2026, with the first contract begun in October and ended in
     * November and the other begun and ended in November.
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->was = Chronos::getTestNow();
        Chronos::setTestNow(new Chronos('2026-11-14 12:00:00'));

        // said rather than inherited, as the settings outlive a test in the cache
        Settings::set('core.dashboard.contracts.period_source', ContractPeriodSource::Versions->value);

        $versions = $this->getTableLocator()->get('ContractVersions');
        $versions->deleteAll(['1 = 1']);
        foreach (
            [
                [self::CONTRACT_ID, '2026-10-05', '2026-11-10'],
                [self::OTHER_CONTRACT_ID, '2026-11-03', '2026-11-20'],
            ] as [$contract, $from, $until]
        ) {
            $versions->saveOrFail($versions->newEntity([
                'contract_id' => $contract,
                'valid_from' => $from,
                'valid_until' => $until,
                'number_of_amendments' => 0,
                'obligations_settled' => false,
            ]));
        }

        // Written straight, the rules of a billing are not what this is about. Each contract has
        // a connection: the first from long ago, and in November a second, overlapping line;
        // the other only in November, and before that nothing but a fee.
        $this->getTableLocator()->get('Services')->updateAll(
            ['connection_profile_id' => null],
            ['id' => self::FEE_SERVICE_ID],
        );
        $billings = $this->getTableLocator()->get('Billings');
        $billings->updateAll(
            ['contract_id' => self::OTHER_CONTRACT_ID, 'billing_from' => '2026-10-01', 'billing_until' => null],
            ['id' => 'b1000000-0000-4000-8000-000000000001'],
        );
        $billings->updateAll(
            ['billing_from' => '2026-11-01', 'billing_until' => '2026-11-20'],
            ['id' => 'b3000000-0000-4000-8000-000000000003'],
        );
        $billings->insertQuery()
            ->insert(['id', 'customer_id', 'contract_id', 'service_id', 'text', 'billing_from', 'quantity'])
            ->values([
                'id' => 'b4000000-0000-4000-8000-000000000004',
                'customer_id' => '403bab0e-52cd-4a8e-83f8-43c2457d0481',
                'contract_id' => self::CONTRACT_ID,
                'service_id' => self::CONNECTION_SERVICE_ID,
                'text' => 'A second line of the connection',
                'billing_from' => '2026-11-01',
                'quantity' => 1,
            ])
            ->execute();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        Chronos::setTestNow($this->was);
        Cache::clear('default');

        parent::tearDown();
    }

    /**
     * The card as the dashboard fetched it, its rows by title.
     *
     * @return array<string, list<int>>
     */
    private function figures(): array
    {
        $this->login('bookkeeper');
        $this->get('/dashboard/card/statistics');
        $this->assertResponseOk();

        /** @var \App\Dashboard\Card\StatisticsCard $card */
        $card = $this->viewVariable('card');
        $rows = $card->data()['rows'];

        return array_combine(array_column($rows, 'title'), array_column($rows, 'values'));
    }

    /**
     * This month and the one before, each counted over the whole of it.
     *
     * @return void
     * @link \App\Dashboard\Card\StatisticsCard::data()
     */
    public function testThisMonthAndTheOneBeforeAreCounted(): void
    {
        $this->assertSame(
            [
                'New Contracts' => [1, 1],
                'Ended Contracts' => [2, 0],
                'Net Change' => [-1, 1],
                'New Customers' => [0, 1],
                'Departed Customers' => [1, 0],
                // today for the month running, the last day for the one before - each contract
                // once, and the fee not at all
                'Active Connections' => [2, 1],
            ],
            $this->figures(),
        );
        $this->assertResponseContains('November 2026');
        $this->assertResponseContains('October 2026');
        $this->assertResponseContains('+1');
        $this->assertResponseContains('From the contract versions');
    }

    /**
     * The settings say which dates are read: by the contracts' own columns, the fixture's
     * contract began in 2022 and nothing here falls in either month.
     *
     * @return void
     * @link \App\Dashboard\Card\StatisticsCard::data()
     */
    public function testTheSettingsSayWhichDatesAreRead(): void
    {
        Settings::set('core.dashboard.contracts.period_source', ContractPeriodSource::ServiceDates->value);

        $figures = $this->figures();

        $this->assertSame([0, 0], $figures['New Contracts']);
        $this->assertSame([0, 0], $figures['New Customers']);
        $this->assertResponseContains('From the installation and termination dates');
    }

    /**
     * A number leads to the overview of the same month, read off the same dates, which counts
     * the same contracts.
     *
     * @return void
     * @link \App\Dashboard\Card\StatisticsCard::data()
     */
    public function testANumberLeadsToTheOverviewThatCountsTheSame(): void
    {
        $this->login('bookkeeper');
        $this->get('/dashboard/card/statistics');

        /** @var \App\Dashboard\Card\StatisticsCard $card */
        $card = $this->viewVariable('card');
        $rows = $card->data()['rows'];

        $this->get($rows[1]['urls'][0]);

        $this->assertResponseOk();
        /** @var iterable<\App\Model\Entity\Contract> $ending */
        $ending = $this->viewVariable('ending');
        $this->assertCount($rows[1]['values'][0], $ending);
    }
}
