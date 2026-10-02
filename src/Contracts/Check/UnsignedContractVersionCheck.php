<?php
declare(strict_types=1);

namespace App\Contracts\Check;

use App\Check\CheckScope;
use App\Contracts\Unsigned\UnsignedPaperwork;
use App\Contracts\Unsigned\UnsignedWaits;
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
class UnsignedContractVersionCheck extends AbstractContractCheck
{
    /**
     * Where the settings say how old the paper may be.
     */
    private const SETTINGS_PATH = 'core.contracts.checks.signature_expected_within_months';

    /**
     * How long before a version takes effect it may have been concluded, if nothing says
     * otherwise.
     */
    private const MONTHS = 3;

    /**
     * @param \App\Model\Table\ContractVersionsTable $versions Contract versions table.
     * @param \App\Contracts\Unsigned\UnsignedPaperwork $paperwork What counts as unsigned,
     *   shared with the command that chases it and the run that blocks on it.
     * @param \App\Check\CheckScope $scope What is being asked about, and how widely.
     */
    public function __construct(
        private ContractVersionsTable $versions,
        private UnsignedPaperwork $paperwork,
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
        return 'unsigned_contract_version';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Contract Version Without Signed Documents');
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
        $query = $this->scope->ignore_inactive ? $this->overdue() : $this->everything();

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
            UnsignedWaits::beforeNotifying(),
            UnsignedWaits::beforeBlocking(),
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
        return $this->paperwork->findDue(UnsignedWaits::none(), Date::today());
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
            // The sending is on the proposals now, and the listing shows it.
            ->contain([
                'Contracts' => ['Customers', 'ContractStates'],
                'ContractProposals' => ['CustomerProposals'],
            ])
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
