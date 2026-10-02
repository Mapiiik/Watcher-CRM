<?php
declare(strict_types=1);

namespace App\Contracts\Check;

use App\Check\CheckScope;
use App\Model\Table\ContractProposalsTable;
use Cake\ORM\Query\SelectQuery;
use Override;

/**
 * Shared ground for the checks that read the papers drawn up for a contract.
 *
 * Five of them ask five different things of the same records, and they were each saying how to
 * reach those records. What they have in common is written here, as it already is on the
 * customer's side: where the contract and the customer are, and what a row has to carry for the
 * listing to print it.
 *
 * What is NOT here is how widely each of them reads. A proposal nobody has sent and a proposal
 * nobody has applied are late for different reasons, and one of them is measured against a
 * contract that still serves somebody while the other is measured against the day it speaks
 * about - so each says that for itself.
 */
abstract class AbstractContractProposalCheck extends AbstractContractCheck
{
    /**
     * @param \App\Model\Table\ContractProposalsTable $proposals Contract version proposals table.
     * @param \App\Check\CheckScope $scope What is being asked about, and how widely.
     */
    public function __construct(
        protected ContractProposalsTable $proposals,
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
        return 'ContractProposals.contract_id';
    }

    /**
     * The papers this check may report, before it says what it is looking for.
     *
     * Whether they went out and whether anything came back is the envelope's to say, and the rows
     * print it, so the envelope is read as well as joined. Every paper belongs to one, so joining
     * it narrows nothing.
     *
     * @param string|null $finder Which papers are candidates at all, where the check is only ever
     *   about some of them - the finders of {@see \App\Model\Table\ContractProposalsTable}.
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    protected function candidates(?string $finder = null): SelectQuery
    {
        $query = $finder === null ? $this->proposals->find() : $this->proposals->find($finder);

        return $query
            ->contain(['Contracts' => ['Customers'], 'ContractVersions', 'CustomerProposals'])
            ->innerJoinWith('Contracts')
            ->innerJoinWith('CustomerProposals');
    }
}
