<?php
declare(strict_types=1);

namespace App\Contracts\Check;

use App\Check\CheckScope;
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
            ->contain(['Contracts' => ['Customers'], 'Services'])
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

        $this->onlyWhatIsRunning($query);

        return $this->scoped($query);
    }
}
