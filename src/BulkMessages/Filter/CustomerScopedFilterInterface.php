<?php
declare(strict_types=1);

namespace App\BulkMessages\Filter;

/**
 * A recipient filter whose condition is about the customer itself (e.g. an
 * assigned label), applied directly to the Customers query.
 *
 * Contrast with {@see ContractScopedFilterInterface}, whose condition is about
 * the customer's contracts and is correlated on a single contract.
 */
interface CustomerScopedFilterInterface extends BulkRecipientFilterInterface
{
    /**
     * Conditions to merge into the Customers query for the given stored value,
     * or null when the value does not activate the filter.
     *
     * @param mixed $value Stored filter value.
     * @return array<mixed>|null
     */
    public function conditions(mixed $value): ?array;
}
