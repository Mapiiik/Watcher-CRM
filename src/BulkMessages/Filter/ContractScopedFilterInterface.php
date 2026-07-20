<?php
declare(strict_types=1);

namespace App\BulkMessages\Filter;

/**
 * A recipient filter that also narrows the *contained* contracts used to build
 * the access-point preview grouping, not just the set of matched customers.
 *
 * Implemented by filters whose condition is about a customer's contracts (e.g. a
 * contract-state flag): the preview must hide the contracts that do not qualify
 * so they never surface a customer under an access point the filter excludes,
 * which would be confusing to the operator.
 */
interface ContractScopedFilterInterface extends BulkRecipientFilterInterface
{
    /**
     * Conditions to merge into the contained Contracts query for the given
     * stored value, or null when the value should not narrow the contained
     * contracts.
     *
     * @param mixed $value Stored filter value.
     * @return array<mixed>|null
     */
    public function containedContractConditions(mixed $value): ?array;
}
