<?php
declare(strict_types=1);

namespace App\Contracts\Check;

use App\Check\AbstractCheck;
use Cake\Database\Expression\QueryExpression;
use Cake\I18n\Date;
use Cake\ORM\Query\SelectQuery;
use Settings\Utility\Settings;

/**
 * Shared ground for contract checks.
 *
 * Two things every one of them is given. Whether it keeps to what is running, which each
 * applies to its own subject - the answer has to be about the record being reported rather
 * than about something else its contract happens to have. And, when the checks are asked
 * about one contract rather than about the whole file, which contract that is.
 *
 * Beside that, what counts as a date at all. Where a day may reasonably fall is a matter of
 * how the company works rather than of how the code does, so it is asked of the settings -
 * and asked once, because a check runs its query more often than the answer changes.
 */
abstract class AbstractContractCheck extends AbstractCheck implements ContractCheckInterface
{
    /**
     * Where the settings say what a date may look like.
     */
    private const SETTINGS_PATH = 'core.contracts.checks';

    /**
     * The oldest day anything on file is allowed to name, if nothing says otherwise.
     */
    private const EARLIEST_DATE = '2000-01-01';

    /**
     * How far ahead a day may reach, if nothing says otherwise.
     */
    private const YEARS_AHEAD = 5;

    private ?Date $plausible_from = null;

    private ?Date $plausible_until = null;

    /**
     * @var list<string>|null
     */
    private ?array $dates_meaning_unknown = null;

    /**
     * @param bool $ignore_inactive Whether the check keeps to what is running.
     * @param string|null $contract_id The one contract being asked about, where there is one.
     */
    public function __construct(
        protected bool $ignore_inactive = true,
        protected ?string $contract_id = null,
    ) {
    }

    /**
     * Narrow a query to the one contract being asked about, where there is one.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query Query to narrow.
     * @param string $field The field holding the contract, qualified by its alias.
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    protected function scopedToContract(SelectQuery $query, string $field): SelectQuery
    {
        if ($this->contract_id !== null) {
            $query->where([$field => $this->contract_id]);
        }

        return $query;
    }

    /**
     * Narrow a query on a record hanging off a contract to the contracts that are running.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query Query to narrow.
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    protected function onlyRunningContracts(SelectQuery $query): SelectQuery
    {
        return $query->innerJoinWith(
            'Contracts.ContractStates',
            fn(SelectQuery $states): SelectQuery => $states->where(['ContractStates.active_services' => true]),
        );
    }

    /**
     * The oldest day anything on file may name.
     *
     * @return \Cake\I18n\Date
     */
    protected function plausibleFrom(): Date
    {
        return $this->plausible_from ??= Settings::getDate(
            self::SETTINGS_PATH . '.earliest_date',
            self::EARLIEST_DATE,
        ) ?? new Date(self::EARLIEST_DATE);
    }

    /**
     * The furthest ahead a day may reach.
     *
     * Counted from today rather than fixed, because a fixed day would quietly go stale while
     * "we do not write longer contracts than that" stays true.
     *
     * @return \Cake\I18n\Date
     */
    protected function plausibleUntil(): Date
    {
        return $this->plausible_until ??= Date::now()
            ->addYears((int)Settings::get(self::SETTINGS_PATH . '.years_ahead', self::YEARS_AHEAD));
    }

    /**
     * The days that stand for "not known" rather than naming one.
     *
     * @return list<string>
     */
    protected function datesMeaningUnknown(): array
    {
        if ($this->dates_meaning_unknown === null) {
            $marks = Settings::get(self::SETTINGS_PATH . '.dates_meaning_unknown', []);

            $this->dates_meaning_unknown = is_array($marks) ? array_values(array_map(strval(...), $marks)) : [];
        }

        return $this->dates_meaning_unknown;
    }

    /**
     * A day that cannot be a day: too far back, or further ahead than anything reaches.
     *
     * This is what a mistyped year looks like - a digit too many or too few - and there is no
     * arguing with it, which is why it is reported rather than guessed at.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query Query being built.
     * @param string $field The date field, qualified by its alias.
     * @return \Cake\Database\Expression\QueryExpression
     */
    protected function implausibleDate(SelectQuery $query, string $field): QueryExpression
    {
        return $query->expr()->or([
            $query->expr()->lt($field, $this->plausibleFrom(), 'date'),
            $query->expr()->gt($field, $this->plausibleUntil(), 'date'),
        ]);
    }

    /**
     * A day that names one, as against standing for "we do not know".
     *
     * An import may have marked the days nobody knew, and comparing those with anything says
     * nothing at all. A field left empty is not one of them: empty means empty.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query Query being built.
     * @param string $field The date field, qualified by its alias.
     * @return \Cake\Database\Expression\QueryExpression
     */
    protected function knownDate(SelectQuery $query, string $field): QueryExpression
    {
        $marks = $this->datesMeaningUnknown();

        if ($marks === []) {
            return $query->expr();
        }

        return $query->expr()->or([
            $query->expr()->isNull($field),
            $query->expr()->notIn($field, $marks, 'date'),
        ]);
    }
}
