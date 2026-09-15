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
 * Which papers a round still owes, and which of them it has to.
 *
 * Both agendas already knew this and neither said it out loud: a contract's papers follow rules
 * written as what would be nonsense, so whatever is left really is meant to exist, and a round put
 * to the customer has a purpose that already picks the one paper that applies.
 *
 * Asked here in one place so that the table which draws them does not have to know which agenda it
 * is looking at.
 */
final class WhatIsOwed
{
    use LocatorAwareTrait;

    /**
     * The papers this round is meant to have, and whether each of them has to exist.
     *
     * @param \App\Model\Entity\ContractProposal|\App\Model\Entity\CustomerProposal $round The round.
     * @return array<string, bool> The document type, and whether its absence is a gap.
     */
    public function of(ContractProposal|CustomerProposal $round): array
    {
        if ($round->hasBeenRevoked()) {
            // Nothing is coming for a round that was given up on, so nothing is owed.
            return [];
        }

        return $round instanceof ContractProposal
            ? (new ProposalDocumentTypes())->expectedOf($round)
            : $this->ofTheCustomers($round);
    }

    /**
     * What to call each of them.
     *
     * @param \App\Model\Entity\ContractProposal|\App\Model\Entity\CustomerProposal $round The round.
     * @return array<string, string>
     */
    public function labels(ContractProposal|CustomerProposal $round): array
    {
        return $round instanceof ContractProposal
            ? (new ContractDocuments())->documentLabels()
            : (new CustomerDocuments())->documentLabels();
    }

    /**
     * The one paper a round put to the customer is for.
     *
     * A consent asked of somebody who has agreed before is a different paper from one asked of
     * somebody who never has, and only the earlier rounds know which it is - so the purpose is
     * asked the same question the printing form used to ask it.
     *
     * @param \App\Model\Entity\CustomerProposal $round The round.
     * @return array<string, bool>
     */
    private function ofTheCustomers(CustomerProposal $round): array
    {
        if ($round->purpose === null) {
            // A round that only holds its contracts' papers asks nothing of the customer.
            return [];
        }

        return [$round->purpose->suggests($this->hasAgreedBefore($round))->value => true];
    }

    /**
     * Whether an earlier round of the same kind was ever signed.
     *
     * @param \App\Model\Entity\CustomerProposal $round The round.
     * @return bool
     */
    private function hasAgreedBefore(CustomerProposal $round): bool
    {
        return $this->fetchTable('CustomerProposals')
            ->find()
            ->where([
                'CustomerProposals.customer_id' => $round->customer_id,
                'CustomerProposals.id !=' => $round->id,
                'CustomerProposals.purpose' => $round->purpose?->value,
                'CustomerProposals.conclusion_date IS NOT' => null,
            ])
            ->count() > 0;
    }
}
