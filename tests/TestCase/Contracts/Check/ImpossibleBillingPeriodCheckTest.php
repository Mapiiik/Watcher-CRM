<?php
declare(strict_types=1);

namespace App\Test\TestCase\Contracts\Check;

use App\Contracts\Check\ImpossibleBillingPeriodCheck;
use App\Model\Table\BillingsTable;
use Cake\Cache\Cache;
use Cake\I18n\Date;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;
use Settings\Utility\Settings;

/**
 * App\Contracts\Check\ImpossibleBillingPeriodCheck Test Case
 *
 * Where a day may reasonably fall is a matter of how the company works, so it is asked of the
 * settings rather than settled here - and that it really is asked is what the last case says.
 */
#[UsesClass(ImpossibleBillingPeriodCheck::class)]
class ImpossibleBillingPeriodCheckTest extends TestCase
{
    use LocatorAwareTrait;

    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    private const SERVICE_ID = 'eaacfeb3-1430-43ce-842e-497c5c95d953';

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
        'app.Queues',
        'app.Services',
        'app.Billings',
        'plugin.Settings.Settings',
    ];

    private BillingsTable $Billings;

    /**
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->Billings = $this->getTableLocator()->get('Billings', ['className' => BillingsTable::class]);
        $this->Billings->deleteAll(['1 = 1']);

        // A setting written here would otherwise answer the next run: the row is rolled back
        // with the test, but what was cached from it is not.
        Cache::clear('default');
    }

    /**
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        Cache::clear('default');

        parent::tearDown();
    }

    /**
     * A billing that ends before it begins invoices nothing at all, and says nothing about it.
     *
     * @return void
     * @link \App\Contracts\Check\ImpossibleBillingPeriodCheck::find()
     */
    public function testABillingEndingBeforeItBeginsIsReported(): void
    {
        $this->billedBetween('2026-09-01', '2026-01-10');

        $this->assertCount(1, $this->found());
    }

    /**
     * @return void
     * @link \App\Contracts\Check\ImpossibleBillingPeriodCheck::find()
     */
    public function testAnOrdinaryPeriodIsNotReported(): void
    {
        $this->billedBetween('2026-01-01', '2026-12-31');

        $this->assertSame([], $this->found());
    }

    /**
     * A digit too few. The day that was meant is obvious, which is why it is worth reporting
     * rather than arguing about.
     *
     * @return void
     * @link \App\Contracts\Check\ImpossibleBillingPeriodCheck::find()
     */
    public function testADayFromLongBeforeTheCompanyExistedIsReported(): void
    {
        $this->billedBetween('0025-12-05', null);

        $this->assertCount(1, $this->found());
    }

    /**
     * A transposed digit at the other end. The file holds one running to the year 20015 as
     * well, from before anything checked the shape of what was typed.
     *
     * @return void
     * @link \App\Contracts\Check\ImpossibleBillingPeriodCheck::find()
     */
    public function testADayFurtherAheadThanAnythingReachesIsReported(): void
    {
        $this->billedBetween('2026-01-01', Date::now()->modify('+40 years')->format('Y-m-d'));

        $this->assertCount(1, $this->found());
    }

    /**
     * The bounds come from the settings, so an installation that writes longer contracts can
     * say so rather than being told what its own dates mean.
     *
     * @return void
     * @link \App\Contracts\Check\AbstractContractCheck::plausibleUntil()
     */
    public function testHowFarAheadADayMayReachComesFromTheSettings(): void
    {
        $this->billedBetween('2026-01-01', Date::now()->modify('+8 years')->format('Y-m-d'));

        // eight years ahead is beyond the five the settings ship with
        $this->assertCount(1, $this->found());

        Settings::set('core.contracts.checks.years_ahead', 10);

        $this->assertSame([], $this->found(), 'The check did not ask the settings again.');
    }

    /**
     * Run the check and return what it found.
     *
     * @return list<\App\Model\Entity\Billing>
     */
    private function found(): array
    {
        /** @var list<\App\Model\Entity\Billing> $records */
        $records = (new ImpossibleBillingPeriodCheck($this->Billings))->find()->all()->toList();

        return $records;
    }

    /**
     * Bill a service between two days, given as they would be typed.
     *
     * @param string $from When billing starts.
     * @param string|null $until When it ends, or null for an open end.
     * @return void
     */
    private function billedBetween(string $from, ?string $until): void
    {
        $this->Billings->saveOrFail($this->Billings->newEntity([
            'customer_id' => self::CUSTOMER_ID,
            'contract_id' => self::CONTRACT_ID,
            'service_id' => self::SERVICE_ID,
            'billing_from' => $from,
            'billing_until' => $until,
            'quantity' => 1,
            'separate_invoice' => false,
        ]));
    }
}
