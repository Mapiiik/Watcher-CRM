<?php
declare(strict_types=1);

namespace App\Contracts\Check;

use App\Model\Table\ContractsTable;
use Cake\ORM\Query\SelectQuery;
use Override;

/**
 * A contract providing services that nobody is being charged for.
 *
 * The state says the service is running, and no billing on the contract covers today. Either
 * somebody is getting the service for nothing, or the state is wrong and the service stopped
 * without anybody saying so - and both are worth a minute.
 *
 * Lifting the filter narrows this rather than widening it: what is left is the contracts that
 * have never been billed at all, which is a different and much smaller thing to put right.
 */
class ActiveWithoutBillingCheck extends AbstractContractCheck
{
    /**
     * @param \App\Model\Table\ContractsTable $contracts Contracts table.
     * @param bool $ignore_inactive Whether a billing that has ended still counts as billing.
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
        return 'active_without_billing';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Providing Services Without Billing');
    }

    /**
     * @return string
     */
    #[Override]
    public function emptyMessage(): string
    {
        return __('Every contract providing services is billed for.');
    }

    /**
     * The subject is a contract that is running, so that is not what the filter narrows -
     * what it narrows is which billings count as billing.
     *
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    #[Override]
    public function find(): SelectQuery
    {
        $billed = $this->contracts->Billings
            ->find($this->ignore_inactive ? 'activeOrFuture' : 'all')
            ->select(['Billings.contract_id'], true);

        $query = $this->contracts
            ->find()
            ->contain(['Customers', 'ServiceTypes', 'ContractStates'])
            ->where([
                'Contracts.id IN' => $this->activeContractIds(),
                'Contracts.id NOT IN' => $billed,
            ])
            ->orderBy(['Contracts.nid' => 'DESC']);

        return $this->scoped($query);
    }
}
