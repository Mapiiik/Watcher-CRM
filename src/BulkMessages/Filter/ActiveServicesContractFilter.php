<?php
declare(strict_types=1);

namespace App\BulkMessages\Filter;

/**
 * Restricts recipients to customers with at least one contract in a state that
 * provides active services (ContractStates.active_services). Checked by default.
 */
final class ActiveServicesContractFilter extends AbstractContractStateFlagFilter
{
    /**
     * @inheritDoc
     */
    public function id(): string
    {
        return 'active_services_contract';
    }

    /**
     * @inheritDoc
     */
    protected function stateFlagColumn(): string
    {
        return 'active_services';
    }

    /**
     * @inheritDoc
     */
    protected function controlLabel(): string
    {
        return __('Only customers with an active contract (provides active services)');
    }
}
