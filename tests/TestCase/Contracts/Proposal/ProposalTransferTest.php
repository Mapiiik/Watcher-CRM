<?php
declare(strict_types=1);

namespace App\Test\TestCase\Contracts\Proposal;

use App\Contracts\Proposal\ProposalTransfer;
use App\Model\Entity\ContractVersionProposal;
use App\Test\Traits\TableTestTrait;
use Cake\I18n\Date;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;

/**
 * App\Contracts\Proposal\ProposalTransfer Test Case
 */
#[CoversClass(ProposalTransfer::class)]
class ProposalTransferTest extends TestCase
{
    use TableTestTrait;

    /**
     * The contract everything hangs off.
     */
    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

    /**
     * A concluded version of it.
     */
    private const VERSION_ID = '74824fba-20b2-46fc-806c-df795aa9e429';

    /**
     * The proposal the fixture carries: open, unsent, changing nothing.
     */
    private const PROPOSAL_ID = 'c9a1f2b3-4d5e-4f60-8a71-9b2c3d4e5f60';

    /**
     * A billing that runs on, which the fixture snapshot knows.
     */
    private const OPEN_BILLING_ID = 'b2000000-0000-4000-8000-000000000002';

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
        'app.ContractVersions',
        'app.Queues',
        'app.Services',
        'app.Billings',
        'app.ContractVersionProposals',
        'plugin.Settings.Settings',
    ];

    /**
     * The proposal, with what the test wants it to say.
     *
     * @param array<string, mixed> $says What it says.
     * @return \App\Model\Entity\ContractVersionProposal
     */
    private function proposal(array $says = []): ContractVersionProposal
    {
        $proposals = $this->getTableLocator()->get('ContractVersionProposals');
        $proposal = $proposals->get(self::PROPOSAL_ID);

        if ($says !== []) {
            $proposals->saveOrFail(
                $proposals->patchEntity($proposal, $says),
                ['checkRules' => false],
            );
        }

        return $proposals->get(self::PROPOSAL_ID);
    }

    /**
     * How many billings the contract has.
     *
     * @return int
     */
    private function billingCount(): int
    {
        return $this->getTableLocator()->get('Billings')
            ->find()
            ->where(['Billings.contract_id' => self::CONTRACT_ID])
            ->count();
    }

    /**
     * The ordinary proposal behind a new contract's papers changes nothing, and is carried over all
     * the same - otherwise it would sit in the checks for ever as signed and not dealt with.
     *
     * @return void
     */
    public function testAProposalThatChangesNothingIsStillCarriedOver(): void
    {
        $proposal = $this->proposal(['conclusion_date' => '2026-09-15']);
        $before = $this->billingCount();

        (new ProposalTransfer())->carryOver($proposal);

        $this->assertTrue(
            $this->getTableLocator()->get('ContractVersionProposals')
                ->get(self::PROPOSAL_ID)
                ->hasBeenApplied(),
        );
        $this->assertSame($before, $this->billingCount(), 'A proposal that asks for nothing wrote.');
    }

    /**
     * The transfer refuses what nobody has signed. The table refuses it too, but the service is
     * asked first, so it says so in its own words.
     *
     * @return void
     */
    public function testNothingIsCarriedOverBeforeItIsConcluded(): void
    {
        $this->expectException(RuntimeException::class);

        (new ProposalTransfer())->carryOver($this->proposal());
    }

    /**
     * And what has already been settled one way or the other.
     *
     * @return void
     */
    public function testASettledProposalIsNotCarriedOverAgain(): void
    {
        $proposal = $this->proposal([
            'conclusion_date' => '2026-09-15',
            'revoked' => '2026-09-16 10:00:00',
        ]);

        $this->expectException(RuntimeException::class);

        (new ProposalTransfer())->carryOver($proposal);
    }

    /**
     * A replaced billing stops the day before its replacement starts - the two halves the preview
     * showed, written as one act.
     *
     * @return void
     */
    public function testAReplacedBillingIsEndedAndItsReplacementStarted(): void
    {
        $proposal = $this->proposal([
            'conclusion_date' => '2026-09-15',
            'effective_from' => '2026-10-01',
            'changes' => ['billings' => [[
                'billing_id' => self::OPEN_BILLING_ID,
                'terminates_only' => false,
                'service_id' => 'eaacfeb3-1430-43ce-842e-497c5c95d953',
                'quantity' => 1,
                'price' => '299.00',
            ]]],
        ]);

        (new ProposalTransfer())->carryOver($proposal);

        $billings = $this->getTableLocator()->get('Billings');

        $ended = $billings->get(self::OPEN_BILLING_ID);
        $this->assertSame('2026-09-30', $ended->billing_until?->toDateString());

        $started = $billings->find()
            ->where([
                'Billings.contract_id' => self::CONTRACT_ID,
                'Billings.billing_from' => '2026-10-01',
            ])
            ->firstOrFail();
        $this->assertSame('299.00', $started->get('price')?->toString());
        $this->assertSame('eaacfeb3-1430-43ce-842e-497c5c95d953', $started->get('service_id'));
    }

    /**
     * What the proposal asks of the version and of the contract is written too, but the state of
     * the contract is left alone - it has its own requirements to satisfy.
     *
     * @return void
     */
    public function testTheVersionAndTheContractTakeWhatIsAskedOfThem(): void
    {
        $contracts = $this->getTableLocator()->get('Contracts');
        $stateBefore = $contracts->get(self::CONTRACT_ID)->get('contract_state_id');

        $proposal = $this->proposal([
            'conclusion_date' => '2026-09-15',
            'effective_from' => '2026-10-01',
            'terminated_contract_number' => 'Lorem ipsum dolor sit amet',
            'changes' => [
                'version' => [
                    'valid_until' => '2026-09-30',
                    'obligation_until' => '2026-09-30',
                ],
                'contract' => ['termination_date' => '2026-09-30'],
            ],
        ]);

        (new ProposalTransfer())->carryOver($proposal);

        $version = $this->getTableLocator()->get('ContractVersions')->get(self::VERSION_ID);
        $this->assertSame('2026-09-30', $version->valid_until?->toDateString());

        $contract = $contracts->get(self::CONTRACT_ID);
        $this->assertSame('2026-09-30', $contract->get('termination_date')?->toDateString());
        $this->assertSame($stateBefore, $contract->get('contract_state_id'));
    }

    /**
     * When one part of the transfer will not go through, none of it does. Half a carried-over
     * proposal is worse than none: the paper would describe one thing and the records another.
     *
     * @return void
     */
    public function testNothingIsWrittenWhenAnyOfItFails(): void
    {
        // A day inside a period that has been invoiced for, which the billings table refuses.
        $proposal = $this->proposal([
            'conclusion_date' => '2023-01-15',
            'effective_from' => '2023-02-01',
            'changes' => ['billings' => [[
                'billing_id' => self::OPEN_BILLING_ID,
                'terminates_only' => false,
                'service_id' => 'eaacfeb3-1430-43ce-842e-497c5c95d953',
                'quantity' => 1,
            ]]],
        ]);

        $before = $this->billingCount();

        try {
            (new ProposalTransfer())->carryOver($proposal);
            $this->fail('The transfer wrote into an invoiced period.');
        } catch (RuntimeException) {
            // what it says is the billings table's business; that it wrote nothing is ours
        }

        $this->assertSame($before, $this->billingCount());
        $this->assertNull(
            $this->getTableLocator()->get('Billings')->get(self::OPEN_BILLING_ID)->billing_until,
        );
        $this->assertFalse(
            $this->getTableLocator()->get('ContractVersionProposals')
                ->get(self::PROPOSAL_ID)
                ->hasBeenApplied(),
        );
    }

    /**
     * An administrator who says so may write into an invoiced period, the same as on a service
     * change.
     *
     * @return void
     */
    public function testAnInvoicedPeriodMayBeReachedIntoDeliberately(): void
    {
        $proposal = $this->proposal([
            'conclusion_date' => '2023-01-15',
            'effective_from' => '2023-02-01',
            'changes' => ['billings' => [[
                'billing_id' => self::OPEN_BILLING_ID,
                'terminates_only' => true,
            ]]],
        ]);

        (new ProposalTransfer())->carryOver($proposal, null, reach_into_closed_periods: true);

        $this->assertSame(
            '2023-01-31',
            $this->getTableLocator()->get('Billings')
                ->get(self::OPEN_BILLING_ID)
                ->billing_until?->toDateString(),
        );
    }

    /**
     * The day the replacement starts is the proposal's own, not today's.
     *
     * @return void
     */
    public function testTheDayItTakesEffectIsTheProposals(): void
    {
        $proposal = $this->proposal([
            'conclusion_date' => '2026-09-15',
            'effective_from' => '2026-11-01',
            'changes' => ['billings' => [[
                'billing_id' => self::OPEN_BILLING_ID,
                'terminates_only' => true,
            ]]],
        ]);

        (new ProposalTransfer())->carryOver($proposal);

        $this->assertSame(
            (new Date('2026-10-31'))->toDateString(),
            $this->getTableLocator()->get('Billings')
                ->get(self::OPEN_BILLING_ID)
                ->billing_until?->toDateString(),
        );
    }
}
