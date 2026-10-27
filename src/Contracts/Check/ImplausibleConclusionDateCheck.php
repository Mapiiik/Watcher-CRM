<?php
declare(strict_types=1);

namespace App\Contracts\Check;

use App\Check\CheckScope;
use App\Model\Table\ContractVersionsTable;
use Cake\I18n\Date;
use Cake\ORM\Query\SelectQuery;
use Override;

/**
 * A contract version concluded on a day that does not fit it.
 *
 * Long before it took effect or long after, after it had ended, or on a day still to come. The
 * usual cause is a mistyped year, often the current one written out of habit over the year the
 * version began, or the previous version's date carried onto a new one.
 */
class ImplausibleConclusionDateCheck extends AbstractContractCheck
{
    /**
     * Where the settings say how long before and after a version takes effect it may be concluded.
     */
    private const IN_ADVANCE_PATH = 'core.contracts.checks.concluded_in_advance_months';
    private const RETROACTIVELY_PATH = 'core.contracts.checks.concluded_retroactively_months';

    /**
     * How long, if nothing says otherwise.
     */
    private const IN_ADVANCE_MONTHS = 3;
    private const RETROACTIVELY_MONTHS = 3;

    /**
     * @param \App\Model\Table\ContractVersionsTable $versions Contract versions table.
     * @param \App\Check\CheckScope $scope What is being asked about, and how widely.
     */
    public function __construct(
        private ContractVersionsTable $versions,
        CheckScope $scope = new CheckScope(),
    ) {
        parent::__construct($scope);
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
        return 'implausible_conclusion_date';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Implausible Conclusion Date');
    }

    /**
     * @return string
     */
    #[Override]
    public function emptyMessage(): string
    {
        return __('Every contract version was concluded on a day that fits it.');
    }

    /**
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    #[Override]
    public function find(): SelectQuery
    {
        $in_advance = $this->days(self::IN_ADVANCE_PATH, self::IN_ADVANCE_MONTHS);
        $retroactively = $this->days(self::RETROACTIVELY_PATH, self::RETROACTIVELY_MONTHS);

        $query = $this->versions->find();

        $query
            ->contain(['Contracts' => ['Customers']])
            ->where([
                $this->knownDate($query, 'ContractVersions.valid_from'),
                $query->expr()->or([
                    // long before it took effect
                    $query->expr()->lt(
                        'ContractVersions.conclusion_date',
                        $query->expr(sprintf(
                            "ContractVersions.valid_from - INTERVAL '%d months'",
                            $in_advance,
                        )),
                    ),
                    // long after it took effect
                    $query->expr()->gt(
                        'ContractVersions.conclusion_date',
                        $query->expr(sprintf(
                            "ContractVersions.valid_from + INTERVAL '%d months'",
                            $retroactively,
                        )),
                    ),
                    // after it had ended
                    $query->expr()->and([
                        $this->knownDate($query, 'ContractVersions.valid_until'),
                        $query->expr()->gt(
                            'ContractVersions.conclusion_date',
                            $query->identifier('ContractVersions.valid_until'),
                        ),
                    ]),
                    // on a day still to come
                    $query->expr()->gt('ContractVersions.conclusion_date', Date::today(), 'date'),
                ]),
            ])
            ->orderBy(['ContractVersions.valid_from' => 'ASC']);

        $this->onlyWhatIsRunning($query);

        return $this->scoped($query);
    }
}
