<?php
declare(strict_types=1);

namespace App\Contracts\Check;

use App\Model\Table\BillingsTable;
use Cake\ORM\Query\SelectQuery;
use Override;

/**
 * A billing over a stretch of time that cannot exist.
 *
 * Either it ends before it begins, in which case it bills nothing at all and nobody finds out
 * until the invoice does not arrive; or one of its days names a year that cannot be right.
 * The file holds days like `0001-01-01` and `2027-09-01` ending on `2026-01-10` - a digit
 * mistyped, or a billing cut short to a termination date that lies before it starts.
 *
 * There is nothing to weigh up in either case, which is why they are one finding: both are
 * put right by typing the day that was meant.
 */
class ImpossibleBillingPeriodCheck extends AbstractContractCheck
{
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
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'impossible_billing_period';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Impossible Billing Period');
    }

    /**
     * @return string
     */
    #[Override]
    public function emptyMessage(): string
    {
        return __('Every billing runs over a stretch of time that can exist.');
    }

    /**
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    #[Override]
    public function find(): SelectQuery
    {
        $query = $this->billings->find();

        $query
            ->contain(['Contracts', 'Services'])
            ->where([
                $query->expr()->or([
                    // ends before it begins, so it bills nothing at all
                    $query->expr()->lt(
                        'Billings.billing_until',
                        $query->identifier('Billings.billing_from'),
                    ),
                    $this->implausibleDate($query, 'Billings.billing_from'),
                    $this->implausibleDate($query, 'Billings.billing_until'),
                ]),
            ])
            ->orderBy(['Billings.billing_from' => 'ASC']);

        if ($this->ignore_inactive) {
            $this->onlyRunningContracts($query);
        }

        return $this->scoped($query, 'Billings.contract_id');
    }
}
