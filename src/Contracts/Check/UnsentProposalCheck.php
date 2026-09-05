<?php
declare(strict_types=1);

namespace App\Contracts\Check;

use App\Model\Table\ContractVersionProposalsTable;
use Cake\I18n\Date;
use Cake\ORM\Query\SelectQuery;
use Override;
use Settings\Utility\Settings;

/**
 * A proposal drawn up and never sent.
 *
 * Nobody is waiting on the customer here - the papers never left the building. It belongs beside
 * the proposals waiting for a signature all the same, because from the office's side the two are
 * the same job half done, and this is the half nothing else reports: the day the version takes
 * effect arrives whether or not anybody printed anything.
 *
 * A proposal whose day is still far off is not shown at all, whichever question is asked: until
 * then there is nothing to do about it, and it would only be a list of things to leave alone.
 * Lifting the filter widens this one to the contracts that serve nobody, and to nothing else.
 */
class UnsentProposalCheck extends AbstractContractCheck
{
    /**
     * How far ahead a proposal nobody has sent is worth raising, if nothing says otherwise.
     */
    private const WITHIN_DAYS = 14;

    /**
     * Where the settings say how far ahead to look.
     */
    private const WITHIN_DAYS_PATH = 'core.contracts.unsigned.proposals.unsent_within_days';

    /**
     * @param \App\Model\Table\ContractVersionProposalsTable $proposals Contract version proposals table.
     * @param bool $ignore_inactive Whether to keep to the contracts that serve somebody.
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
        return 'unsent_proposal';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Proposal That Never Went Out');
    }

    /**
     * @return string
     */
    #[Override]
    public function emptyMessage(): string
    {
        return __('Every proposal drawn up has gone out.');
    }

    /**
     * Proposals nobody has sent to the customer.
     *
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    #[Override]
    public function find(): SelectQuery
    {
        $within = (int)Settings::get(self::WITHIN_DAYS_PATH, self::WITHIN_DAYS);

        $query = $this->proposals->find('open');

        $query
            ->contain(['Contracts', 'ContractVersions'])
            ->innerJoinWith('Contracts')
            ->where([
                'ContractVersionProposals.sent_date IS' => null,
                // The wait holds whichever question is being asked. A proposal drawn up this
                // morning for a version that starts in the spring is somebody's work in hand,
                // and a contract's own card reporting it as a fault would be wrong in the one
                // place where the person reading is the person who drew it up.
                'ContractVersionProposals.effective_from <=' => Date::today()->addDays(max(0, $within)),
            ])
            ->orderBy(['ContractVersionProposals.effective_from' => 'ASC']);

        if ($this->ignore_inactive) {
            $this->onlyRunningContracts($query);
        }

        return $this->scoped($query);
    }
}
