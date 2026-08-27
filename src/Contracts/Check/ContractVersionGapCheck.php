<?php
declare(strict_types=1);

namespace App\Contracts\Check;

use App\Model\Table\ContractVersionsTable;
use Cake\ORM\Query\SelectQuery;
use Override;

/**
 * A contract whose versions leave a stretch of time with none in force.
 *
 * Not a fault on its own: a contract can lapse and be signed again, and the months between
 * are then exactly what happened. It is worth looking at because the other reading is that a
 * version was given the wrong end or the wrong start, and nothing else would ever say so.
 *
 * Because both readings are ordinary, this one stays off until it is asked for.
 */
class ContractVersionGapCheck extends AbstractContractCheck
{
    /**
     * Another version of the same contract begins, but later than the day after this one ends.
     */
    private const RESUMES_LATER = <<<'SQL'
        EXISTS (
            SELECT 1 FROM contract_versions later
            WHERE later.id <> ContractVersions.id
              AND later.contract_id = ContractVersions.contract_id
              AND later.valid_from > ContractVersions.valid_until + 1
        )
        SQL;

    /**
     * Nothing starts within the break, so this really is the last version before it.
     */
    private const NOTHING_FOLLOWS_IT = <<<'SQL'
        NOT EXISTS (
            SELECT 1 FROM contract_versions covering
            WHERE covering.contract_id = ContractVersions.contract_id
              AND covering.valid_from > ContractVersions.valid_from
              AND covering.valid_from <= ContractVersions.valid_until + 1
        )
        SQL;

    /**
     * The break has not been closed yet, which is when there is still something to decide.
     */
    private const BREAK_NOT_OVER = <<<'SQL'
        NOT EXISTS (
            SELECT 1 FROM contract_versions resumed
            WHERE resumed.id <> ContractVersions.id
              AND resumed.contract_id = ContractVersions.contract_id
              AND resumed.valid_from > ContractVersions.valid_until + 1
              AND resumed.valid_from <= CURRENT_DATE
        )
        SQL;

    /**
     * The day a version is in force again, so the listing can say how long the break is.
     */
    private const RESUMES_ON = <<<'SQL'
        (
            SELECT MIN(resumes.valid_from) FROM contract_versions resumes
            WHERE resumes.id <> ContractVersions.id
              AND resumes.contract_id = ContractVersions.contract_id
              AND resumes.valid_from > ContractVersions.valid_until + 1
        )
        SQL;

    /**
     * @param \App\Model\Table\ContractVersionsTable $versions Contract versions table.
     * @param bool $ignore_inactive Whether to count only breaks that are still open.
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
        return 'contract_version_gap';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Gap Between Consecutive Contract Versions');
    }

    /**
     * @return string
     */
    #[Override]
    public function emptyMessage(): string
    {
        return __('No contract is left without a version in force between two of them.');
    }

    /**
     * A lapse and a re-signing look the same as a mistyped date, so this keeps to the
     * overview and is asked for rather than offered.
     *
     * @return bool
     */
    #[Override]
    public function onDashboard(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    #[Override]
    public function optional(): bool
    {
        return true;
    }

    /**
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    #[Override]
    public function find(): SelectQuery
    {
        $query = $this->versions->find();

        $query->getSelectTypeMap()->addDefaults(['resumes_on' => 'date']);

        $query
            ->select(['resumes_on' => $query->expr(self::RESUMES_ON)])
            ->select($this->versions)
            ->select($this->versions->Contracts)
            ->contain(['Contracts'])
            ->where([
                'ContractVersions.valid_until IS NOT' => null,
                $this->knownDate($query, 'ContractVersions.valid_from'),
                $query->expr(self::RESUMES_LATER),
                $query->expr(self::NOTHING_FOLLOWS_IT),
            ])
            ->orderBy(['ContractVersions.valid_until' => 'ASC']);

        if ($this->ignore_inactive) {
            $this->onlyRunningContracts($query)->where([$query->expr(self::BREAK_NOT_OVER)]);
        }

        return $this->scoped($query);
    }
}
