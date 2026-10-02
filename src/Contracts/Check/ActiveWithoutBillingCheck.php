<?php
declare(strict_types=1);

namespace App\Contracts\Check;

use App\Check\CheckScope;
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
 * There is only the one answer to give. A contract billed for something that has since ended
 * is not billed now, which is the whole of what this is about - so unlike its neighbours it
 * has no longer history to fall back on, and lifting the filter leaves it saying the same.
 * Anything else would have a contract's own page, which lifts the filter to see everything,
 * shown less than the listing that led there.
 */
class ActiveWithoutBillingCheck extends AbstractContractCheck
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
     * @return bool
     */
    #[Override]
    public function hasAWiderReading(): bool
    {
        return false;
    }

    /**
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    #[Override]
    public function find(): SelectQuery
    {
        $billed = $this->contracts->Billings
            ->find('activeOrFuture')
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
