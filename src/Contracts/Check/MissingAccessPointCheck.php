<?php
declare(strict_types=1);

namespace App\Contracts\Check;

use App\Check\CheckScope;
use App\Model\Table\ContractsTable;
use Cake\ORM\Query\SelectQuery;
use Override;

/**
 * A service that is running, with nothing on record saying where it is served from.
 *
 * Without the access point the contract is missing from everything that reads the network
 * backwards: what an outage takes down, what a point carries, where to go when it stops.
 *
 * As with the installation date, the flag on the service type is what decides who is asked -
 * a tariff served from nowhere in particular is not missing anything.
 */
class MissingAccessPointCheck extends AbstractContractCheck
{
    /**
     * @param \App\Model\Table\ContractsTable $contracts Contracts table.
     * @param \App\Check\CheckScope $scope What is being asked about, and how widely.
     */
    public function __construct(
        private ContractsTable $contracts,
        CheckScope $scope = new CheckScope(),
    ) {
        parent::__construct($scope);
    }

    /**
     * The finding is the contract itself, which four of these have in common, so they are
     * listed by the same template rather than by four copies of it.
     *
     * @return string
     */
    #[Override]
    public function template(): string
    {
        return 'contract';
    }

    /**
     * @return string|null
     */
    #[Override]
    protected function contractField(): ?string
    {
        return 'Contracts.id';
    }

    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'missing_access_point';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Served From No Access Point');
    }

    /**
     * @return string
     */
    #[Override]
    public function emptyMessage(): string
    {
        return __('Every contract that is served from an access point names one.');
    }

    /**
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    #[Override]
    public function find(): SelectQuery
    {
        $query = $this->contracts
            ->find()
            ->contain(['Customers', 'ServiceTypes', 'ContractStates'])
            ->where([
                'ServiceTypes.access_point_required' => true,
                'Contracts.access_point_id IS' => null,
            ])
            ->orderBy(['Contracts.nid' => 'DESC']);

        if ($this->scope->ignore_inactive) {
            $query->where(['Contracts.id IN' => $this->activeContractIds()]);
        }

        return $this->scoped($query);
    }
}
