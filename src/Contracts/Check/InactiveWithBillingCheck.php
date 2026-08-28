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
 * What makes it a fault is the billing reaching past the day the contract itself stopped, so
 * that is what is asked - not whether the billing is running now. A contract wound up properly,
 * with the billing ended on the very day the service was, is not a finding on any day of any
 * month, and there is no wider reading of it to give.
 */
class InactiveWithBillingCheck extends AbstractContractCheck
{
    /**
     * A billing that runs on past the day the contract stopped.
     *
     * Held against the contract's own end rather than against today. Asked about today it would
     * report every contract wound up during the current month - the billing ends on the day the
     * service does, which is still "this month" until the month turns over.
     *
     * A contract stopped without a day recorded has nothing to hold the billing against, so
     * there today is the only thing left to ask.
     */
    private const BILLED_PAST_ITS_OWN_END = <<<'SQL'
        EXISTS (
            SELECT 1 FROM billings billed
            WHERE billed.contract_id = Contracts.id
              AND (
                billed.billing_until IS NULL
                OR billed.billing_until > COALESCE(Contracts.termination_date, CURRENT_DATE)
              )
        )
        SQL;

    /**
     * How far the billing reaches, so the listing can say why the contract is on it.
     */
    private const BILLED_UNTIL = <<<'SQL'
        (
            SELECT MAX(reaching.billing_until) FROM billings reaching
            WHERE reaching.contract_id = Contracts.id
        )
        SQL;

    /**
     * Whether any of it has no end at all, which `MAX` passes over.
     */
    private const BILLED_OPEN = <<<'SQL'
        EXISTS (
            SELECT 1 FROM billings open_ended
            WHERE open_ended.contract_id = Contracts.id
              AND open_ended.billing_until IS NULL
        )
        SQL;

    /**
     * @param \App\Model\Table\ContractsTable $contracts Contracts table.
     * @param bool $ignore_inactive Kept for the shape of the family; this check has only the
     *   one reading. {@see self::hasAWiderReading()}
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
     * @return bool
     */
    #[Override]
    public function hasAWiderReading(): bool
    {
        return false;
    }

    /**
     * The subject is a contract that has stopped, so keeping to the ones that are running
     * would empty this check rather than narrow it - and asking instead which contracts were
     * ever billed answers about nearly every one of them, which is what a contract's own page
     * was being shown.
     *
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    #[Override]
    public function find(): SelectQuery
    {
        $query = $this->contracts
            ->find()
            ->contain(['Customers', 'ServiceTypes', 'ContractStates'])
            ->where([
                'Contracts.id NOT IN' => $this->activeContractIds(),
                $this->contracts->find()->expr(self::BILLED_PAST_ITS_OWN_END),
            ])
            ->orderBy(['Contracts.termination_date' => 'DESC']);

        $query->getSelectTypeMap()->addDefaults(['billed_until' => 'date']);

        $query
            ->select(['billed_until' => $query->expr(self::BILLED_UNTIL)])
            ->select(['billed_open' => $query->expr(self::BILLED_OPEN)])
            ->select($this->contracts)
            ->select($this->contracts->Customers)
            ->select($this->contracts->ServiceTypes)
            ->select($this->contracts->ContractStates);

        return $this->scoped($query);
    }
}
