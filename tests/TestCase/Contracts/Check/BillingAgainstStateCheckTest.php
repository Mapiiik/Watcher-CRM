<?php
declare(strict_types=1);

namespace App\Test\TestCase\Contracts\Check;

use App\Contracts\Check\ActiveWithoutBillingCheck;
use App\Contracts\Check\InactiveWithBillingCheck;
use App\Model\Table\BillingsTable;
use App\Model\Table\ContractsTable;
use Cake\I18n\Date;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * The pair of checks that hold a contract's state against its billing.
 *
 * Either a service is being given away, or a stopped service is still being charged for. They
 * are two readings of the same disagreement and are tested together because what makes one
 * fire is what makes the other stay quiet.
 */
#[UsesClass(ActiveWithoutBillingCheck::class)]
#[UsesClass(InactiveWithBillingCheck::class)]
class BillingAgainstStateCheckTest extends TestCase
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
    ];

    private BillingsTable $Billings;

    private ContractsTable $Contracts;

    /**
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->Billings = $this->getTableLocator()->get('Billings', ['className' => BillingsTable::class]);
        $this->Contracts = $this->getTableLocator()->get('Contracts', ['className' => ContractsTable::class]);

        // the fixtures leave every contract in a state that provides services
        $this->Billings->deleteAll(['1 = 1']);
    }

    /**
     * A service running with nothing billed for it is either given away, or stopped without
     * anybody saying so.
     *
     * @return void
     * @link \App\Contracts\Check\ActiveWithoutBillingCheck::find()
     */
    public function testAServiceNobodyIsChargedForIsReported(): void
    {
        $this->assertCount(1, $this->givenAwayService());
    }

    /**
     * @return void
     * @link \App\Contracts\Check\ActiveWithoutBillingCheck::find()
     */
    public function testAServiceThatIsBilledIsNotReported(): void
    {
        $this->billed('-1 year', null);

        $this->assertSame([], $this->givenAwayService());
    }

    /**
     * A billing that has ended is not billing today, which is what the state claims.
     *
     * @return void
     * @link \App\Contracts\Check\ActiveWithoutBillingCheck::find()
     */
    public function testABillingThatHasEndedDoesNotCountAsBilling(): void
    {
        $this->billed('-3 years', '-2 years');

        $this->assertCount(1, $this->givenAwayService());
    }

    /**
     * There is no longer history to fall back on here - not billed now is the whole of it -
     * so a page that lifts the filter to see everything about a contract has to keep being
     * shown this, rather than a narrower question about whether it was ever billed at all.
     *
     * @return void
     * @link \App\Contracts\Check\ActiveWithoutBillingCheck::hasAWiderReading()
     */
    public function testLiftingTheFilterLeavesItSayingTheSame(): void
    {
        $this->billed('-3 years', '-2 years');

        $this->assertCount(1, $this->givenAwayService(ignore_inactive: false));
        $this->assertFalse((new ActiveWithoutBillingCheck($this->Contracts))->hasAWiderReading());
    }

    /**
     * The other way round: the contract provides nothing and the money keeps coming.
     *
     * @return void
     * @link \App\Contracts\Check\InactiveWithBillingCheck::find()
     */
    public function testAStoppedServiceStillBeingChargedForIsReported(): void
    {
        $this->billed('-1 year', null);
        $this->stoppedOn('-1 month');

        $this->assertCount(1, $this->chargedForNothing());
    }

    /**
     * Billing that runs on past the day the contract stopped is the whole of the fault, however
     * long ago it was and whether or not it is still running now.
     *
     * @return void
     * @link \App\Contracts\Check\InactiveWithBillingCheck::find()
     */
    public function testBillingThatOutlivedTheContractIsReportedLongAfterwards(): void
    {
        $this->billed('-3 years', '-1 year');
        $this->stoppedOn('-2 years');

        $this->assertCount(1, $this->chargedForNothing());
    }

    /**
     * A contract wound up properly - the billing ended on the very day the service was - is not
     * a finding. This is what was reported from production: contract 116969-5694 stopped on the
     * last day of July with its billing ended the same day, and its own page said it was still
     * being charged for.
     *
     * @return void
     * @link \App\Contracts\Check\InactiveWithBillingCheck::find()
     */
    public function testAContractWoundUpProperlyIsNotAFinding(): void
    {
        $this->billed('-3 years', '-1 month');
        $this->stoppedOn('-1 month');

        $this->assertSame([], $this->chargedForNothing());
    }

    /**
     * And it stays out of it whichever way the filter is set. A contract's own page lifts the
     * filter to see everything about it, and was being shown every terminated contract that had
     * ever been billed at all - which is nearly all of them.
     *
     * @return void
     * @link \App\Contracts\Check\InactiveWithBillingCheck::hasAWiderReading()
     */
    public function testLiftingTheFilterDoesNotTurnItIntoEveryStoppedContract(): void
    {
        $this->billed('-3 years', '-1 month');
        $this->stoppedOn('-1 month');

        $this->assertSame([], $this->chargedForNothing(ignore_inactive: false));
        $this->assertFalse((new InactiveWithBillingCheck($this->Contracts))->hasAWiderReading());
    }

    /**
     * The billing ends the day after the service did, which is a day charged for nothing.
     *
     * @return void
     * @link \App\Contracts\Check\InactiveWithBillingCheck::find()
     */
    public function testABillingOutlivingTheContractByOneDayIsReported(): void
    {
        $this->billed('-3 years', '-1 month +1 day');
        $this->stoppedOn('-1 month');

        $this->assertCount(1, $this->chargedForNothing());
    }

    /**
     * Stopped with no day recorded, so there is nothing to hold the billing against but today.
     *
     * @return void
     * @link \App\Contracts\Check\InactiveWithBillingCheck::find()
     */
    public function testWithNoDayRecordedTheBillingIsHeldAgainstToday(): void
    {
        $this->billed('-3 years', '-1 month');
        $this->stopTheServices();
        $this->Contracts->updateAll(['termination_date' => null], ['1 = 1']);

        $this->assertSame([], $this->chargedForNothing(), 'Billing already over is not still charging.');

        $this->billed('-1 month', null);

        $this->assertCount(1, $this->chargedForNothing());
    }

    /**
     * Services running with nothing billed for them, on the contract under test.
     *
     * The fixtures carry a second contract in the same state, so each check is asked about
     * the one the case set up rather than about both.
     *
     * @param bool $ignore_inactive Whether a billing that has ended still counts as billing.
     * @return list<\App\Model\Entity\Contract>
     */
    private function givenAwayService(bool $ignore_inactive = true): array
    {
        /** @var list<\App\Model\Entity\Contract> $records */
        $records = (new ActiveWithoutBillingCheck($this->Contracts, $ignore_inactive, self::CONTRACT_ID))
            ->find()
            ->all()
            ->toList();

        return $records;
    }

    /**
     * Contracts providing nothing and still being charged for, on the contract under test.
     *
     * @param bool $ignore_inactive Whether to keep to billings that are still running.
     * @return list<\App\Model\Entity\Contract>
     */
    private function chargedForNothing(bool $ignore_inactive = true): array
    {
        /** @var list<\App\Model\Entity\Contract> $records */
        $records = (new InactiveWithBillingCheck($this->Contracts, $ignore_inactive, self::CONTRACT_ID))
            ->find()
            ->all()
            ->toList();

        return $records;
    }

    /**
     * Put every contract into a state that provides nothing.
     *
     * @return void
     */
    private function stopTheServices(): void
    {
        $this->getTableLocator()->get('ContractStates')->updateAll(['active_services' => false], ['1 = 1']);
    }

    /**
     * Stop the services and say which day they stopped on.
     *
     * @param string $on The day, as a modifier of today.
     * @return void
     */
    private function stoppedOn(string $on): void
    {
        $this->stopTheServices();
        $this->Contracts->updateAll(
            ['termination_date' => Date::now()->modify($on)->format('Y-m-d')],
            ['id' => self::CONTRACT_ID],
        );
    }

    /**
     * Bill the contract over a stretch of time, given relative to today.
     *
     * @param string $from When billing starts, as a modifier of today.
     * @param string|null $until When it ends, or null for an open end.
     * @return void
     */
    private function billed(string $from, ?string $until): void
    {
        // dated as it would have come to be before the rules were there to refuse it, which
        // is the very thing the check is here to find
        $this->Billings->saveOrFail($this->Billings->newEntity([
            'customer_id' => self::CUSTOMER_ID,
            'contract_id' => self::CONTRACT_ID,
            'service_id' => self::SERVICE_ID,
            'billing_from' => Date::now()->modify($from)->format('Y-m-d'),
            'billing_until' => $until === null ? null : Date::now()->modify($until)->format('Y-m-d'),
            'quantity' => 1,
            'separate_invoice' => false,
        ]), [BillingsTable::ALLOW_CLOSED_PERIODS => true]);
    }
}
