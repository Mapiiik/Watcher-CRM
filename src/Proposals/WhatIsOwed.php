<?php
declare(strict_types=1);

namespace App\Proposals;

use App\Contracts\Proposal\ProposalDocumentTypes;
use App\Model\Entity\ContractProposal;
use App\Model\Entity\CustomerProposal;
use App\Service\ContractPrint\ContractDocuments;
use App\Service\CustomerPrint\CustomerDocuments;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Which papers a proposal still owes, and which of them it has to.
 *
 * Both agendas already knew this and neither said it out loud: a contract's papers follow rules
 * written as what would be nonsense, so whatever is left really is meant to exist, and a proposal put
 * to the customer has a purpose that already picks the one paper that applies.
 *
 * Asked here in one place so that the table which draws them does not have to know which agenda it
 * is looking at.
 */
final class WhatIsOwed
{
    use LocatorAwareTrait;

    /**
     * The papers this proposal is meant to have, and whether each of them has to exist.
     *
     * @param \App\Model\Entity\ContractProposal|\App\Model\Entity\CustomerProposal $proposal The proposal.
     * @return array<string, bool> The document type, and whether its absence is a gap.
     */
    public function of(ContractProposal|CustomerProposal $proposal): array
    {
        if ($proposal->hasBeenRevoked()) {
            // Nothing is coming for a proposal that was given up on, so nothing is owed.
            return [];
        }

        return $proposal instanceof ContractProposal
            ? (new ProposalDocumentTypes())->expectedOf($proposal)
            : $this->ofTheCustomers($proposal);
    }

    /**
     * Whether the proposal has any paper of its own at all.
     *
     * A proposal put to the customer that asks nothing of them is there to hold the papers of their
     * contracts, and holds none itself - so there is nothing of its own to draw, nothing that went
     * out, and nothing that can come back. Different from owing nothing, which is what a proposal
     * whose papers are all drawn already says.
     *
     * @param \App\Model\Entity\ContractProposal|\App\Model\Entity\CustomerProposal $proposal The proposal.
     * @return bool
     */
    public function mayHoldPapers(ContractProposal|CustomerProposal $proposal): bool
    {
        return $proposal instanceof ContractProposal
            ? (new ProposalDocumentTypes())->options($proposal) !== []
            : $proposal->purpose !== null;
    }

    /**
     * What to call each of them.
     *
     * @param \App\Model\Entity\ContractProposal|\App\Model\Entity\CustomerProposal $proposal The proposal.
     * @return array<string, string>
     */
    public function labels(ContractProposal|CustomerProposal $proposal): array
    {
        return $proposal instanceof ContractProposal
            ? (new ContractDocuments())->documentLabels()
            : (new CustomerDocuments())->documentLabels();
    }

    /**
     * The one paper a proposal put to the customer is for.
     *
     * A consent asked of somebody who has agreed before is a different paper from one asked of
     * somebody who never has, and only the earlier proposals know which it is - so the purpose is
     * asked the same question the printing form used to ask it.
     *
     * @param \App\Model\Entity\CustomerProposal $proposal The proposal.
     * @return array<string, bool>
     */
    private function ofTheCustomers(CustomerProposal $proposal): array
    {
        if ($proposal->purpose === null) {
            // A proposal that only holds its contracts' papers asks nothing of the customer.
            return [];
        }

        return [$proposal->purpose->suggests($this->hasAgreedBefore($proposal))->value => true];
    }

    /**
     * Whether an earlier proposal of the same kind was ever signed.
     *
     * @param \App\Model\Entity\CustomerProposal $proposal The proposal.
     * @return bool
     */
    private function hasAgreedBefore(CustomerProposal $proposal): bool
    {
        return $this->fetchTable('CustomerProposals')
            ->find()
            ->where([
                'CustomerProposals.customer_id' => $proposal->customer_id,
                'CustomerProposals.id !=' => $proposal->id,
                'CustomerProposals.purpose' => $proposal->purpose?->value,
                'CustomerProposals.conclusion_date IS NOT' => null,
            ])
            ->count() > 0;
    }
}
