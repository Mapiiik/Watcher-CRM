<?php
declare(strict_types=1);

namespace App\BulkMessages\Filter;

use Cake\ORM\Query\SelectQuery;

/**
 * Common base for the contract-state flag filters.
 *
 * Each concrete filter narrows the recipients to customers who have at least one
 * contract whose {@see \App\Model\Entity\ContractState} carries a given boolean
 * flag (e.g. `active_services` or `billed`). The filter is a single checkbox
 * that is checked by default, so the wizard starts scoped to customers with a
 * qualifying contract; unchecking it includes everyone.
 *
 * The same flag also narrows the *contained* contracts used to build the
 * access-point preview, so contracts in a non-qualifying state never surface a
 * customer under an access point they would otherwise be hidden from
 * (see {@see ContractScopedFilterInterface}).
 */
abstract class AbstractContractStateFlagFilter extends AbstractBulkRecipientFilter implements
    ContractScopedFilterInterface
{
    /**
     * Boolean ContractStates column a contract's state must have set to qualify.
     *
     * @return string
     */
    abstract protected function stateFlagColumn(): string;

    /**
     * Label shown next to the checkbox.
     *
     * @return string
     */
    abstract protected function controlLabel(): string;

    /**
     * @inheritDoc
     */
    public function defaultValue(): mixed
    {
        // this filter restricts by default, so the restriction must hold even
        // for a wizard that never submitted the filter step
        return true;
    }

    /**
     * @inheritDoc
     */
    public function controls(mixed $value): array
    {
        // normally the seeded default (true) or the stored bool; the null
        // fallback only covers wizard state written before defaults existed
        $checked = $value === null ? true : (bool)$value;

        return [
            [
                'name' => $this->id(),
                'options' => [
                    'type' => 'checkbox',
                    'label' => $this->controlLabel(),
                    'checked' => $checked,
                ],
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function buildValue(array $data): mixed
    {
        // always store an explicit bool so an unchecked box is remembered as
        // "off" rather than falling back to the checked-by-default render
        return (bool)($data[$this->id()] ?? false);
    }

    /**
     * @inheritDoc
     */
    public function containedContractConditions(mixed $value): ?array
    {
        if ($value !== true) {
            return null;
        }

        return ['Contracts.contract_state_id IN' => $this->qualifyingStateIds()];
    }

    /**
     * Subquery of the ContractStates ids whose flag column is true.
     *
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\ContractState>
     */
    private function qualifyingStateIds(): SelectQuery
    {
        return $this->customerMessages->Customers->Contracts->ContractStates
            ->find()
            ->select(['ContractStates.id'])
            ->where(['ContractStates.' . $this->stateFlagColumn() => true]);
    }
}
