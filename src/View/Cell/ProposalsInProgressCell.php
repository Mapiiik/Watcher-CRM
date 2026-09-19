<?php
declare(strict_types=1);

namespace App\View\Cell;

use App\Model\Entity\CustomerProposal;
use App\Proposals\ProposalRows;
use Cake\View\Cell;
use InvalidArgumentException;

/**
 * The proposals still being worked on, for a customer's or a contract's own card.
 *
 * What is there says that the records below it are about to change, so a card shows it before
 * somebody changes the same thing by hand. Only what is still open counts: a proposal given up on
 * is nobody's work, and one whose changes have been applied is history - which leaves the ones
 * waiting to be sent, signed or applied, the last of them the one that matters most.
 *
 * Nothing at all is drawn when there is nothing, so that a card does not grow an empty section.
 */
class ProposalsInProgressCell extends Cell
{
    /**
     * Default display method.
     *
     * @param string $of Whose card: `customer` or `contract`.
     * @param string $id Which one.
     * @return void
     * @throws \InvalidArgumentException When asked about anything else.
     */
    public function display(string $of, string $id): void
    {
        $proposals = $this->fetchTable('CustomerProposals')->find()
            ->contain(['Customers', 'ContractProposals' => ['Contracts']])
            ->where(['CustomerProposals.revoked IS' => null])
            ->orderByDesc('CustomerProposals.effective_from');

        $proposals = match ($of) {
            'customer' => $proposals->where(['CustomerProposals.customer_id' => $id]),
            'contract' => $proposals->where(['CustomerProposals.id IN' => $this->fetchTable('ContractProposals')
                ->find()
                ->select(['ContractProposals.customer_proposal_id'])
                ->where(['ContractProposals.contract_id' => $id])]),
            default => throw new InvalidArgumentException(sprintf('`%s` has no card.', $of)),
        };

        // Dealt with is asked of each, since what is left to apply is read off its parts.
        /** @var list<\App\Model\Entity\CustomerProposal> $found */
        $found = $proposals->all()->toList();
        $inProgress = array_filter(
            $found,
            fn(CustomerProposal $proposal): bool => !$proposal->hasBeenDealtWith(),
        );

        $this->set('rounds', (new ProposalRows())->of($inProgress));
    }
}
