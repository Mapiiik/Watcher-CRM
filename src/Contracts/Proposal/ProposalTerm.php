<?php
declare(strict_types=1);

namespace App\Contracts\Proposal;

use App\Model\Entity\ContractProposal;
use Cake\I18n\Date;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Until when the papers leave anything to be billed for, and the lines that stop it there.
 */
final class ProposalTerm
{
    use LocatorAwareTrait;

    /**
     * Stops everything the contract is billed for, where the papers say when it has to stop.
     *
     * An ending means nothing goes on being charged for, and a fixed term means nothing goes on
     * past it, so the lines that say so are put there rather than clicked one at a time. They are
     * the proposal's own lines, no different from any other, so anything that is to run on can be
     * taken back off the proposal's table.
     *
     * A version ending while the contract runs on gets none of this: what is billed for hangs off
     * the contract, which carries on, and the version that follows says what becomes of it.
     *
     * What is still billed for is read off the snapshot, so this is asked only of a proposal that
     * has one - which a half-filled form being drawn again has not.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal being drawn up.
     * @param \App\Contracts\Proposal\ProposalSnapshot|null $before The snapshot this one replaces.
     *   What already ran on in it had its line then, so a line missing now was taken back.
     * @return void
     */
    public function endWhatIsBilledFor(ContractProposal $proposal, ?ProposalSnapshot $before = null): void
    {
        $lastDay = $this->lastDayBilledFor($proposal);

        if ($lastDay === null) {
            return;
        }

        // An ending stops on the day the proposal takes effect, a fixed term on a day of its own.
        $stopsOn = $proposal->purpose->ends() ? null : $lastDay->addDays(1);
        $asked = $proposal->proposedChanges();
        $changes = $asked;
        $spokenFor = $asked->billingsByBillingId();
        $known = $before?->billings() ?? [];
        $lines = new ProposedBillingForm();

        foreach ($proposal->stateOfThings()->billings() as $billing_id => $billing) {
            $id = (string)$billing_id;

            if (
                !isset($spokenFor[$id])
                && $this->runsPast($billing, $lastDay)
                && !(isset($known[$id]) && $this->runsPast($known[$id], $lastDay))
            ) {
                $changes = $changes->withLine($lines->ending($id, $stopsOn));
            }
        }

        if ($changes !== $asked) {
            $proposal->set('changes', $changes->toArray());
        }
    }

    /**
     * The last day the papers leave anything to be billed for, where they say one.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @return \Cake\I18n\Date|null
     */
    private function lastDayBilledFor(ContractProposal $proposal): ?Date
    {
        if ($proposal->purpose->ends()) {
            return $proposal->endsTheContract() ? $proposal->dayBeforeItTakesEffect() : null;
        }

        if (!$proposal->purpose->mayChangeTheTerm()) {
            return null;
        }

        /** @var \App\Model\Table\ContractProposalsTable $proposals */
        $proposals = $this->fetchTable('ContractProposals');

        return $proposals->lastDayOfARunningTerm($proposal);
    }

    /**
     * Whether a billing the snapshot took down is still billed for after the given day.
     *
     * A billing that stops by then is left alone. Stopped years ago it has nothing left to end, and
     * stopping sooner than a fixed term is somebody's decision: a tariff followed by another looks
     * the same.
     *
     * @param array<string, mixed> $billing The billing as the snapshot holds it.
     * @param \Cake\I18n\Date $day The day.
     * @return bool
     */
    private function runsPast(array $billing, Date $day): bool
    {
        $stops = $billing['billing_until'] ?? null;

        return $stops === null || new Date((string)$stops) > $day;
    }
}
