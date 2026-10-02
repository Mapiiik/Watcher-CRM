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
 * Two records can say it. A contract version that has taken effect and come back unsigned is one.
 * A running service no version covers at all is the other, and it is the ordinary case now that the
 * papers wait on a proposal: the billings go in so that the line runs the day it is installed, and
 * the version only arrives when somebody applies the proposal. Watching the versions alone left
 * exactly the new work unwatched.
 *
 * The two cannot report the same contract twice: a version in force is the first kind, no version
 * in force is the second.
 *
 * The wait is measured against two dates at once, and the later of them is what counts. The
 * service has to have been running for a while, and what it runs on has to have been in effect
 * for a while: either alone catches a contract that is merely new. For a version that second date
 * is its start; for a service without one it is the day we began to charge for it.
 */
final class UnsignedPaperwork
{
    /**
     * Where the settings say whether a service with no version at all is watched at all.
     */
    private const WITHOUT_VERSION_PATH = 'core.contracts.paperwork.unsigned.thresholds.without_version';

    /**
     * The day we began to charge for the contract, which is what a service with no version is held
     * to instead of a version's start.
     */
    private const CHARGED_SINCE = '(SELECT MIN(Charged.billing_from) FROM billings Charged'
        . ' WHERE Charged.contract_id = Contracts.id)';

    /**
     * The last day any version of the contract was in force, for a listing to show.
     */
    private const COVERED_UNTIL = '(SELECT MAX(Covered.valid_until) FROM contract_versions Covered'
        . ' WHERE Covered.contract_id = Contracts.id)';

    /**
     * @param \App\Model\Table\ContractVersionsTable $versions Contract versions table. The
     *   contracts are reached through it rather than asked for separately, so that everything that
     *   already builds this class keeps building it the same way.
     */
    public function __construct(private ContractVersionsTable $versions)
    {
    }

    /**
     * Every unsigned service whose wait was up on the given day or before it, of both kinds.
     *
     * @param \App\Contracts\Unsigned\UnsignedWaits $waits How long it may go unsigned first.
     * @param \Cake\I18n\Date $today The day being asked about.
     * @return list<\App\Contracts\Unsigned\UnsignedService>
     */
    public function due(UnsignedWaits $waits, Date $today): array
    {
        return $this->gathered(
            $this->findDue($waits, $today),
            $this->watchingServicesWithoutAVersion() ? $this->findServicesDue($waits, $today) : null,
            $today,
        );
    }

    /**
     * The same, for the one day the wait ran out on.
     *
     * @param \App\Contracts\Unsigned\UnsignedWaits $waits How long it may go unsigned first.
     * @param \Cake\I18n\Date $day The day the wait is asked to have run out on.
     * @return list<\App\Contracts\Unsigned\UnsignedService>
     */
    public function becomingDueOn(UnsignedWaits $waits, Date $day): array
    {
        return $this->gathered(
            $this->findBecomingDueOn($waits, $day),
            $this->watchingServicesWithoutAVersion() ? $this->findServicesBecomingDueOn($waits, $day) : null,
            $day,
        );
    }

