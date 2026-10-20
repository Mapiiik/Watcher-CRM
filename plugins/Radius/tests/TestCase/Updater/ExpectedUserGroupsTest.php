<?php
declare(strict_types=1);

namespace Radius\Test\TestCase\Updater;

use App\Model\Table\BillingsTable;
use App\Model\Table\ServiceOverridesTable;
use Cake\I18n\Date;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;
use Radius\Test\Traits\TariffsTestTrait;
use Radius\Updater\ExpectedUserGroups;

/**
 * Radius\Updater\ExpectedUserGroups Test Case
 */
#[UsesClass(ExpectedUserGroups::class)]
class ExpectedUserGroupsTest extends TestCase
{
    use TariffsTestTrait;

    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

    private const OTHER_CONTRACT_ID = '9c0d5e5c-2a6b-4f8e-9a3d-1b7c4e2f6a90';

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
    ];

    private Date $today;

    /**
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->today = new Date('2026-06-15');

        // every case writes the tariffs it is about
        $this->fetchTable(BillingsTable::class)->deleteAll(['1 = 1']);
        $this->fetchTable(ServiceOverridesTable::class)->deleteAll(['1 = 1']);
    }

    /**
     * @return void
     * @link \Radius\Updater\ExpectedUserGroups::forContract()
     */
    public function testTheBillingRunningNowGivesTheGroup(): void
    {
        $this->billed(self::CONTRACT_ID, self::CUSTOMER_ID, $this->tariff('slow'), $this->day('-1 year'));

        $this->assertSame('slow', $this->expected()->forContract(self::CONTRACT_ID));
    }

    /**
     * The updater writes the group a little ahead, so that a tariff changing on the first of the
     * month is in place on the day.
     *
     * @return void
     * @link \Radius\Updater\ExpectedUserGroups::forContract()
     */
    public function testABillingStartingWithinAMonthGivesTheGroup(): void
    {
        $this->billed(self::CONTRACT_ID, self::CUSTOMER_ID, $this->tariff('fast'), $this->day('+20 days'));

        $this->assertSame('fast', $this->expected()->forContract(self::CONTRACT_ID));
    }

    /**
     * Of two billings in range, the one that started first decides, so a tariff changing later
     * this month does not take over before its day.
     *
     * @return void
     * @link \Radius\Updater\ExpectedUserGroups::forContract()
     */
    public function testTheEarliestBillingDecides(): void
    {
        $this->billed(
            self::CONTRACT_ID,
            self::CUSTOMER_ID,
            $this->tariff('slow'),
            $this->day('-1 year'),
            $this->day('+10 days'),
        );
        $this->billed(self::CONTRACT_ID, self::CUSTOMER_ID, $this->tariff('fast'), $this->day('+11 days'));

        $this->assertSame('slow', $this->expected()->forContract(self::CONTRACT_ID));
        $this->assertSame('fast', $this->expected($this->day('+11 days'))->forContract(self::CONTRACT_ID));
    }

    /**
     * @return void
     * @link \Radius\Updater\ExpectedUserGroups::forContract()
     */
    public function testAnEndedBillingGivesNoGroup(): void
    {
        $this->billed(
            self::CONTRACT_ID,
            self::CUSTOMER_ID,
            $this->tariff('slow'),
            $this->day('-2 years'),
            $this->day('-1 day'),
        );

        $this->assertNull($this->expected()->forContract(self::CONTRACT_ID));
        $this->assertSame([], $this->expected()->laterStarts([self::CONTRACT_ID]));
    }

    /**
     * A billing further ahead than a month gives no group yet, but says when one will come.
     *
     * @return void
     * @link \Radius\Updater\ExpectedUserGroups::laterStarts()
     */
    public function testABillingFurtherAheadIsALaterStart(): void
    {
        $tariff = $this->tariff('fast');
        $this->billed(self::CONTRACT_ID, self::CUSTOMER_ID, $tariff, $this->day('+3 months'));
        $this->billed(self::CONTRACT_ID, self::CUSTOMER_ID, $tariff, $this->day('+2 months'));

        $this->assertNull($this->expected()->forContract(self::CONTRACT_ID));
        $this->assertEquals(
            [self::CONTRACT_ID => $this->day('+2 months')],
            $this->expected()->laterStarts([self::CONTRACT_ID]),
        );
    }

    /**
     * A billing whose service has no connection profile says nothing about the group.
     *
     * @return void
     * @link \Radius\Updater\ExpectedUserGroups::forContract()
     */
    public function testABillingWithoutAProfileGivesNoGroup(): void
    {
        $tariff = $this->tariff('slow');
        $this->fetchTable('Services')->updateAll(['connection_profile_id' => null], ['id' => $tariff]);
        $this->billed(self::CONTRACT_ID, self::CUSTOMER_ID, $tariff, $this->day('-1 year'));

        $this->assertNull($this->expected()->forContract(self::CONTRACT_ID));
    }

    /**
     * @return void
     * @link \Radius\Updater\ExpectedUserGroups::forContract()
     */
    public function testAnOverrideInForceWins(): void
    {
        $this->billed(self::CONTRACT_ID, self::CUSTOMER_ID, $this->tariff('slow'), $this->day('-1 year'));
        $this->overridden(self::CONTRACT_ID, $this->tariff('fast'), $this->day('-1 day'), $this->day('+1 day'));

        $this->assertSame('fast', $this->expected()->forContract(self::CONTRACT_ID));
    }

    /**
     * @return void
     * @link \Radius\Updater\ExpectedUserGroups::forContract()
     */
    public function testAnOverrideRevokedOrOutOfItsDaysDoesNotCount(): void
    {
        $this->billed(self::CONTRACT_ID, self::CUSTOMER_ID, $this->tariff('slow'), $this->day('-1 year'));
        $fast = $this->tariff('fast');
        $this->overridden(self::CONTRACT_ID, $fast, $this->day('-1 day'), $this->day('+1 day'), revoked: true);
        $this->overridden(self::CONTRACT_ID, $fast, $this->day('-1 month'), $this->day('-1 day'));
        $this->overridden(self::CONTRACT_ID, $fast, $this->day('+1 day'), $this->day('+1 month'));

        $this->assertSame('slow', $this->expected()->forContract(self::CONTRACT_ID));
    }

    /**
     * Asked about several contracts at once, each gets its own answer and one with none is left out.
     *
     * @return void
     * @link \Radius\Updater\ExpectedUserGroups::forContracts()
     */
    public function testSeveralContractsAreAnsweredTogether(): void
    {
        $this->billed(self::CONTRACT_ID, self::CUSTOMER_ID, $this->tariff('slow'), $this->day('-1 year'));
        $this->overridden(self::OTHER_CONTRACT_ID, $this->tariff('fast'), $this->day('-1 day'), $this->day('+1 day'));

        $this->assertEquals(
            [self::CONTRACT_ID => 'slow', self::OTHER_CONTRACT_ID => 'fast'],
            $this->expected()->forContracts([self::CONTRACT_ID, self::OTHER_CONTRACT_ID, self::CONTRACT_ID]),
        );
        $this->assertSame([], $this->expected()->forContracts([]));
    }

    /**
     * @param \Cake\I18n\Date|null $today The day to ask about.
     * @return \Radius\Updater\ExpectedUserGroups
     */
    private function expected(?Date $today = null): ExpectedUserGroups
    {
        return new ExpectedUserGroups($today ?? $this->today);
    }

    /**
     * A day relative to the one the cases ask about.
     *
     * @param string $modifier Relative day, as `Date::modify()` takes it.
     * @return \Cake\I18n\Date
     */
    private function day(string $modifier): Date
    {
        return $this->today->modify($modifier);
    }
}
