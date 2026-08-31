<?php
declare(strict_types=1);

namespace App\Contracts\Check;

use App\Contracts\Unsigned\UnsignedPaperwork;
use App\Model\Table\ContractVersionsTable;
use Cake\I18n\Date;
use Cake\ORM\Query\SelectQuery;
use Override;
use Settings\Utility\Settings;

/**
 * A version of a contract with no paper behind it, or with paper too old to be its own.
 *
 * Either nothing says when it was concluded, or what says so is from long before the version
 * took effect - which is what carrying the previous version's date onto the new one looks
 * like. In both cases what is on file cannot show what the customer actually agreed to.
 *
 * The check answers two different questions depending on whether it is asked to keep to what
 * is running, because the same fault means two different things:
 *
 *   Keeping to it, the answer is the day's work - the running services whose wait for a
 *   signature has actually run out, by the same reckoning the automation chases and blocks
 *   by. That is a short list somebody can go through, which is why the check is now handed
 *   out rather than asked for.
 *
 *   Lifting it, the answer is the whole file, deadlines and all - including the thousand an
 *   import left behind. That is putting the history straight, which is its own afternoon
 *   rather than something done while looking at one contract.
 */
class UnsignedContractCheck extends AbstractContractCheck
{
    /**
     * Where the settings say how old the paper may be.
     */
    private const SETTINGS_PATH = 'core.contracts.checks.signature_expected_within_months';

    /**
     * Where the settings say how long a running service may go unsigned before the customer
     * is written to, and before the service is cut off.
     */
    private const NOTIFY_PATH = 'core.contracts.unsigned.notifications';

    private const BLOCK_PATH = 'core.contracts.unsigned.blocking';

    /**
     * How long before a version takes effect it may have been concluded, if nothing says
     * otherwise.
     */
    private const MONTHS = 3;

    /**
     * The two waits, where the settings name none. They are only ever shown here, never acted
     * on, so being out of step with the settings costs a wrong caption rather than a wrong
     * disconnection.
     */
    private const NOTIFY_AFTER_INSTALLATION_DAYS = 5;

    private const NOTIFY_AFTER_VALID_FROM_DAYS = 10;

    private const BLOCK_AFTER_INSTALLATION_DAYS = 10;

    private const BLOCK_AFTER_VALID_FROM_DAYS = 20;

    /**
     * @param \App\Model\Table\ContractVersionsTable $versions Contract versions table.
     * @param \App\Contracts\Unsigned\UnsignedPaperwork $paperwork What counts as unsigned,
     *   shared with the command that chases it and the run that blocks on it.
     * @param bool $ignore_inactive Whether to count only the contracts that are running.
     * @param string|null $contract_id The one contract being asked about, where there is one.
     * @param string|null $customer_id The one customer being asked about, where there is one.
     */
    public function __construct(
        private ContractVersionsTable $versions,
        private UnsignedPaperwork $paperwork,
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
        return 'unsigned_contract';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Contract Version With No Paper Behind It');
    }

    /**
     * @return string
     */
    #[Override]
    public function emptyMessage(): string
    {
        return __('Every contract version says when it was concluded.');
    }

    /**
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    #[Override]
    public function find(): SelectQuery
    {
        $query = $this->ignore_inactive ? $this->overdue() : $this->everything();

        return $this->scoped($this->withDeadlines($query));
    }

    /**
     * Hang the two deadlines off every row, whichever question was asked.
     *
     * The whole file needs them as much as the day's work does - more, because a contract's
     * own card asks the wider question, and a finding there that cannot say whether anything
     * is about to happen leaves the reader to work it out from three dates and two settings.
     * On the rows the automation will never reach they come back empty, which is the true
     * answer rather than a missing one.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query Query being built.
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    private function withDeadlines(SelectQuery $query): SelectQuery
    {
        return $this->paperwork->withDeadlines(
            $query,
            Date::today(),
            (int)Settings::get(self::NOTIFY_PATH . '.after_installation_days', self::NOTIFY_AFTER_INSTALLATION_DAYS),
            (int)Settings::get(self::NOTIFY_PATH . '.after_valid_from_days', self::NOTIFY_AFTER_VALID_FROM_DAYS),
            (int)Settings::get(self::BLOCK_PATH . '.after_installation_days', self::BLOCK_AFTER_INSTALLATION_DAYS),
            (int)Settings::get(self::BLOCK_PATH . '.after_valid_from_days', self::BLOCK_AFTER_VALID_FROM_DAYS),
        );
    }

    /**
     * The running services carrying paperwork nobody has signed.
     *
     * Every one of them, from the day the version takes effect - not only the ones already
     * out of time. Held to the blocking deadline this would list the last of the three
     * things that can be done about a version and hide the two where doing something still
     * helps, and it would hold a different number than the dashboard card that leads here.
     *
     * What each of them is past is said by the deadlines {@see self::withDeadlines()} hangs
     * off every row.
     *
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    private function overdue(): SelectQuery
    {
        return $this->paperwork->findDue(0, 0, Date::today());
    }

    /**
     * Every version with nothing behind it, whatever it says about itself.
     *
     * No deadline and no consideration of what is still running: this is the whole file, for
     * whoever is putting it straight.
     *
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    private function everything(): SelectQuery
    {
        $months = (int)Settings::get(self::SETTINGS_PATH, self::MONTHS);

        $query = $this->versions->find();

        return $query
            // The state comes along because the deadlines are only shown against a contract
            // that still serves somebody, and that is asked of the state.
            ->contain(['Contracts' => ['Customers', 'ContractStates']])
            ->where([
                'OR' => [
                    // No paper at all is a finding whatever the version says about itself -
                    // including the thousand an import left with a start nobody knows, which
                    // is why this limb is not held to a start that means anything.
                    'ContractVersions.conclusion_date IS' => null,
                    [
                        $this->knownDate($query, 'ContractVersions.valid_from'),
                        // paper from long before the version it is meant to be behind, which
                        // is what the previous version's date carried onto a new one looks like
                        $query->expr()->lt(
                            'ContractVersions.conclusion_date',
                            $query->expr(sprintf(
                                "ContractVersions.valid_from - INTERVAL '%d months'",
                                $months,
                            )),
                        ),
                    ],
                ],
            ])
            ->orderBy(['ContractVersions.valid_from' => 'DESC']);
    }
}
