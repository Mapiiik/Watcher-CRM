<?php
declare(strict_types=1);

namespace App\Contracts\Check;

use Cake\ORM\Query\SelectQuery;
use Override;

/**
 * A proposal that was applied with something left out.
 *
 * The way out of a proposal the customer has signed whose records have moved under it since: an
 * administrator applies the rest of it and what could not be written is set down on the proposal.
 * That is the end of the emergency and the beginning of a job, because the papers say one thing
 * and the records now say almost it.
 *
 * Rare by design. Nothing here has a deadline, so both readings of the question answer the same
 * way - every one of these is somebody's to see to, whether it is the day's work or the whole
 * file.
 */
class PartlyAppliedContractProposalCheck extends AbstractContractProposalCheck
{
    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'partly_applied_contract_proposal';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Contract Proposal Applied With Something Left Out');
    }

    /**
     * @return string
     */
    #[Override]
    public function emptyMessage(): string
    {
        return __('Everything the customers have agreed to was applied in full.');
    }

    /**
     * Nothing here waits for a day, so the filter has nothing narrower to ask.
     *
     * @return bool
     */
    #[Override]
    public function hasAWiderReading(): bool
    {
        return false;
    }

    /**
     * Proposals that were applied with a line passed over.
     *
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    #[Override]
    public function find(): SelectQuery
    {
        $query = $this->candidates()
            ->where(['ContractProposals.applied IS NOT' => null])
            // Written out rather than bound: a placeholder beside a jsonb column is read as the
            // operator of the same name ({@see \App\Model\Table\BillingsTable}).
            ->where(["ContractProposals.left_out::text <> '{}'"])
            // Once somebody has said it is done, it is done. A job nobody can close is a job
            // the listing stops being read for.
            ->where(['ContractProposals.left_out_settled IS' => null])
            ->orderBy(['ContractProposals.applied' => 'DESC']);

        return $this->scoped($query);
    }
}
