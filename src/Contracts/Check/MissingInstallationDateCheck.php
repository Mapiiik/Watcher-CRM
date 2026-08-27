<?php
declare(strict_types=1);

namespace App\Contracts\Check;

use App\Model\Table\ContractsTable;
use Cake\ORM\Query\SelectQuery;
use Override;

/**
 * A service that is installed somewhere, with no day saying when.
 *
 * The date is what every other date on the contract is read against - a billing before it, a
 * piece of equipment lent before it - so a contract without one cannot be checked at all.
 *
 * Only the service types that are installed somewhere are asked. Hosting is not put in, so it
 * has no day it was put in, and the flag saying an installation address is required is what
 * tells the two apart - which means a service type added later is judged by what it is rather
 * than by a list somebody has to remember to extend.
 */
class MissingInstallationDateCheck extends AbstractContractCheck
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
        return 'missing_installation_date';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Installed With No Installation Date');
    }

    /**
     * @return string
     */
    #[Override]
    public function emptyMessage(): string
    {
        return __('Every contract that is installed somewhere says when it was.');
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
                'ServiceTypes.installation_address_required' => true,
                'Contracts.installation_date IS' => null,
            ])
            ->orderBy(['Contracts.nid' => 'DESC']);

        if ($this->ignore_inactive) {
            $query->where(['Contracts.id IN' => $this->activeContractIds()]);
        }

        return $this->scoped($query);
    }
}
