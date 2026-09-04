<?php
declare(strict_types=1);

namespace App\Contracts\Check;

use App\Model\Table\ContractVersionProposalsTable;
use Cake\I18n\Date;
use Cake\ORM\Query\SelectQuery;
use Override;
use Settings\Utility\Settings;

/**
 * A signed proposal nobody has carried over.
 *
 * The customer has agreed to something and the records still say the old thing. Until somebody
 * presses the button, the service runs on the old terms and is invoiced on them - which is the one
 * way this whole arrangement can go wrong quietly, because everything else about a proposal is
 * visible on the contract and this is the step that has no deadline of its own.
 *
 * A proposal whose day has not come yet is not shown by default. There is nothing to do about it
 * until it does, and it would only be a list of things to leave alone.
 */
class UntransferredProposalCheck extends AbstractContractCheck
{
    /**
     * How far ahead a proposal is worth raising, if nothing says otherwise.
     */
    private const WITHIN_DAYS = 14;

    /**
     * Where the settings say how far ahead to look.
     */
    private const WITHIN_DAYS_PATH = 'core.contracts.checks.untransferred_proposal_within_days';

    /**
     * @param \App\Model\Table\ContractVersionProposalsTable $proposals Contract version proposals table.
     * @param bool $ignore_inactive Whether to count only proposals whose day has come or is near.
     * @param string|null $contract_id The one contract being asked about, where there is one.
     * @param string|null $customer_id The one customer being asked about, where there is one.
     */
    public function __construct(
        private ContractVersionProposalsTable $proposals,
        bool $ignore_inactive = true,
        ?string $contract_id = null,
        ?string $customer_id = null,
    ) {
        parent::__construct($ignore_inactive, $contract_id, $customer_id);
    }

    /**
     * @return string|null
     */
    #[Override]
    protected function contractField(): ?string
    {
        return 'ContractVersionProposals.contract_id';
    }

    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'untransferred_proposal';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Signed Proposal Nobody Has Carried Over');
    }

    /**
     * @return string
     */
    #[Override]
    public function emptyMessage(): string
    {
        return __('Everything the customers have agreed to has been carried over.');
    }

    /**
     * Proposals the customer has signed and nobody has acted on.
     *
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    #[Override]
    public function find(): SelectQuery
    {
        $query = $this->proposals->find('pendingTransfer');

        $query
            ->contain(['Contracts', 'ContractVersions'])
            ->innerJoinWith('Contracts')
            ->orderBy(['ContractVersionProposals.effective_from' => 'ASC']);

        if ($this->ignore_inactive) {
            $within = (int)Settings::get(self::WITHIN_DAYS_PATH, self::WITHIN_DAYS);

            $query->where([
                'ContractVersionProposals.effective_from <=' => Date::now()->addDays($within),
            ]);
        }

        return $this->scoped($query);
    }
}
