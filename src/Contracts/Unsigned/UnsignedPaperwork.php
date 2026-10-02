<?php
declare(strict_types=1);

namespace App\Contracts\Unsigned;

use App\Model\Enum\UnsignedDeadlineAnchor;
use App\Model\Table\ContractVersionsTable;
use App\Proposals\WatchedSince;
use Cake\Database\Expression\CaseStatementExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\I18n\Date;
use Cake\ORM\Query\SelectQuery;
use Settings\Utility\Settings;

/**
 * A running service whose paperwork nobody has signed, and how long it has been that way.
 *
 * One place says what that means, because three things act on the answer - the check that
 * lists them, the command that chases the customer, and the run that cuts them off - and
 * three readings of "unsigned" that drift apart would have the office chasing one set and
 * the routers blocking another.
 *
 * The wait is measured against two dates at once, and the later of them is what counts. The
 * service has to have been running for a while, and the version has to have been in effect
 * for a while: either alone catches a contract that is merely new.
 */
final class UnsignedPaperwork
{
    /**
     * @param \App\Model\Table\ContractVersionsTable $versions Contract versions table.
     */
    public function __construct(private ContractVersionsTable $versions)
    {
    }

    /**
     * Versions whose wait was up on the given day or before it.
     *
     * This is a state rather than an event, which is what blocking asks: whoever is past the
     * deadline today is blocked today, and the run recomputes the whole set each time.
     *
     * @param \App\Contracts\Unsigned\UnsignedWaits $waits How long it may go unsigned first.
     * @param \Cake\I18n\Date $today The day being asked about.
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    public function findDue(UnsignedWaits $waits, Date $today): SelectQuery
    {
        $query = $this->base($today);

        return $query->where(
            $query->expr()->lte($this->deadline($query, $waits), $today, 'date'),
        );
    }

    /**
     * The contracts whose service is to be cut off for want of a signature.
     *
     * Contracts rather than customers, and rather than versions. Not customers, because a
     * customer holding three contracts has not agreed to lose the two that are signed and
     * paid for over the one that is not. Not versions, because two unsigned versions of the
     * same contract are still one service to cut off.
     *
     * @param \App\Contracts\Unsigned\UnsignedWaits $waits How long it may go unsigned first.
     * @param \Cake\I18n\Date $today The day being asked about.
     * @return array<string, string> Contract id to the reason it is being cut off.
     */
    public function contractIdsToBlock(UnsignedWaits $waits, Date $today): array
    {
        $blocked = [];

        /** @var \App\Model\Entity\ContractVersion $version */
        foreach ($this->findDue($waits, $today)->all() as $version) {
            $blocked[$version->contract_id] = __('unsigned contract');
        }

        return $blocked;
    }

