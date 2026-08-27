<?php
declare(strict_types=1);

namespace App\Contracts\Check;

use App\Check\AbstractCheck;
use Cake\ORM\Query\SelectQuery;

/**
 * Shared ground for contract checks.
 *
 * Two things every one of them is given. Whether it keeps to what is running, which each
 * applies to its own subject - the answer has to be about the record being reported rather
 * than about something else its contract happens to have. And, when the checks are asked
 * about one contract rather than about the whole file, which contract that is.
 */
abstract class AbstractContractCheck extends AbstractCheck implements ContractCheckInterface
{
    /**
     * @param bool $ignore_inactive Whether the check keeps to what is running.
     * @param string|null $contract_id The one contract being asked about, where there is one.
     */
    public function __construct(
        protected bool $ignore_inactive = true,
        protected ?string $contract_id = null,
    ) {
    }

    /**
     * Narrow a query to the one contract being asked about, where there is one.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query Query to narrow.
     * @param string $field The field holding the contract, qualified by its alias.
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    protected function scopedToContract(SelectQuery $query, string $field): SelectQuery
    {
        if ($this->contract_id !== null) {
            $query->where([$field => $this->contract_id]);
        }

        return $query;
    }

    /**
     * Narrow a query on a record hanging off a contract to the contracts that are running.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query Query to narrow.
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    protected function onlyRunningContracts(SelectQuery $query): SelectQuery
    {
        return $query->innerJoinWith(
            'Contracts.ContractStates',
            fn(SelectQuery $states): SelectQuery => $states->where(['ContractStates.active_services' => true]),
        );
    }
}