    /**
     * The same, for everything that ran out before the given day.
     *
     * @param \App\Contracts\Unsigned\UnsignedWaits $waits How long it may go unsigned first.
     * @param \Cake\I18n\Date $day The day the wait is asked to have run out before.
     * @return list<\App\Contracts\Unsigned\UnsignedService>
     */
    public function dueBefore(UnsignedWaits $waits, Date $day): array
    {
        return $this->gathered(
            $this->findDueBefore($waits, $day),
            $this->watchingServicesWithoutAVersion() ? $this->findServicesDueBefore($waits, $day) : null,
            $day,
        );
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

        foreach ($this->due($waits, $today) as $service) {
            $blocked[(string)$service->contract->id] = __('unsigned contract');
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
     * Services with no version at all whose wait was up on the given day or before it.
     *
     * @param \App\Contracts\Unsigned\UnsignedWaits $waits How long it may go unsigned first.
     * @param \Cake\I18n\Date $today The day being asked about.
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    public function findServicesDue(UnsignedWaits $waits, Date $today): SelectQuery
    {
        $query = $this->servicesBase($today);

        return $query->where(
            $query->expr()->lte($this->serviceDeadline($query, $waits), $today, 'date'),
        );
    }

    /**
     * The same, for the one day the wait ran out on.
     *
     * @param \App\Contracts\Unsigned\UnsignedWaits $waits How long it may go unsigned first.
     * @param \Cake\I18n\Date $day The day the wait is asked to have run out on.
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    public function findServicesBecomingDueOn(UnsignedWaits $waits, Date $day): SelectQuery
    {
        $query = $this->servicesBase(Date::today());

        return $query->where(
            $query->expr()->eq($this->serviceDeadline($query, $waits), $day, 'date'),
        );
    }

    /**
     * The same, for everything that ran out before the given day.
     *
     * @param \App\Contracts\Unsigned\UnsignedWaits $waits How long it may go unsigned first.
     * @param \Cake\I18n\Date $day The day the wait is asked to have run out before.
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    public function findServicesDueBefore(UnsignedWaits $waits, Date $day): SelectQuery
    {
        $query = $this->servicesBase(Date::today());

        return $query->where(
            $query->expr()->lt($this->serviceDeadline($query, $waits), $day, 'date'),
        );
    }

    /**
     * Every running service with no version at all, deadlines or no deadlines.
     *
     * What the whole file holds, for whoever is putting it straight rather than doing today's work:
     * no wait, and no line drawn at the day the office watches from.
     *
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    public function findEveryServiceWithoutAVersion(): SelectQuery
    {
        $today = Date::today();
        $query = $this->services();

        return $query
            ->where([
                $this->serviceIsRunning(),
                $query->expr($this->noVersionInForce($today)),
                $query->expr()->isNotNull($query->expr(self::CHARGED_SINCE)),
            ])
            ->orderBy([self::CHARGED_SINCE => 'DESC']);
    }

    /**
     * Hang both deadlines off a service with no version, the way the versions carry theirs.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query Query to add them to.
     * @param \Cake\I18n\Date $today The day the standing is asked as of.
     * @param \App\Contracts\Unsigned\UnsignedWaits $notify How long before the customer is written to.
     * @param \App\Contracts\Unsigned\UnsignedWaits $block How long before the service is cut off.
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    public function withServiceDeadlines(
        SelectQuery $query,
        Date $today,
        UnsignedWaits $notify,
        UnsignedWaits $block,
    ): SelectQuery {
        $query->selectAlso([
            'notify_due' => $this->serviceDeadlineWhereItApplies($query, $today, $notify),
            'block_due' => $this->serviceDeadlineWhereItApplies($query, $today, $block),
            // What the listing says about the service itself, since there is no version to say it.
            'charged_since' => $query->expr(self::CHARGED_SINCE),
            'covered_until' => $query->expr(self::COVERED_UNTIL),
        ]);

        $query->getSelectTypeMap()->addDefaults([
            'notify_due' => 'date',
            'block_due' => 'date',
            'charged_since' => 'date',
            'covered_until' => 'date',
        ]);

        return $query;
    }

    /**
     * Whether a service with no version of its own is watched at all.
     *
     * The way out for an installation that keeps its papers some other way, and for the first night
     * after this was switched on: the backlog is the office's to go through, not the cron's.
     *
     * @return bool
     */
    public function watchingServicesWithoutAVersion(): bool
    {
        return (bool)Settings::get(self::WITHOUT_VERSION_PATH, true);
    }

    /**
     * The two sources as one list, with everything the letter needs along.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $versions Unsigned versions.
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>|null $services Services with none.
     * @param \Cake\I18n\Date $today The day it is being asked as of.
     * @return list<\App\Contracts\Unsigned\UnsignedService>
     */
    private function gathered(SelectQuery $versions, ?SelectQuery $services, Date $today): array
    {
        $found = [];

        /** @var \App\Model\Entity\ContractVersion $version */
        foreach ($this->withContacts($versions)->all() as $version) {
            if ($version->contract === null) {
                continue;
            }

            $service = UnsignedService::ofVersion($version);
            $found[$service->key()] = $service;
        }

        if ($services !== null) {
            $charged = $this->withServiceDeadlines($services, $today, UnsignedWaits::none(), UnsignedWaits::none());

            /** @var \App\Model\Entity\Contract $contract */
            foreach ($charged->all() as $contract) {
                $since = $contract->has('charged_since') ? $contract->get('charged_since') : null;

                if (!$since instanceof Date) {
                    continue;
                }

                $service = UnsignedService::ofContract($contract, $since);
                $found[$service->key()] = $service;
            }
        }

        return array_values($found);
    }

    /**
     * The people to write to, and what to call the contract in the letter.
     *
     * Asked for whether or not the caller is going to write anything: the blocking needs a contract
     * id and nothing else, and one path that always says who is involved is worth more than two
     * that disagree about it.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query Query to widen.
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    private function withContacts(SelectQuery $query): SelectQuery
    {
        return $query->contain([
            'Contracts' => [
                'Customers' => ['Emails', 'Phones'],
                'ServiceTypes',
                'InstallationAddresses',
            ],
        ]);
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
     * The contracts, as the second source reads them.
     *
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    private function services(): SelectQuery
    {
        return $this->versions->Contracts->find()
            // The contacts come along for the same reason they do on the versions' side: one path
            // that always says who is involved is worth more than two that disagree about it.
            ->contain([
                'Customers' => ['Emails', 'Phones'],
                'ContractStates',
                'ServiceTypes',
                'InstallationAddresses',
            ]);
    }

    /**
     * Running services with no version covering them, before any deadline is applied.
     *
     * @param \Cake\I18n\Date $today The day it is being asked as of.
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    private function servicesBase(Date $today): SelectQuery
    {
        $query = $this->services();

        return $query
            ->where($this->serviceConditions($query, $today))
            ->orderBy([self::CHARGED_SINCE => 'DESC']);
    }

    /**
     * What makes a service with no version one the automation will act on.
     *
     * Written once and used twice, as the versions' conditions are: what narrows the day's work,
     * and what decides whether a deadline may be shown against a row at all.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query Query being built.
     * @param \Cake\I18n\Date $today The day it is being asked as of.
     * @return array<mixed>
     */
    private function serviceConditions(SelectQuery $query, Date $today): array
    {
        return [
            $this->serviceIsRunning(),
            // A version in force is the other source's business. Nothing in force - none ever
            // drawn, or the last of them run out - is this one's.
            $query->expr($this->noVersionInForce($today)),
            // The line the office draws, measured the same way round as it is for a version: by the
            // day the thing speaks about. For a service that is the day we began to charge for it,
            // so a contract billed since 2010 stays out of this however much changed on it lately.
            $query->expr(sprintf(
                "%s >= DATE '%s'",
                self::CHARGED_SINCE,
                $this->considerFrom()->format('Y-m-d'),
            )),
        ];
    }

    /**
     * A service that is being provided and charged for.
     *
     * Both halves are asked, because they are allowed to disagree: nothing is billed before the
     * installation, and a contract waiting for one has no service to chase a signature for. The
     * service type has to be one that keeps versions at all - a reseller's never has one, and
     * chasing a paper that is never going to exist is worse than not chasing.
     *
     * @return array<string, mixed>
     */
    private function serviceIsRunning(): array
    {
        return [
            'ContractStates.active_services' => true,
            'ContractStates.billed' => true,
            'ServiceTypes.have_contract_versions' => true,
        ];
    }

    /**
     * No version of the contract covers the given day.
     *
     * The day is written into the SQL rather than bound, because this sits inside an expression the
     * deadline is built from; it is a date this class was handed, never anything a reader typed.
     *
     * @param \Cake\I18n\Date $today The day nothing may cover.
     * @return string
     */
    private function noVersionInForce(Date $today): string
    {
        return sprintf(
            "NOT EXISTS (
                SELECT 1 FROM contract_versions InForce
                WHERE InForce.contract_id = Contracts.id
                AND InForce.valid_from <= DATE '%1\$s'
                AND (InForce.valid_until IS NULL OR InForce.valid_until >= DATE '%1\$s')
            )",
            $today->format('Y-m-d'),
        );
    }

    /**
     * The day a service with no version runs out of time.
     *
     * The same rule as a version's, with the day we began to charge for the service standing in for
     * the version's start. The anchor falls back on that day as well: we are demonstrably charging
     * for this, so unlike a version it cannot quietly drop out of the watch for want of an
     * installation date - that gap is reported on its own, by MissingInstallationDateCheck.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query Query being built.
     * @param \App\Contracts\Unsigned\UnsignedWaits $waits The two waits it is held to.
     * @return \Cake\Database\Expression\QueryExpression
     */
    private function serviceDeadline(SelectQuery $query, UnsignedWaits $waits): QueryExpression
    {
        return $query->expr(sprintf(
            "GREATEST(COALESCE(%s, %s) + INTERVAL '%d days', %s + INTERVAL '%d days')",
            $this->contractAnchorSql(),
            self::CHARGED_SINCE,
            $waits->after_anchor,
            self::CHARGED_SINCE,
            $waits->after_start,
        ));
    }

    /**
     * That day, but only on a service the automation would ever reach.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query Query being built.
     * @param \Cake\I18n\Date $today The day it is being asked as of.
     * @param \App\Contracts\Unsigned\UnsignedWaits $waits The two waits it is held to.
     * @return \Cake\Database\Expression\CaseStatementExpression
     */
    private function serviceDeadlineWhereItApplies(
        SelectQuery $query,
        Date $today,
        UnsignedWaits $waits,
    ): CaseStatementExpression {
        return $query->expr()
            ->case()
            ->when($this->serviceConditions($query, $today))
            ->then($this->serviceDeadline($query, $waits), 'date');
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
        return $this->anchor()->sql();
    }

    /**
     * The same, asked of the contract rather than of one of its versions.
     *
     * @return string
     */
    private function contractAnchorSql(): string
    {
        return $this->anchor()->contractSql();
    }

    /**
     * Which anchor the office works by.
     *
     * @return \App\Model\Enum\UnsignedDeadlineAnchor
     */
    private function anchor(): UnsignedDeadlineAnchor
    {
        return UnsignedDeadlineAnchor::fromSetting(Settings::getString(
            UnsignedDeadlineAnchor::SETTINGS_PATH,
            UnsignedDeadlineAnchor::Installation->value,
        ));
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