    /**
     * Versions whose wait was up on exactly the given day.
     *
     * This is an event, which is what notifying asks. Asking for the day rather than for the
     * range is what keeps a nightly run from sending the same reminder over and over without
     * anything having to be remembered between runs.
     *
     * @param \App\Contracts\Unsigned\UnsignedWaits $waits How long it may go unsigned first.
     * @param \Cake\I18n\Date $day The day the wait is asked to have run out on.
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    public function findBecomingDueOn(UnsignedWaits $waits, Date $day): SelectQuery
    {
        $query = $this->base(Date::today());

        return $query->where(
            $query->expr()->eq($this->deadline($query, $waits), $day, 'date'),
        );
    }

    /**
     * Versions whose wait was up before the given day.
     *
     * What is left once the named reminder days are done with, for an installation that
     * would rather keep asking every day than let it go quiet. The boundary is kept strict
     * so that this and the named days cannot both pick the same version up in one run.
     *
     * @param \App\Contracts\Unsigned\UnsignedWaits $waits How long it may go unsigned first.
     * @param \Cake\I18n\Date $day The day the wait is asked to have run out before.
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    public function findDueBefore(UnsignedWaits $waits, Date $day): SelectQuery
    {
        $query = $this->base(Date::today());

        return $query->where(
            $query->expr()->lt($this->deadline($query, $waits), $day, 'date'),
        );
    }

    /**
     * Hang both deadlines off each row, so that a listing can say which one a version is past.
     *
     * Worked out by the database rather than in PHP afterwards, because the rule about which
     * date the wait is counted from lives in one place - the anchor's own SQL - and a second
     * reading of it in PHP would be free to drift from the one the chasing and the blocking
     * actually go by.
     *
     * The two arrive on the entity as `notify_due` and `block_due`. They are of the query
     * rather than of the record, so a version fetched any other way does not carry them.
     *
     * Both come back empty on a version the automation is never going to touch - one a later
     * version has replaced, one from before the line the settings draw, one on a contract
     * that serves nobody. A listing of the whole file holds plenty of those, and a deadline
     * printed against one of them would promise a disconnection that is not coming.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query Query to add them to.
     * @param \Cake\I18n\Date $today The day the standing is asked as of.
     * @param \App\Contracts\Unsigned\UnsignedWaits $notify How long before the customer is written to.
     * @param \App\Contracts\Unsigned\UnsignedWaits $block How long before the service is cut off.
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    public function withDeadlines(
        SelectQuery $query,
        Date $today,
        UnsignedWaits $notify,
        UnsignedWaits $block,
    ): SelectQuery {
        $query->selectAlso([
            'notify_due' => $this->deadlineWhereItApplies($query, $today, $notify),
            'block_due' => $this->deadlineWhereItApplies($query, $today, $block),
        ]);

        // Said outright, because an expression carries no type of its own and both of these
        // would otherwise come back as strings that compare by their spelling.
        $query->getSelectTypeMap()->addDefaults(['notify_due' => 'date', 'block_due' => 'date']);

        return $query;
    }

    /**
     * Everything the deadlines are measured against, before any deadline is applied.
     *
     * @param \Cake\I18n\Date $today The day a version has to still be in force on.
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    private function base(Date $today): SelectQuery
    {
        $query = $this->versions->find();

        $query
            // The sending is on the proposals now, and the listing shows it.
            ->contain([
                'Contracts' => ['Customers', 'ContractStates'],
                'ContractProposals' => ['CustomerProposals'],
            ])
            ->where([
                'ContractVersions.conclusion_date IS' => null,
                $this->consideredConditions($query, $today),
            ])
            ->orderBy(['ContractVersions.valid_from' => 'DESC']);

        return $query;
    }

    /**
     * What makes a version one the automation will act on, beyond having no paper.
     *
     * Written once and used twice - as what narrows the day's work, and as what decides
     * whether a deadline may be shown against a row at all. Two readings of this would let a
     * listing print a disconnection date for a version no run is ever going to reach.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query Query being built.
     * @param \Cake\I18n\Date $today The day it is being asked as of.
     * @return array<mixed>
     */
    private function consideredConditions(SelectQuery $query, Date $today): array
    {
        return [
            // The thousand a migration left behind are not work anybody is going to do, and
            // mailing them would be worse than leaving them. Where the line falls is the
            // office's answer, not the code's.
            'ContractVersions.valid_from >=' => $this->considerFrom(),
            // No day to count from is no deadline. This has to be said out loud because
            // Postgres reads GREATEST past a NULL rather than through it: without the guard a
            // version missing its anchor would quietly take its deadline from the other date
            // alone and be chased on half the rule.
            //
            // Which versions this drops is the anchor's business - a missing installation
            // date under one, an unrecorded sending under another. The first of those is
            // reported on its own, by MissingInstallationDateCheck.
            $query->expr()->isNotNull($query->expr($this->anchorSql())),
            // A contract whose state serves nobody is not going to be cut off again.
            'ContractStates.active_services' => true,
            // A version a later one has replaced is history. Its paperwork is worth putting
            // straight, but nobody is running a service on it today.
            'OR' => [
                'ContractVersions.valid_until IS' => null,
                'ContractVersions.valid_until >=' => $today,
            ],
        ];
    }

    /**
     * The day the wait runs out: the later of the two dates the version is held to.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query Query being built.
     * @param \App\Contracts\Unsigned\UnsignedWaits $waits The two waits it is held to.
     * @return \Cake\Database\Expression\QueryExpression
     */
    private function deadline(SelectQuery $query, UnsignedWaits $waits): QueryExpression
    {
        return $query->expr(sprintf(
            "GREATEST(%s + INTERVAL '%d days', ContractVersions.valid_from + INTERVAL '%d days')",
            $this->anchorSql(),
            $waits->after_anchor,
            $waits->after_start,
        ));
    }

    /**
     * That day, but only on a version the automation would ever reach - and nothing at all on
     * one it would not.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query Query being built.
     * @param \Cake\I18n\Date $today The day it is being asked as of.
     * @param \App\Contracts\Unsigned\UnsignedWaits $waits The two waits it is held to.
     * @return \Cake\Database\Expression\CaseStatementExpression
     */
    private function deadlineWhereItApplies(
        SelectQuery $query,
        Date $today,
        UnsignedWaits $waits,
    ): CaseStatementExpression {
        return $query->expr()
            ->case()
            ->when($this->consideredConditions($query, $today))
            ->then($this->deadline($query, $waits), 'date');
    }

    /**
     * The date the shorter of the two waits is counted from, as the settings have it.
     *
     * Asked once per query rather than held, because a query is built far more often than
     * the office changes its mind about this.
     *
     * @return string
     */
    private function anchorSql(): string
    {
        return UnsignedDeadlineAnchor::fromSetting(Settings::getString(
            UnsignedDeadlineAnchor::SETTINGS_PATH,
            UnsignedDeadlineAnchor::Installation->value,
        ))->sql();
    }

    /**
     * The earliest a version may take effect and still be the automation's business.
     *
     * The same day the rest of the family is held to, {@see \App\Proposals\WatchedSince}.
     *
     * @return \Cake\I18n\Date
     */
    private function considerFrom(): Date
    {
        return WatchedSince::contracts();
    }
}
