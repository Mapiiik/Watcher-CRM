<?php
declare(strict_types=1);

namespace App\Bulk\Filter;

/**
 * A single recipient filter for the bulk customer message wizard.
 *
 * A filter owns a logical id (its slot in the wizard state and registry key),
 * renders one or more form controls, folds the posted form fields into a single
 * value, and turns that value into query conditions. Owning several form fields
 * while exposing a single value keeps composite filters (e.g. an access point
 * selection plus a "cascade" toggle) as one logical unit without nesting the
 * form field names.
 */
interface BulkRecipientFilterInterface
{
    /**
     * Logical filter id — the wizard-state slot and the registry key.
     *
     * @return string
     */
    public function id(): string;

    /**
     * Form controls this filter renders, in order. Each descriptor is
     * `['name' => <field name>, 'options' => <FormHelper::control() options>]`.
     *
     * @param mixed $value Current stored filter value (or null when unset).
     * @return list<array{name: string, options: array<string, mixed>}>
     */
    public function controls(mixed $value): array;

    /**
     * Fold the posted request data into this filter's stored value, or null
     * when the filter is inactive (nothing selected).
     *
     * @param array<string, mixed> $data Posted request data.
     * @return mixed
     */
    public function buildValue(array $data): mixed;

    /**
     * Conditions to merge into the Customers query for the given stored value,
     * or null when the value does not activate the filter.
     *
     * @param mixed $value Stored filter value.
     * @return array<mixed>|null
     */
    public function conditions(mixed $value): ?array;

    /**
     * A user-facing warning about this filter's data source (e.g. an external
     * service is unavailable), or null when everything is fine. Populated as a
     * side effect of building the controls.
     *
     * @return string|null
     */
    public function warning(): ?string;
}
