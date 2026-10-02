<?php
declare(strict_types=1);

namespace App\Proposals;

use App\Model\Entity\ContractProposal;
use App\Model\Entity\CustomerProposal;

/**
 * Proposals as the table of proposals reads them, one row each.
 *
 * The workbench, the register and the cards all draw that one table, so the rows are put together
 * here once rather than by each page that shows them.
 */
final class ProposalRows
{
    /**
     * The proposals, a row each.
     *
     * @param iterable<\Cake\Datasource\EntityInterface> $proposals The ones in view.
     * @return array<array<string, mixed>>
     */
    public function of(iterable $proposals): array
    {
        $listed = [];

        foreach ($proposals as $proposal) {
            /** @var \App\Model\Entity\CustomerProposal $proposal */
            $listed[] = $this->row($proposal);
        }

        return $listed;
    }

    /**
     * One proposal, as a listing wants it.
     *
     * @param \App\Model\Entity\ContractProposal|\App\Model\Entity\CustomerProposal $proposal The proposal.
     * @return array<string, mixed>
     */
    private function row(ContractProposal|CustomerProposal $proposal): array
    {
        $ofAContract = $proposal instanceof ContractProposal;

        return [
            'id' => (string)$proposal->id,
            'agenda' => $ofAContract ? 'ContractProposals' : 'CustomerProposals',
            'proposal' => $proposal,
            'customer' => $ofAContract
                ? ($proposal->contract->customer ?? null)
                : ($proposal->customer ?? null),
            'contract' => $ofAContract ? ($proposal->contract ?? null) : null,
            'covers' => $ofAContract ? [] : $this->contractsCovered($proposal),
            'version' => $ofAContract ? ($proposal->contract_version ?? null) : null,
            'purpose' => $ofAContract ? $proposal->purpose->label() : $proposal->whatItIsFor(),
        ];
    }

    /**
     * Which contracts a proposal says something about, by their numbers.
     *
     * @param \App\Model\Entity\CustomerProposal $proposal The proposal.
     * @return array<string>
     */
    private function contractsCovered(CustomerProposal $proposal): array
    {
        $covered = [];

        foreach ($proposal->contract_proposals ?? [] as $papers) {
            $number = (string)($papers->contract->number ?? '');

            if ($number === '') {
                continue;
            }

            // The number says which contract, the day and the purpose what is asked of it and
            // from when. Together they are the whole of what a part amounts to, which is why the
            // parts are not listed a second time underneath - and the day is the part's own,
            // which need not be the day the proposal speaks from.
            $said = __(
                '{0} ({1} - {2})',
                $number,
                (string)$papers->effective_from,
                $papers->purpose->label(),
            );
            $covered[$said] = $said;
        }

        sort($covered);

        return $covered;
    }
}
