<?php
declare(strict_types=1);

namespace App\Contracts\Check;

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
     * @param bool $ignore_inactive Whether to count only the contracts that are running.
     * @param string|null $contract_id The one contract being asked about, where there is one.
     * @param string|null $customer_id The one customer being asked about, where there is one.
     */
    public function __construct(
        private ContractsTable $contracts,
        bool $ignore_inactive = true,
        ?string $contract_id = null,
        ?string $customer_id = null,
    ) {
        parent::__construct($ignore_inactive, $contract_id, $customer_id);
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

        if ($this->ignore_inactive) {
            $query->where(['Contracts.id IN' => $this->activeContractIds()]);
        }

        return $this->scoped($query);
    }
}
