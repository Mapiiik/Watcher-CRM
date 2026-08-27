<?php
declare(strict_types=1);

namespace App\Contracts\Check;

use App\Model\Table\ContractVersionsTable;
use Cake\I18n\Date;
use Cake\ORM\Query\SelectQuery;
use Override;

/**
 * A minimum term still running after the contract it belongs to has stopped.
 *
 * The file holds a version in force for eighteen days carrying a term of two years, and
 * dozens like it: the customer left early and the term stands. Either something is owed and
 * nobody has asked for it, or it was waived and nobody has written that down - which is what
 * `obligations_settled` is for. Both are put right here; neither is visible anywhere else,
 * because every other listing asks what is current and this is about what was.
 *
 * Terms already run out are not shown by default. There is nothing left to ask for and
 * nothing left to waive, so it would be a list of things that cannot be done.
 */
class UnsettledObligationCheck extends AbstractContractCheck
{
    /**
     * @param \App\Model\Table\ContractVersionsTable $versions Contract versions table.
     * @param bool $ignore_inactive Whether to count only terms that are still running.
     * @param string|null $contract_id The one contract being asked about, where there is one.
     * @param string|null $customer_id The one customer being asked about, where there is one.
     */
    public function __construct(
        private ContractVersionsTable $versions,
        bool $ignore_inactive = true,
        ?string $contract_id = null,
        ?string $customer_id = null,
    ) {
        parent::__construct($ignore_inactive, $contract_id, $customer_id);
    }

    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'unsettled_obligation';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Unsettled Obligation Outliving the Contract');
    }

    /**
     * @return string
     */
    #[Override]
    public function emptyMessage(): string
    {
        return __('No minimum term is left running after the contract it belongs to has stopped.');
    }

    /**
     * The subject is a contract that has stopped, so keeping to the ones that are running
     * would empty this check rather than narrow it. What it keeps to instead is the terms
     * still running, which are the ones there is anything to do about.
     *
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    #[Override]
    public function find(): SelectQuery
    {
        $query = $this->versions->find();

        $query
            ->contain(['Contracts'])
            ->innerJoinWith('Contracts')
            ->where([
                'ContractVersions.obligations_settled' => false,
                'ContractVersions.obligation_until IS NOT' => null,
                $this->knownDate($query, 'ContractVersions.valid_from'),
                $query->expr()->or([
                    // the version it belongs to has ended, and the term reaches past it
                    $query->expr()->gt(
                        'ContractVersions.obligation_until',
                        $query->identifier('ContractVersions.valid_until'),
                    ),
                    // or the whole contract has, which is the same thing said on the contract
                    $query->expr()->gt(
                        'ContractVersions.obligation_until',
                        $query->identifier('Contracts.termination_date'),
                    ),
                ]),
            ])
            ->orderBy(['ContractVersions.obligation_until' => 'ASC']);

        if ($this->ignore_inactive) {
            $query->where(['ContractVersions.obligation_until >=' => Date::now()]);
        }

        return $this->scoped($query, 'ContractVersions.contract_id');
    }
}
