<?php
declare(strict_types=1);

namespace App\Contracts\Check;

use App\Model\Table\ContractVersionsTable;
use Cake\ORM\Query\SelectQuery;
use Override;

/**
 * Two versions of one contract in force at the same time.
 *
 * A contract is meant to have exactly one version in force on any given day - that is what
 * makes it possible to say what was agreed. Two at once means the earlier one was left open
 * when the new one was written, and from then on nothing can say which terms apply.
 *
 * The earlier of the two is reported, because that is the one whose end is missing or wrong.
 * Versions whose start stands for "not known" are passed over: an open-ended one of those
 * would overlap everything that came after it and say nothing by doing so.
 */
class OverlappingContractVersionsCheck extends AbstractContractCheck
{
    /**
     * Another version of the same contract starts before this one has finished.
     *
     * A version with no end is in force forever, which `infinity` says without the query
     * having to be written twice.
     */
    private const RUNS_INTO_ANOTHER = <<<'SQL'
        EXISTS (
            SELECT 1 FROM contract_versions other
            WHERE other.contract_id = ContractVersions.contract_id
              AND (other.valid_from, other.id) > (ContractVersions.valid_from, ContractVersions.id)
              AND other.valid_from <= COALESCE(ContractVersions.valid_until, 'infinity'::date)
        )
        SQL;

    /**
     * The day the second version starts, so the listing can say where the overlap begins.
     */
    private const OVERLAPS_FROM = <<<'SQL'
        (
            SELECT MIN(overlapping.valid_from) FROM contract_versions overlapping
            WHERE overlapping.contract_id = ContractVersions.contract_id
              AND (overlapping.valid_from, overlapping.id) > (ContractVersions.valid_from, ContractVersions.id)
              AND overlapping.valid_from <= COALESCE(ContractVersions.valid_until, 'infinity'::date)
        )
        SQL;

    /**
     * @param \App\Model\Table\ContractVersionsTable $versions Contract versions table.
     * @param bool $ignore_inactive Whether to count only the contracts that are running.
     * @param string|null $contract_id The one contract being asked about, where there is one.
     */
    public function __construct(
        private ContractVersionsTable $versions,
        bool $ignore_inactive = true,
        ?string $contract_id = null,
    ) {
        parent::__construct($ignore_inactive, $contract_id);
    }

    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'overlapping_contract_versions';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Overlapping Contract Versions');
    }

    /**
     * @return string
     */
    #[Override]
    public function emptyMessage(): string
    {
        return __('No contract has two versions in force at the same time.');
    }

    /**
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    #[Override]
    public function find(): SelectQuery
    {
        $query = $this->versions->find();

        $query->getSelectTypeMap()->addDefaults(['overlaps_from' => 'date']);

        $query
            ->select(['overlaps_from' => $query->expr(self::OVERLAPS_FROM)])
            ->select($this->versions)
            ->select($this->versions->Contracts)
            ->contain(['Contracts'])
            ->where([
                $this->knownDate($query, 'ContractVersions.valid_from'),
                $query->expr(self::RUNS_INTO_ANOTHER),
            ])
            ->orderBy(['ContractVersions.valid_from' => 'ASC']);

        if ($this->ignore_inactive) {
            $this->onlyRunningContracts($query);
        }

        return $this->scopedToContract($query, 'ContractVersions.contract_id');
    }
}
