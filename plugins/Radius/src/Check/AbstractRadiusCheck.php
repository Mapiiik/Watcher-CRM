<?php
declare(strict_types=1);

namespace Radius\Check;

use App\Check\CheckScope;
use App\Contracts\Check\AbstractContractCheck;
use App\Model\Entity\Contract;
use App\Model\Table\ContractsTable;
use Cake\Collection\CollectionInterface;
use Cake\ORM\Query\SelectQuery;
use Override;

/**
 * Shared ground for the checks holding the RADIUS accounts against the tariffs.
 *
 * The accounts live in another database, so they are compared in {@see \Radius\Check\TariffComparison}
 * and the finding is the contract, with its accounts attached as `radius_findings`.
 */
abstract class AbstractRadiusCheck extends AbstractContractCheck
{
    private TariffComparison $comparison;

    /**
     * @param \App\Model\Table\ContractsTable $contracts Contracts table.
     * @param \App\Check\CheckScope $scope What is being asked about, and how widely.
     * @param \Radius\Check\TariffComparison|null $comparison The comparison, shared with the other checks.
     */
    public function __construct(
        private ContractsTable $contracts,
        CheckScope $scope = new CheckScope(),
        ?TariffComparison $comparison = null,
    ) {
        parent::__construct($scope);

        $this->comparison = $comparison ?? new TariffComparison($scope);
    }

    /**
     * The accounts this check reports.
     *
     * @param \Radius\Check\TariffComparison $comparison The comparison.
     * @return list<\Radius\Check\AccountFinding>
     */
    abstract protected function findings(TariffComparison $comparison): array;

    /**
     * @return string|null
     */
    #[Override]
    protected function contractField(): ?string
    {
        return 'Contracts.id';
    }

    /**
     * An active account is running whatever state its contract is in.
     *
     * @return bool
     */
    #[Override]
    public function hasAWiderReading(): bool
    {
        return false;
    }

    /**
     * @return string
     */
    #[Override]
    public function element(): string
    {
        return 'Radius.ContractChecks/' . $this->template();
    }

    /**
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    #[Override]
    public function find(): SelectQuery
    {
        $findings = [];
        foreach ($this->findings($this->comparison) as $finding) {
            $findings[$finding->account->contract_id][] = $finding;
        }

        $query = $this->contracts
            ->find()
            ->contain(['Customers', 'ContractStates'])
            ->whereInList('Contracts.id', array_keys($findings), ['allowEmpty' => true])
            ->orderBy(['Contracts.nid' => 'DESC'])
            ->formatResults(fn(CollectionInterface $contracts): CollectionInterface => $contracts->map(
                function (Contract $contract) use ($findings): Contract {
                    $contract->set('radius_findings', $findings[$contract->id] ?? []);
                    $contract->setDirty('radius_findings', false);

                    return $contract;
                },
            ));

        return $this->scoped($query);
    }
}
