<?php
declare(strict_types=1);

namespace App\Contracts\Check;

use App\Model\Table\BillingsTable;
use Cake\ORM\Query\SelectQuery;
use Override;

/**
 * One service billed twice over the same stretch of time on one contract.
 *
 * Almost always the previous billing was left open when the new one was written, so the
 * customer is charged for both from that day on. It is the mirror image of a break, and the
 * more expensive way round: a break nobody notices costs us, an overlap nobody notices costs
 * the customer, and they do notice.
 *
 * The earlier of the two is reported, because that is the one whose end is missing or wrong.
 */
class OverlappingBillingsCheck extends AbstractContractCheck
{
    /**
     * Another billing of the same service starts before this one has finished.
     *
     * A billing with no end reaches forever, which `infinity` says without the query having to
     * be written twice. The pair is ordered so that only the earlier of the two is reported -
     * two lines about one overlap would read as two overlaps.
     */
    private const RUNS_INTO_ANOTHER = <<<'SQL'
        EXISTS (
            SELECT 1 FROM billings other
            WHERE other.contract_id = Billings.contract_id
              AND other.service_id IS NOT DISTINCT FROM Billings.service_id
              AND (other.billing_from, other.id) > (Billings.billing_from, Billings.id)
              AND other.billing_from <= COALESCE(Billings.billing_until, 'infinity'::date)
        )
        SQL;

    /**
     * The day the second billing starts, so the listing can say where the overlap begins.
     */
    private const OVERLAPS_FROM = <<<'SQL'
        (
            SELECT MIN(overlapping.billing_from) FROM billings overlapping
            WHERE overlapping.contract_id = Billings.contract_id
              AND overlapping.service_id IS NOT DISTINCT FROM Billings.service_id
              AND (overlapping.billing_from, overlapping.id) > (Billings.billing_from, Billings.id)
              AND overlapping.billing_from <= COALESCE(Billings.billing_until, 'infinity'::date)
        )
        SQL;

    /**
     * @param \App\Model\Table\BillingsTable $billings Billings table.
     * @param bool $ignore_inactive Whether to count only the contracts that are running.
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
        return 'overlapping_billings';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Overlapping Billings');
    }

    /**
     * @return string
     */
    #[Override]
    public function emptyMessage(): string
    {
        return __('No service is billed twice over the same stretch of time.');
    }

    /**
     * One row per overlapping pair, the earlier of the two, with the day the overlap begins.
     *
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    #[Override]
    public function find(): SelectQuery
    {
        $query = $this->billings->find();

        $query->getSelectTypeMap()->addDefaults(['overlaps_from' => 'date']);

        $query
            ->select(['overlaps_from' => $query->expr(self::OVERLAPS_FROM)])
            ->select($this->billings)
            ->select($this->billings->Contracts)
            ->select($this->billings->Services)
            ->contain(['Contracts', 'Services'])
            ->where([$query->expr(self::RUNS_INTO_ANOTHER)])
            ->orderBy(['Billings.billing_from' => 'ASC']);

        if ($this->ignore_inactive) {
            $this->onlyRunningContracts($query);
        }

        return $this->scoped($query);
    }
}
