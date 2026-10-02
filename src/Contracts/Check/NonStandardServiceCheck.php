<?php
declare(strict_types=1);

namespace App\Contracts\Check;

use App\Check\CheckScope;
use App\Model\Table\BillingsTable;
use Cake\ORM\Query\SelectQuery;
use Override;

/**
 * A billing that does not simply say which service is being sold.
 *
 * A billing is meant to point at a service and take its price from there. One that carries
 * its own text, its own price, or no service at all was written by hand - sometimes for a
 * good reason, and sometimes because the service it wanted did not exist yet and never got
 * made afterwards.
 *
 * It is not a fault on its own, which is why it says what it is rather than that it is wrong.
 * What it is good for is finding the services the price list is still missing.
 */
class NonStandardServiceCheck extends AbstractContractCheck
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
        return 'non_standard_service';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Billed by Hand Rather Than for a Service');
    }

    /**
     * @return string
     */
    #[Override]
    public function emptyMessage(): string
    {
        return __('Every billing simply names the service it is for.');
    }

    /**
     * Worth looking at rather than worth fixing, so it keeps to the overview and is asked for.
     *
     * @return bool
     */
    #[Override]
    public function onDashboard(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    #[Override]
    public function optional(): bool
    {
        return true;
    }

    /**
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    #[Override]
    public function find(): SelectQuery
    {
        $query = $this->billings->find($this->scope->ignore_inactive ? 'activeOrFuture' : 'all');

        $query
            ->contain(['Contracts' => ['Customers'], 'Services'])
            ->where([
                'OR' => [
                    'Billings.text IS NOT' => null,
                    'Billings.price IS NOT' => null,
                    'Billings.service_id IS' => null,
                ],
            ])
            ->orderBy(['Billings.billing_from' => 'DESC']);

        $this->onlyWhatIsRunning($query);

        return $this->scoped($query);
    }
}
