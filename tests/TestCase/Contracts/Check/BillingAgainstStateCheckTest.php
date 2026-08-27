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
        $this->stopTheServices();

        $this->assertCount(1, $this->chargedForNothing());
    }

    /**
     * Stopped, and the billing stopped with it - which is what stopping a contract properly
     * looks like. Lifting the filter is what finds it, because it happened once.
     *
     * @return void
     * @link \App\Contracts\Check\InactiveWithBillingCheck::find()
     */
    public function testABillingEndedWithTheContractIsOnlyReportedWhenTheFilterIsLifted(): void
    {
        $this->billed('-3 years', '-2 years');
        $this->stopTheServices();

        $this->assertSame([], $this->chargedForNothing());
        $this->assertCount(1, $this->chargedForNothing(ignore_inactive: false));
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
     * Bill the contract over a stretch of time, given relative to today.
     *
     * @param string $from When billing starts, as a modifier of today.
     * @param string|null $until When it ends, or null for an open end.
     * @return void
     */
    private function billed(string $from, ?string $until): void
    {
        $this->Billings->saveOrFail($this->Billings->newEntity([
            'customer_id' => self::CUSTOMER_ID,
            'contract_id' => self::CONTRACT_ID,
            'service_id' => self::SERVICE_ID,
            'billing_from' => Date::now()->modify($from)->format('Y-m-d'),
            'billing_until' => $until === null ? null : Date::now()->modify($until)->format('Y-m-d'),
            'quantity' => 1,
            'separate_invoice' => false,
        ]));
    }
}
