<?php
declare(strict_types=1);

namespace App\Contracts\Check;

use App\Model\Table\ContractsTable;
use Cake\ORM\Query\SelectQuery;
use Override;

/**
 * A contract that provides nothing and is still being charged for.
 *
 * The mirror image of a service nobody pays for, and the one the customer finds first. Either
 * the billing was never given an end when the contract stopped, or the state is wrong and the
 * service is in fact still running.
 *
 * Lifting the filter brings in the contracts that were billed after they stopped and are not
 * any more - the same mistake, already over, which is the history rather than today's work.
 */
class InactiveWithBillingCheck extends AbstractContractCheck
{
    /**
     * @param \App\Model\Table\ContractsTable $contracts Contracts table.
     * @param bool $ignore_inactive Whether to keep to billings that are still running.
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
        return 'inactive_with_billing';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Billed Without Providing Services');
    }

    /**
     * @return string
     */
    #[Override]
    public function emptyMessage(): string
    {
        return __('No contract is billed for a service it is not providing.');
    }

    /**
     * The subject is a contract that has stopped, so keeping to the ones that are running
     * would empty this check rather than narrow it. What it keeps to is the billings.
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
                'Contracts.id NOT IN' => $this->activeContractIds(),
                'Contracts.id IN' => $billed,
            ])
            ->orderBy(['Contracts.nid' => 'DESC']);

        return $this->scoped($query);
    }
}
