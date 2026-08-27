<?php
declare(strict_types=1);

namespace App\Contracts\Check;

use App\Model\Table\ContractVersionsTable;
use Cake\ORM\Query\SelectQuery;
use Override;

/**
 * A contract version over a stretch of time that cannot exist.
 *
 * Either it ends before it begins, or its minimum term was already over when it began, or one
 * of its days names a year that cannot be right - the file holds a version starting in the
 * year 14. A term ending before its own version starts is the same kind of slip: the term
 * belongs to the version, so it cannot have run out before the version existed.
 *
 * Versions whose start stands for "not known" are passed over. Comparing a day nobody knows
 * with anything says nothing, and there are a thousand of them from one import.
 */
class ImpossibleContractVersionPeriodCheck extends AbstractContractCheck
{
    /**
     * @param \App\Model\Table\ContractVersionsTable $versions Contract versions table.
     * @param bool $ignore_inactive Whether to count only the contracts that are running.
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
     * @return string|null
     */
    #[Override]
    protected function contractField(): ?string
    {
        return 'ContractVersions.contract_id';
    }

    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'impossible_contract_version_period';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Impossible Contract Version Period');
    }

    /**
     * @return string
     */
    #[Override]
    public function emptyMessage(): string
    {
        return __('Every contract version runs over a stretch of time that can exist.');
    }

    /**
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    #[Override]
    public function find(): SelectQuery
    {
        $query = $this->versions->find();

        $query
            ->contain(['Contracts'])
            ->where([
                $this->knownDate($query, 'ContractVersions.valid_from'),
                $query->expr()->or([
                    // ends before it begins
                    $query->expr()->lt(
                        'ContractVersions.valid_until',
                        $query->identifier('ContractVersions.valid_from'),
                    ),
                    // a minimum term that was over before the version it belongs to began
                    $query->expr()->lt(
                        'ContractVersions.obligation_until',
                        $query->identifier('ContractVersions.valid_from'),
                    ),
                    $this->implausibleDate($query, 'ContractVersions.valid_from'),
                    $this->implausibleDate($query, 'ContractVersions.valid_until'),
                    $this->implausibleDate($query, 'ContractVersions.obligation_until'),
                    $this->implausibleDate($query, 'ContractVersions.conclusion_date'),
                ]),
            ])
            ->orderBy(['ContractVersions.valid_from' => 'ASC']);

        if ($this->ignore_inactive) {
            $this->onlyRunningContracts($query);
        }

        return $this->scoped($query);
    }
}
