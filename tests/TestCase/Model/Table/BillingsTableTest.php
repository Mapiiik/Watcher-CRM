<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Entity\Billing;
use App\Model\Table\BillingsTable;
use App\Test\Traits\TableTestTrait;
use Bookkeeping\Model\Enum\InvoicingSchedule;
use Cake\Chronos\Chronos;
use Cake\I18n\Date;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Settings\Utility\Settings;

/**
 * App\Model\Table\BillingsTable Test Case
 */
class BillingsTableTest extends TestCase
{
    use TableTestTrait;

    /**
     * Test subject
     *
     * @var \App\Model\Table\BillingsTable
     */
    protected $Billings;

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

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Billings') ? [] : ['className' => BillingsTable::class];
        $this->Billings = $this->getTableLocator()->get('Billings', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        /** @phpstan-ignore unset.possiblyHookedProperty */
        unset($this->Billings);

        parent::tearDown();
    }

    /**
     * A new record with nothing filled in is refused - see the trait for why that is the question
     * worth asking here.
     *
     * @return void
     * @link \App\Model\Table\BillingsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->assertEmptyRecordIsRefused($this->Billings);
    }

    /**
     * The rules refuse a record whose references point nowhere - see the trait for why that is
     * the question worth asking here.
     *
     * @return void
     * @link \App\Model\Table\BillingsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->assertDanglingReferencesAreRefused($this->Billings);
    }

    /**
     * Where the line falls, spelled out for each way an installation invoices and for the days it
     * turns on. Everything else in this class hangs off these four dates.
     *
     * @return array<string, array{string, bool, string, string, string}>
     */
    public static function boundaries(): array
    {
        return [
            'the month is invoiced on its own last day, and today is not it' => [
                InvoicingSchedule::CURRENT_MONTH_ON_LAST->value, false, '2026-08-30', '2026-07-31', '2026-07-31',
            ],
            'the month is invoiced today, and the day is shut' => [
                InvoicingSchedule::CURRENT_MONTH_ON_LAST->value, false, '2026-08-31', '2026-08-31', '2026-08-31',
            ],
            'the month is invoiced today, and the day stays open for corrections' => [
                InvoicingSchedule::CURRENT_MONTH_ON_LAST->value, true, '2026-08-31', '2026-08-31', '2026-07-31',
            ],
            'invoiced in arrears, in the middle of the month' => [
                InvoicingSchedule::PREV_MONTH_ON_FIRST->value, false, '2026-08-14', '2026-07-31', '2026-07-31',
            ],
            'invoiced in arrears, on the day of the run, shut' => [
                InvoicingSchedule::PREV_MONTH_ON_FIRST->value, false, '2026-09-01', '2026-08-31', '2026-08-31',
            ],
            'invoiced in arrears, on the day of the run, open for corrections' => [
                InvoicingSchedule::PREV_MONTH_ON_FIRST->value, true, '2026-09-01', '2026-08-31', '2026-07-31',
            ],
        ];
    }

    /**
     * What invoicing has settled is a fact about the run; what may no longer be moved is a
     * decision about editing. They part company on the day of the run and nowhere else.
     *
     * @param string $schedule When the installation invoices.
     * @param bool $dayStaysOpen Whether the day of the run is left editable.
     * @param string $today The day the question is asked on.
     * @param string $invoiced The last day invoicing has settled.
     * @param string $closed The last day a billing may no longer be moved behind.
     * @return void
     * @link \App\Model\Table\BillingsTable::lastInvoicedPeriodEnd()
     * @link \App\Model\Table\BillingsTable::lastClosedPeriodEnd()
     */
    #[DataProvider('boundaries')]
    public function testWhereTheLineFalls(
        string $schedule,
        bool $dayStaysOpen,
        string $today,
        string $invoiced,
        string $closed,
    ): void {
        Settings::set(InvoicingSchedule::SETTINGS_PATH, $schedule);
        Settings::set(BillingsTable::DAY_STAYS_OPEN, $dayStaysOpen);

        $was = Chronos::getTestNow();
        Chronos::setTestNow(new Chronos($today . ' 09:00:00'));

        try {
            $this->assertSame($invoiced, $this->Billings->lastInvoicedPeriodEnd()->toDateString());
            $this->assertSame($closed, $this->Billings->lastClosedPeriodEnd()->toDateString());
            $this->assertSame(
                (new Date($closed))->addDays(1)->toDateString(),
                $this->Billings->firstOpenPeriodStart()->toDateString(),
                'A day belongs to neither period, or to both.',
            );
        } finally {
            Chronos::setTestNow($was);
        }
    }

