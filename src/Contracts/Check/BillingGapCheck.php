<?php
declare(strict_types=1);

namespace App\Contracts\Check;

use App\Model\Table\BillingsTable;
use Cake\ORM\Query\SelectQuery;
use Override;

/**
 * A service that stops being billed and starts again later, with nothing in between.
 *
 * Billings of one service on one contract are meant to run end to end: one ends, the next
 * begins the day after. A break means those months are invoiced to nobody, and nothing says
 * so until the money fails to arrive. It is almost always a mistyped year on the billing that
 * follows - seven contracts signed in one batch carry the same one.
 *
 * The billing reported is the one *before* the break, because that is what is either
 * extended or whose successor is corrected.
 *
 * A service that was genuinely paused and later resumed looks exactly the same, which is why
 * only breaks that have not finished yet are counted by default: those are the ones still
 * worth doing something about.
 *
 * What counts as one run is the line rather than the service. A queue carries the speeds and
 * the kind of service the regulator is told about - it is what says which line this is - so a
 * contract has one such service at a time and a tariff giving way to another is a change of
 * tariff, not a month nobody paid for. A service without a queue is a fee or an add-on
 * standing beside the line: a static address or a television package running alongside does
 * not pay for the connection, so it cannot fill a break in it either.
 */
class BillingGapCheck extends AbstractContractCheck
{
    /**
     * Another billing of the same run picks up, but later than the day after this one ends.
     *
     * Two billings are of one run when both are for a tariff - whichever tariff - or when they
     * name the very same service. `IS NOT DISTINCT FROM` so that billings carrying no service
     * at all form one run rather than none.
     */
    private const RESUMES_LATER = <<<'SQL'
        EXISTS (
            SELECT 1 FROM billings later
            LEFT JOIN services later_service ON later_service.id = later.service_id
            WHERE later.id <> Billings.id
              AND later.contract_id = Billings.contract_id
              AND (
                (later_service.queue_id IS NOT NULL AND Services.queue_id IS NOT NULL)
                OR later.service_id IS NOT DISTINCT FROM Billings.service_id
              )
              AND later.billing_from > Billings.billing_until + 1
        )
        SQL;

    /**
     * Nothing of the same run starts within the break, so this really is the last billing
     * before it. Without this every earlier billing of a long run would be reported as well.
     */
    private const NOTHING_FOLLOWS_IT = <<<'SQL'
        NOT EXISTS (
            SELECT 1 FROM billings covering
            LEFT JOIN services covering_service ON covering_service.id = covering.service_id
            WHERE covering.contract_id = Billings.contract_id
              AND (
                (covering_service.queue_id IS NOT NULL AND Services.queue_id IS NOT NULL)
                OR covering.service_id IS NOT DISTINCT FROM Billings.service_id
              )
              AND covering.billing_from > Billings.billing_from
              AND covering.billing_from <= Billings.billing_until + 1
        )
        SQL;

    /**
     * Billing has not picked up again yet. Together with the clause above this says the break
     * is still ahead: what is already behind cannot be invoiced after the fact.
     */
    private const BREAK_NOT_OVER = <<<'SQL'
        NOT EXISTS (
            SELECT 1 FROM billings resumed
            LEFT JOIN services resumed_service ON resumed_service.id = resumed.service_id
            WHERE resumed.id <> Billings.id
              AND resumed.contract_id = Billings.contract_id
              AND (
                (resumed_service.queue_id IS NOT NULL AND Services.queue_id IS NOT NULL)
                OR resumed.service_id IS NOT DISTINCT FROM Billings.service_id
              )
              AND resumed.billing_from > Billings.billing_until + 1
              AND resumed.billing_from <= CURRENT_DATE
        )
        SQL;

    /**
     * The day billing picks up again, so the listing can say how long the break is without
     * asking a second time per row.
     */
    private const RESUMES_ON = <<<'SQL'
        (
            SELECT MIN(resumes.billing_from) FROM billings resumes
            LEFT JOIN services resumes_service ON resumes_service.id = resumes.service_id
            WHERE resumes.id <> Billings.id
              AND resumes.contract_id = Billings.contract_id
              AND (
                (resumes_service.queue_id IS NOT NULL AND Services.queue_id IS NOT NULL)
                OR resumes.service_id IS NOT DISTINCT FROM Billings.service_id
              )
              AND resumes.billing_from > Billings.billing_until + 1
        )
        SQL;

    /**
     * @param \App\Model\Table\BillingsTable $billings Billings table.
     * @param bool $ignore_inactive Whether to count only breaks that are still ahead.
     * @param string|null $contract_id The one contract being asked about, where there is one.
     * @param string|null $customer_id The one customer being asked about, where there is one.
     */
    public function __construct(
        private BillingsTable $billings,
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
        return 'Billings.contract_id';
    }

    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'billing_gap';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Gap Between Consecutive Billings');
    }

    /**
     * @return string
     */
    #[Override]
    public function emptyMessage(): string
    {
        return __('No service stops being billed and starts again later.');
    }

    /**
     * One row per billing that a break follows, with the day billing picks up again.
     *
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    #[Override]
    public function find(): SelectQuery
    {
        $query = $this->billings->find();

        // the day is worked out by the database, so it arrives untyped unless it is said what
        // it is - and a listing that prints one date differently from the next reads as a fault
        $query->getSelectTypeMap()->addDefaults(['resumes_on' => 'date']);

        $query
            ->select(['resumes_on' => $query->expr(self::RESUMES_ON)])
            ->select($this->billings)
            ->select($this->billings->Contracts)
            ->select($this->billings->Services)
            ->contain(['Contracts', 'Services'])
            ->where([
                'Billings.billing_until IS NOT' => null,
                $query->expr(self::RESUMES_LATER),
                $query->expr(self::NOTHING_FOLLOWS_IT),
            ])
            ->orderBy(['Billings.billing_until' => 'ASC']);

        if ($this->ignore_inactive) {
            $this->onlyRunningContracts($query)->where([$query->expr(self::BREAK_NOT_OVER)]);
        }

        return $this->scoped($query);
    }
}
