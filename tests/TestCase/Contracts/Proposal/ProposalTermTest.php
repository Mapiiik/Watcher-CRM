<?php
declare(strict_types=1);

namespace App\Test\TestCase\Contracts\Proposal;

use App\Contracts\Proposal\ProposalSnapshot;
use App\Contracts\Proposal\ProposalTerm;
use App\Contracts\Proposal\ProposedBilling;
use App\Model\Entity\ContractProposal;
use App\Model\Enum\ProposalPurpose;
use Cake\I18n\Date;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\Contracts\Proposal\ProposalTerm Test Case
 */
#[CoversClass(ProposalTerm::class)]
class ProposalTermTest extends TestCase
{
    /**
     * Papers on a contract, with what it was billed for when they were drawn up.
     *
     * @param \App\Model\Enum\ProposalPurpose $purpose What they are for.
     * @param array<array<string, mixed>> $billings The billings the snapshot took down.
     * @param array<string, mixed> $changes What they ask for.
     * @return \App\Model\Entity\ContractProposal
     */
    private function papers(ProposalPurpose $purpose, array $billings, array $changes = []): ContractProposal
    {
        return new ContractProposal([
            'purpose' => $purpose,
            'effective_from' => new Date('2027-01-01'),
            'snapshot' => $this->snapshot($billings),
            'changes' => $changes,
        ]);
    }

    /**
     * @param array<array<string, mixed>> $billings The billings the snapshot took down.
     * @return array<string, mixed>
     */
    private function snapshot(array $billings): array
    {
        return ['contract' => [], 'customer' => [], 'version' => [], 'billings' => $billings];
    }

    /**
     * @param \App\Model\Entity\ContractProposal $proposal The papers.
     * @return array<string, \App\Contracts\Proposal\ProposedBilling> What ends what, by the billing.
     */
    private function lines(ContractProposal $proposal): array
    {
        return $proposal->proposedChanges()->billingsByBillingId();
    }

    /**
     * An ending stops whatever is still billed for on the day it takes effect, and leaves what
     * stopped before then alone.
     *
     * @return void
     */
    public function testAnEndingStopsWhatIsStillBilled(): void
    {
        $proposal = $this->papers(
            ProposalPurpose::Termination,
            [
                ['id' => 'running', 'billing_until' => null],
                ['id' => 'runs-on', 'billing_until' => '2027-06-30'],
                ['id' => 'stopped', 'billing_until' => '2026-06-30'],
            ],
            ['contract' => ['termination_date' => '2026-12-31']],
        );

        (new ProposalTerm())->endWhatIsBilledFor($proposal);

        $lines = $this->lines($proposal);
        $this->assertSame(['running', 'runs-on'], array_keys($lines));
        $this->assertContainsOnlyInstancesOf(ProposedBilling::class, $lines);
        $this->assertTrue($lines['running']->terminatesOnly());
        $this->assertNull($lines['running']->billing_from, 'An ending stops on the day it takes effect.');
    }

    /**
     * A version ending while the contract runs on stops nothing: what is billed for hangs off the
     * contract.
     *
     * @return void
     */
    public function testAVersionEndingWhileTheContractRunsOnStopsNothing(): void
    {
        $proposal = $this->papers(
            ProposalPurpose::Termination,
            [['id' => 'running', 'billing_until' => null]],
            ['version' => ['valid_until' => '2026-12-31']],
        );

        (new ProposalTerm())->endWhatIsBilledFor($proposal);

        $this->assertSame([], $this->lines($proposal));
    }

    /**
     * A fixed term stops what would run past it the day after it ends, and a billing that ends
     * sooner is somebody's decision.
     *
     * @return void
     */
    public function testAFixedTermStopsWhatWouldRunPastIt(): void
    {
        $proposal = $this->papers(
            ProposalPurpose::NewContract,
            [
                ['id' => 'running', 'billing_until' => null],
                ['id' => 'longer', 'billing_until' => '2100-06-30'],
                ['id' => 'sooner', 'billing_until' => '2099-06-30'],
            ],
            ['version' => ['valid_until' => '2099-12-31']],
        );

        (new ProposalTerm())->endWhatIsBilledFor($proposal);

        $lines = $this->lines($proposal);
        $this->assertSame(['running', 'longer'], array_keys($lines));
        $this->assertSame('2100-01-01', $lines['running']->billing_from?->toDateString());
    }

    /**
     * What the proposal already says something about is not spoken for a second time.
     *
     * @return void
     */
    public function testALineAlreadyThereStands(): void
    {
        $proposal = $this->papers(
            ProposalPurpose::NewContract,
            [['id' => 'running', 'billing_until' => null]],
            [
                'version' => ['valid_until' => '2099-12-31'],
                'billings' => [['billing_id' => 'running', 'percentage_discount' => 50]],
            ],
        );

        (new ProposalTerm())->endWhatIsBilledFor($proposal);

        $lines = $this->lines($proposal);
        $this->assertCount(1, $lines);
        $this->assertFalse($lines['running']->terminatesOnly());
    }

    /**
     * Drawn up again, a billing that ran on before had its line then. A line missing now was taken
     * back and is not put there again, while what started to run on only now gets one.
     *
     * @return void
     */
    public function testALineTakenBackIsNotPutThereAgain(): void
    {
        $before = ProposalSnapshot::fromArray($this->snapshot([
            ['id' => 'taken-back', 'billing_until' => null],
            ['id' => 'moved', 'billing_until' => '2099-06-30'],
        ]));

        $proposal = $this->papers(
            ProposalPurpose::NewContract,
            [
                ['id' => 'taken-back', 'billing_until' => null],
                ['id' => 'moved', 'billing_until' => null],
            ],
            ['version' => ['valid_until' => '2099-12-31']],
        );

        (new ProposalTerm())->endWhatIsBilledFor($proposal, $before);

        $this->assertSame(['moved'], array_keys($this->lines($proposal)));
    }
}