    /**
     * A billing of a single day is a real one, so it is the day before the start that is refused -
     * the same reading `ImpossibleBillingPeriodCheck` gives it after the fact.
     *
     * @return void
     * @link \App\Model\Table\BillingsTable::buildRules()
     */
    public function testTheEndMayNotComeBeforeTheStart(): void
    {
        $billing = $this->openBilling();
        $day = $billing->billing_from;

        $this->assertNotFalse(
            $this->Billings->save(
                $this->Billings->patchEntity($billing, ['billing_until' => $day->toDateString()]),
            ),
            'A billing lasting one day was refused.',
        );

        $refused = $this->Billings->patchEntity(
            $this->Billings->get($billing->id),
            ['billing_until' => $day->subDays(1)->toDateString()],
        );

        $this->assertFalse($this->Billings->save($refused));
        $this->assertArrayHasKey('billingPeriodIsPossible', $refused->getError('billing_until'));
    }

    /**
     * The record would stop saying what was invoiced, even though no invoice changes.
     *
     * @return void
     * @link \App\Model\Table\BillingsTable::buildRules()
     */
    public function testAStartThatHasBeenInvoicedForCannotBeMoved(): void
    {
        $billing = $this->Billings->patchEntity(
            $this->Billings->get($this->closedBillingId()),
            ['billing_from' => $this->Billings->firstOpenPeriodStart()->toDateString()],
        );

        $this->assertFalse($this->Billings->save($billing));
        $this->assertArrayHasKey('billingStartsInAnOpenPeriod', $billing->getError('billing_from'));
    }

    /**
     * The other way round, which lays a charge on months whose invoices have already gone out.
     *
     * @return void
     * @link \App\Model\Table\BillingsTable::buildRules()
     */
    public function testAStartCannotBeMovedBackIntoAnInvoicedPeriod(): void
    {
        $billing = $this->Billings->patchEntity(
            $this->openBilling(),
            ['billing_from' => $this->Billings->lastClosedPeriodEnd()->toDateString()],
        );

        $this->assertFalse($this->Billings->save($billing));
        $this->assertArrayHasKey('billingStartsInAnOpenPeriod', $billing->getError('billing_from'));
    }

    /**
     * Entering one late is the same act as back-dating one that was already there.
     *
     * @return void
     * @link \App\Model\Table\BillingsTable::buildRules()
     */
    public function testANewBillingMayNotStartInAnInvoicedPeriod(): void
    {
        $billing = $this->newBillingFrom($this->Billings->lastClosedPeriodEnd());

        $this->assertFalse($this->Billings->save($billing));
        $this->assertArrayHasKey('billingStartsInAnOpenPeriod', $billing->getError('billing_from'));
    }

    /**
     * The last day invoiced is still allowed: ending there charges exactly the months that were
     * charged. A day earlier takes part of an invoice back.
     *
     * @return void
     * @link \App\Model\Table\BillingsTable::buildRules()
     */
    public function testTheEndMayNotBeMovedBeforeWhatWasInvoiced(): void
    {
        $closed = $this->Billings->lastClosedPeriodEnd();
        $running = $this->runningBillingId();

        $this->assertNotFalse(
            $this->Billings->save(
                $this->Billings->patchEntity(
                    $this->Billings->get($running),
                    ['billing_until' => $closed->toDateString()],
                ),
            ),
            'Ending a billing on the last day invoiced was refused.',
        );

        $refused = $this->Billings->patchEntity(
            $this->Billings->get($running),
            ['billing_until' => $closed->subDays(1)->toDateString()],
        );

        $this->assertFalse($this->Billings->save($refused));
        $this->assertArrayHasKey('billingEndsAfterWhatWasInvoiced', $refused->getError('billing_until'));
    }

