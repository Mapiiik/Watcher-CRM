<?php
declare(strict_types=1);

namespace App\Test\TestCase\Contracts\Check;

use App\Contracts\Check\OverlappingBillingsCheck;
use App\Model\Table\BillingsTable;
use Cake\I18n\Date;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Contracts\Check\OverlappingBillingsCheck Test Case
 *
 * The mirror image of a break, and the expensive way round: a break costs us, an overlap
 * costs the customer, and the customer notices.
 */
#[UsesClass(OverlappingBillingsCheck::class)]
class OverlappingBillingsCheckTest extends TestCase
{
    use LocatorAwareTrait;

    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    private const SERVICE_ID = 'eaacfeb3-1430-43ce-842e-497c5c95d953';

    private const OTHER_SERVICE_ID = '5f6a2f47-0a4d-4c05-9bcb-2f0dc0a3f0d2';

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
    }

    /**
     * The usual shape of it: the old billing was never given an end, so from the day the new
     * one starts the customer is charged for both.
     *
     * @return void
     * @link \App\Contracts\Check\OverlappingBillingsCheck::find()
     */
    public function testABillingLeftOpenUnderANewOneIsReported(): void
    {
        $this->billed('-2 years', null);
        $this->billed('-1 year', null);

        $found = $this->found();

        $this->assertCount(1, $found, 'Only the earlier of the pair is the one to fix.');
        $this->assertSame(
            Date::now()->modify('-2 years')->format('Y-m-d'),
            $found[0]->billing_from?->format('Y-m-d'),
        );
    }

    /**
     * The day the second one starts is where the double charging begins, so it comes back
     * with the row.
     *
     * @return void
     * @link \App\Contracts\Check\OverlappingBillingsCheck::find()
     */
    public function testTheDayTheOverlapBeginsComesBackWithTheRow(): void
    {
        $this->billed('-2 years', '+1 year');
        $this->billed('-1 year', null);

        $overlaps = $this->found()[0]->get('overlaps_from');

        $this->assertInstanceOf(Date::class, $overlaps);
        $this->assertSame(Date::now()->modify('-1 year')->format('Y-m-d'), $overlaps->format('Y-m-d'));
    }

    /**
     * One ending the day before the next begins is an unbroken run, not an overlap.
     *
     * @return void
     * @link \App\Contracts\Check\OverlappingBillingsCheck::find()
     */
    public function testBillingsThatOnlyMeetAreNotAnOverlap(): void
    {
        $this->billed('-2 years', '-1 year -1 day');
        $this->billed('-1 year', null);

        $this->assertSame([], $this->found());
    }

    /**
     * Two services on one contract run side by side on purpose - that is what a contract with
     * an internet tariff and a static address looks like.
     *
     * @return void
     * @link \App\Contracts\Check\OverlappingBillingsCheck::find()
     */
    public function testTwoServicesRunningTogetherAreNotAnOverlap(): void
    {
        $this->billed('-2 years', null);
        $this->billed('-1 year', null, self::OTHER_SERVICE_ID);

        $this->assertSame([], $this->found());
    }

    /**
     * Three billings over one stretch are two overlapping pairs, and the two earlier ones are
     * what there is to fix. The latest cannot be at fault: nothing starts under it.
     *
     * @return void
     * @link \App\Contracts\Check\OverlappingBillingsCheck::find()
     */
    public function testEveryBillingWithAnotherStartingUnderItIsReported(): void
    {
        $this->billed('-3 years', null);
        $this->billed('-2 years', null);
        $this->billed('-1 year', null);

        $this->assertCount(2, $this->found());
    }

    /**
     * Run the check and return what it found.
     *
     * @param bool $ignore_inactive Whether to keep to the contracts that are running.
     * @return list<\App\Model\Entity\Billing>
     */
    private function found(bool $ignore_inactive = true): array
    {
        /** @var list<\App\Model\Entity\Billing> $records */
        $records = (new OverlappingBillingsCheck($this->Billings, $ignore_inactive))->find()->all()->toList();

        return $records;
    }

    /**
     * Bill a service over a stretch of time, given relative to today.
     *
     * @param string $from When billing starts, as a modifier of today.
     * @param string|null $until When it ends, or null for an open end.
     * @param string $service_id The service being billed.
     * @return void
     */
    private function billed(string $from, ?string $until, string $service_id = self::SERVICE_ID): void
    {
        $this->Billings->saveOrFail($this->Billings->newEntity([
            'customer_id' => self::CUSTOMER_ID,
            'contract_id' => self::CONTRACT_ID,
            'service_id' => $service_id,
            'billing_from' => Date::now()->modify($from)->format('Y-m-d'),
            'billing_until' => $until === null ? null : Date::now()->modify($until)->format('Y-m-d'),
            'quantity' => 1,
            'separate_invoice' => false,
        ]));
    }
}
