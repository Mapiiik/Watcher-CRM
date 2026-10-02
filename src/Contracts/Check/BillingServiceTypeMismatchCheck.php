<?php
declare(strict_types=1);

namespace App\Contracts\Check;

use App\Check\CheckScope;
use App\Model\Table\BillingsTable;
use Cake\ORM\Query\SelectQuery;
use Override;

/**
 * A contract billed for a service belonging to another kind of contract.
 *
 * A wireless tariff on a cable contract, a hosting fee on an internet one. Nothing refuses it
 * when the billing is written, and afterwards every report that counts what is sold by kind
 * of service counts it under the wrong heading - including the ones that go to the regulator.
 */
class BillingServiceTypeMismatchCheck extends AbstractContractCheck
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
        return 'billing_service_type_mismatch';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Billed for Another Kind of Service');
    }

    /**
     * @return string
     */
    #[Override]
    public function emptyMessage(): string
    {
        return __('Every billing is for a service of the kind its contract is.');
    }

    /**
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    #[Override]
    public function find(): SelectQuery
    {
        $query = $this->billings->find();

        $query
            ->contain(['Contracts' => ['Customers', 'ServiceTypes'], 'Services' => ['ServiceTypes']])
            ->innerJoinWith('Services')
            ->innerJoinWith('Contracts')
            ->where([
                $query->expr()->notEq(
                    $query->identifier('Services.service_type_id'),
                    $query->identifier('Contracts.service_type_id'),
                ),
            ])
            ->orderBy(['Billings.billing_from' => 'DESC']);

        $this->onlyWhatIsRunning($query);

        return $this->scoped($query);
    }
}
