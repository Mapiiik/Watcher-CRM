<?php
declare(strict_types=1);

namespace App\Contracts\Check;

use App\Check\CheckScope;
use App\Model\Table\BillingsTable;
use Cake\ORM\Association;
use Cake\ORM\Query\SelectQuery;
use Override;

/**
 * One line billed twice over the same stretch of time on one contract.
 *
 * Almost always the previous billing was left open when the new one was written, so the
 * customer is charged for both from that day on. It is the mirror image of a break, and the
 * more expensive way round: a break nobody notices costs us, an overlap nobody notices costs
 * the customer, and they do notice.
 *
 * What counts as one line is what counts as one for a break, and for the same reason: a connection profile
 * says which line a service is, and a contract has one of those at a time. So two tariffs
 * running at once are an overlap however differently they are named - the customer is paying
 * for the connection twice - while a fee standing beside the line overlaps only itself.
 *
 * The earlier of the two is reported, because that is the one whose end is missing or wrong.
 */
class OverlappingBillingsCheck extends AbstractContractCheck
{
    /**
     * Another billing of the same line starts before this one has finished.
     *
     * A billing with no end reaches forever, which `infinity` says without the query having to
     * be written twice. The pair is ordered so that only the earlier of the two is reported -
     * two lines about one overlap would read as two overlaps.
     */
    private const RUNS_INTO_ANOTHER = <<<'SQL'
        EXISTS (
            SELECT 1 FROM billings other
            LEFT JOIN services other_service ON other_service.id = other.service_id
            WHERE other.contract_id = Billings.contract_id
              AND
        SQL . ' ' . self::SAME_LINE . ' ' . <<<'SQL'
              AND (other.billing_from, other.id) > (Billings.billing_from, Billings.id)
              AND other.billing_from <= COALESCE(Billings.billing_until, 'infinity'::date)
        )
        SQL;

    /**
     * The day the second billing starts, so the listing can say where the overlap begins.
     */
    private const OVERLAPS_FROM = <<<'SQL'
        (
            SELECT MIN(other.billing_from) FROM billings other
            LEFT JOIN services other_service ON other_service.id = other.service_id
            WHERE other.contract_id = Billings.contract_id
              AND
        SQL . ' ' . self::SAME_LINE . ' ' . <<<'SQL'
              AND (other.billing_from, other.id) > (Billings.billing_from, Billings.id)
              AND other.billing_from <= COALESCE(Billings.billing_until, 'infinity'::date)
        )
        SQL;

    /**
     * What the other one is for, so that a pair of differently named tariffs reads as the
     * overlap it is rather than as one line listed for no visible reason.
     */
    private const OVERLAPS_WITH = <<<'SQL'
        (
            SELECT other_service.name
            FROM billings other
            LEFT JOIN services other_service ON other_service.id = other.service_id
            WHERE other.contract_id = Billings.contract_id
              AND
        SQL . ' ' . self::SAME_LINE . ' ' . <<<'SQL'
              AND (other.billing_from, other.id) > (Billings.billing_from, Billings.id)
              AND other.billing_from <= COALESCE(Billings.billing_until, 'infinity'::date)
            ORDER BY other.billing_from, other.id
            LIMIT 1
        )
        SQL;

    /**
     * @param \App\Model\Table\BillingsTable $billings Billings table.
     * @param \App\Check\CheckScope $scope What is being asked about, and how widely.
     */
    public function __construct(
        private BillingsTable $billings,
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
            ->select(['overlaps_with' => $query->expr(self::OVERLAPS_WITH)])
            ->select($this->billings)
            ->select($this->billings->Contracts)
            ->select($this->billings->Services)
            // apart, as the join of the running contracts leaves the customer out of this query
            ->contain(['Contracts' => ['Customers' => ['strategy' => Association::STRATEGY_SELECT]], 'Services'])
            ->where([$query->expr(self::RUNS_INTO_ANOTHER)])
            ->orderBy(['Billings.billing_from' => 'ASC']);

        $this->onlyWhatIsRunning($query);

        return $this->scoped($query);
    }
}
