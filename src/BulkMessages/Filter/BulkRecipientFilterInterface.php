<?php
declare(strict_types=1);

namespace App\BulkMessages\Filter;

/**
 * A single recipient filter for the bulk customer message wizard.
 *
 * A filter owns a logical id (its slot in the wizard state and registry key),
 * renders one or more form controls, and folds the posted form fields into a
 * single value. Owning several form fields while exposing a single value keeps
 * composite filters (e.g. an access point selection plus a "cascade" toggle) as
 * one logical unit without nesting the form field names.
 *
 * How a filter narrows the query is delegated to one of the two scoping
 * sub-interfaces it must also implement: {@see CustomerScopedFilterInterface}
 * (a condition on the customer) or {@see ContractScopedFilterInterface} (a
 * condition on the customer's contracts, correlated on a single contract).
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
     * The value this filter starts out with, seeded into the wizard state when a
     * purpose is picked, or null when it starts inactive. A filter that restricts
     * by default (rather than merely offering to) must return that restriction
     * here, so it also applies to a wizard that never submitted the filter step.
     *
     * @return mixed
     */
    public function defaultValue(): mixed;

    /**
     * A user-facing warning about this filter's data source (e.g. an external
     * service is unavailable), or null when everything is fine. Populated as a
     * side effect of building the controls.
     *
     * @return string|null
     */
    public function warning(): ?string;

    /**
     * One plain-text line stating what this filter narrowed the recipients to,
     * or null when it was inactive. Goes into the post-send report, so a send can
     * be explained after the fact — keep it readable, not a dump of ids.
     *
     * @param mixed $value Stored filter value.
     * @return string|null
     */
    public function describe(mixed $value): ?string;
}