    /**
     * Lifting it charges for months the invoices went out without, so an end lying behind the line
     * is not to be moved in either direction.
     *
     * @return void
     * @link \App\Model\Table\BillingsTable::buildRules()
     */
    public function testAnEndThatHasBeenInvoicedPastCannotBeLifted(): void
    {
        $billing = $this->Billings->patchEntity(
            $this->Billings->get($this->closedBillingId()),
            ['billing_until' => null],
        );

        $this->assertFalse($this->Billings->save($billing));
        $this->assertArrayHasKey('billingEndsAfterWhatWasInvoiced', $billing->getError('billing_until'));
    }

    /**
     * Which is what the box on the form asks for, and why only an admin is offered it.
     *
     * @return void
     * @link \App\Model\Table\BillingsTable::buildRules()
     */
    public function testTheInvoicedPeriodCanBeReachedIntoWhenItIsAskedFor(): void
    {
        $billing = $this->Billings->patchEntity(
            $this->Billings->get($this->runningBillingId()),
            ['billing_from' => $this->Billings->firstOpenPeriodStart()->toDateString()],
        );

        $this->assertNotFalse(
            $this->Billings->save($billing, [BillingsTable::ALLOW_CLOSED_PERIODS => true]),
        );
    }

    /**
     * None of this reaches a billing whose dates are all still ahead of the invoicing.
     *
     * @return void
     * @link \App\Model\Table\BillingsTable::buildRules()
     */
    public function testAChangeInsideTheOpenPeriodIsLeftAlone(): void
    {
        $open = $this->Billings->firstOpenPeriodStart();

        $billing = $this->Billings->patchEntity($this->openBilling(), [
            'billing_from' => $open->addDays(3)->toDateString(),
            'billing_until' => $open->addMonths(2)->toDateString(),
        ]);

        $this->assertNotFalse($this->Billings->save($billing));
    }

    /**
     * What may be taken back and what may not, which the permissions ask before they draw the
     * button and again before they let the request through.
     *
     * @return void
     * @link \App\Model\Table\BillingsTable::mayBeDeleted()
     */
    public function testOnlyABillingInvoicingHasNotReachedMayBeTakenBack(): void
    {
        $open = $this->Billings->firstOpenPeriodStart();

        $this->assertTrue(
            $this->Billings->mayBeDeleted($this->newBillingFrom($open)),
            'A billing nobody has been invoiced for could not be taken back.',
        );
        $this->assertFalse(
            $this->Billings->mayBeDeleted($this->newBillingFrom($open->subDays(1))),
            'A billing starting in a period already invoiced was taken back.',
        );
        $this->assertFalse(
            $this->Billings->mayBeDeleted($this->Billings->get($this->closedBillingId())),
            'A billing from years back was taken back.',
        );
    }

    /**
     * A billing that starts in the period nobody has been invoiced for yet.
     *
     * It has to be made here rather than in the fixture: where the line falls depends on the day
     * the test is run on, which a fixture cannot know.
     *
     * @return \App\Model\Entity\Billing
     */
    private function openBilling(): Billing
    {
        return $this->Billings->saveOrFail($this->newBillingFrom($this->Billings->firstOpenPeriodStart()));
    }

    /**
     * A billing on the fixture's contract, starting on the given day.
     *
     * @param \Cake\I18n\Date $from The day billing starts.
     * @return \App\Model\Entity\Billing
     */
    private function newBillingFrom(Date $from): Billing
    {
        $existing = $this->Billings->get($this->closedBillingId());

        return $this->Billings->newEntity([
            'customer_id' => $existing->customer_id,
            'contract_id' => $existing->contract_id,
            'text' => 'Billed from ' . $from->toDateString(),
            'quantity' => 1,
            'separate_invoice' => false,
            'billing_from' => $from->toDateString(),
        ]);
    }

    /**
     * A billing from the fixture, which begins and ends in years nobody can still invoice for.
     *
     * @return string
     */
    private function closedBillingId(): string
    {
        return (string)$this->Billings
            ->find()
            ->where(['Billings.billing_until IS NOT' => null])
            ->firstOrFail()
            ->get('id');
    }

    /**
     * A billing from the fixture that started years ago and has not been given an end.
     *
     * Where the start is behind the line and the end is not, so a rule about one of them is not
     * answered first by a rule about the other.
     *
     * @return string
     */
    private function runningBillingId(): string
    {
        return (string)$this->Billings
            ->find()
            ->where(['Billings.billing_until IS' => null])
            ->firstOrFail()
            ->get('id');
    }
}
