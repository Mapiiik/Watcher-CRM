<?php
declare(strict_types=1);

namespace App\BulkMessages\Filter;

/**
 * Restricts recipients to customers with at least one contract in a billed
 * state (ContractStates.billed) — the billing-message scope. Checked by default.
 */
final class BilledContractFilter extends AbstractContractStateFlagFilter
{
    /**
     * @inheritDoc
     */
    public function id(): string
    {
        return 'billed_contract';
    }

    /**
     * @inheritDoc
     */
    protected function stateFlagColumn(): string
    {
        return 'billed';
    }

    /**
     * @inheritDoc
     */
    protected function controlLabel(): string
    {
        return __('Only customers with a billed contract');
    }
}
