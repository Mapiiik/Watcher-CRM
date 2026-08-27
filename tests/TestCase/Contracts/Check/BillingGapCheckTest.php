<?php
declare(strict_types=1);

namespace App\Test\TestCase\Contracts\Check;

use App\Contracts\Check\BillingGapCheck;
use App\Model\Table\BillingsTable;
use Cake\I18n\Date;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Contracts\Check\BillingGapCheck Test Case
 *
 * Billings of one service on one contract are meant to run end to end. A break means those
 * months are invoiced to nobody, and the only sign of it is the money that fails to arrive.
 */
#[UsesClass(BillingGapCheck::class)]
class BillingGapCheckTest extends TestCase
{
    use LocatorAwareTrait;

    /**
     * The contract the billings under test hang on.
     */
    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

    /**
     * A second contract, to show that a check asked about one does not answer about another.
     */
    private const OTHER_CONTRACT_ID = '9c0d5e5c-2a6b-4f8e-9a3d-1b7c4e2f6a90';

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

        // Every case here is about how a handful of billings sit against each other in time,
        // so each one lays out its own; what the fixture happens to carry would only be a
        // second set of billings the case says nothing about.
        $this->Billings->deleteAll(['1 = 1']);
    }

    /**
     * A billing that ends and one that picks up a year later, with nothing in between.
     *
     * @return void
     * @link \App\Contracts\Check\BillingGapCheck::find()
     */
    public function testABreakStillToComeIsReported(): void
    {
        $this->billed('+1 month', '+13 months');
        $this->billed('+25 months', null);

        $found = $this->found();

        $this->assertCount(1, $found);
        $this->assertSame(
            Date::now()->modify('+13 months')->format('Y-m-d'),
            $found[0]->billing_until?->format('Y-m-d'),
        );
    }

    /**
     * The one before the break is the one to report - it is either extended, or its successor
     * is corrected. Reporting every earlier billing of the run would bury it.
     *
     * @return void
     * @link \App\Contracts\Check\BillingGapCheck::find()
     */
    public function testOnlyTheLastBillingBeforeTheBreakIsReported(): void
    {
        $this->billed('+1 month', '+6 months');
        $this->billed('+6 months +1 day', '+13 months');
        $this->billed('+25 months', null);

        $found = $this->found();

        $this->assertCount(1, $found);
        $this->assertSame(
            Date::now()->modify('+13 months')->format('Y-m-d'),
            $found[0]->billing_until?->format('Y-m-d'),
        );
    }

    /**
     * The next one starting the day after is exactly what an unbroken run looks like.
     *
     * @return void
     * @link \App\Contracts\Check\BillingGapCheck::find()
     */
    public function testBillingsThatMeetAreNotABreak(): void
    {
        $this->billed('+1 month', '+13 months');
        $this->billed('+13 months +1 day', null);

        $this->assertSame([], $this->found());
    }

    /**
     * Billings that overlap have no break between them. That they overlap at all is another
     * check's finding, not this one's.
     *
     * @return void
     * @link \App\Contracts\Check\BillingGapCheck::find()
     */
    public function testBillingsThatOverlapAreNotABreak(): void
    {
        $this->billed('+1 month', '+13 months');
        $this->billed('+6 months', null);

        $this->assertSame([], $this->found());
    }

    /**
     * Two services each have their own run. Another service billed across the break covers
     * the calendar but not the service, and the service is what is left unbilled.
     *
     * @return void
     * @link \App\Contracts\Check\BillingGapCheck::find()
     */
    public function testAnotherServiceDoesNotFillTheBreak(): void
    {
        $this->billed('+1 month', '+13 months');
        $this->billed('+25 months', null);
        $this->billed('+14 months', '+24 months', self::OTHER_SERVICE_ID);

        $this->assertCount(1, $this->found());
    }

    /**
     * A single billing has nothing after it, so there is nothing it can have a break before.
     *
     * @return void
     * @link \App\Contracts\Check\BillingGapCheck::find()
     */
    public function testABillingWithNothingAfterItIsNotReported(): void
    {
        $this->billed('+1 month', '+13 months');

        $this->assertSame([], $this->found());
    }

    /**
     * A break that is already over cannot be invoiced after the fact, so daily work is not
     * shown it. Putting the history straight is what lifting the filter is for.
     *
     * @return void
     * @link \App\Contracts\Check\BillingGapCheck::find()
     */
    public function testABreakThatIsOverIsOnlyReportedWhenTheFilterIsLifted(): void
    {
        $this->billed('-3 years', '-2 years');
        $this->billed('-1 year', null);

        $this->assertSame([], $this->found());
        $this->assertCount(1, $this->found(ignore_inactive: false));
    }

    /**
     * Asked about one contract, the check answers about that one.
     *
     * @return void
     * @link \App\Contracts\Check\BillingGapCheck::find()
     */
    public function testAskedAboutOneContractItLeavesTheOthersAlone(): void
    {
        $this->billed('+1 month', '+13 months');
        $this->billed('+25 months', null);

        $this->assertCount(1, $this->found(contract_id: self::CONTRACT_ID));
        $this->assertSame([], $this->found(contract_id: self::OTHER_CONTRACT_ID));
    }

    /**
     * The listing says how long the break is, so the day billing picks up again comes back
     * with the row rather than being asked for once per line.
     *
     * @return void
     * @link \App\Contracts\Check\BillingGapCheck::find()
     */
    public function testTheDayBillingPicksUpAgainComesBackWithTheRow(): void
    {
        $this->billed('+1 month', '+13 months');
        $this->billed('+25 months', null);

        $resumes = $this->found()[0]->get('resumes_on');

        $this->assertInstanceOf(Date::class, $resumes);
        $this->assertSame(Date::now()->modify('+25 months')->format('Y-m-d'), $resumes->format('Y-m-d'));
    }

    /**
     * Run the check and return what it found.
     *
     * @param bool $ignore_inactive Whether to keep to breaks that are still ahead.
     * @param string|null $contract_id One contract to ask about.
     * @return list<\App\Model\Entity\Billing>
     */
    private function found(bool $ignore_inactive = true, ?string $contract_id = null): array
    {
        $check = new BillingGapCheck($this->Billings, $ignore_inactive, $contract_id);

        /** @var list<\App\Model\Entity\Billing> $records */
        $records = $check->find()->all()->toList();

        return $records;
    }

    /**
     * Bill a service over a stretch of time, given relative to today so that "still ahead"
     * and "already over" mean what they say whenever the test is run.
     *
     * @param string $from When billing starts, as a modifier of today.
     * @param string|null $until When it ends, or null for an open end.
     * @param string $service_id The service being billed.
     * @param string $contract_id The contract it hangs on.
     * @return void
     */
    private function billed(
        string $from,
        ?string $until,
        string $service_id = self::SERVICE_ID,
        string $contract_id = self::CONTRACT_ID,
    ): void {
        $billing = $this->Billings->newEntity([
            'customer_id' => self::CUSTOMER_ID,
            'contract_id' => $contract_id,
            'service_id' => $service_id,
            'billing_from' => Date::now()->modify($from)->format('Y-m-d'),
            'billing_until' => $until === null ? null : Date::now()->modify($until)->format('Y-m-d'),
            'quantity' => 1,
            'separate_invoice' => false,
        ]);

        $this->Billings->saveOrFail($billing);
    }
}
