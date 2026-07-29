<?php
declare(strict_types=1);

namespace App\BulkMessages;

use App\BulkMessages\Filter\AccessPointFilter;
use App\BulkMessages\Filter\ActiveServicesContractFilter;
use App\BulkMessages\Filter\BilledContractFilter;
use App\BulkMessages\Filter\BulkRecipientFilterInterface;
use App\BulkMessages\Filter\LabelsFilter;
use App\BulkMessages\Filter\NotLabelsFilter;
use App\BulkMessages\Filter\RegistryAddressFilter;
use App\BulkMessages\Filter\ServicesFilter;
use App\BulkMessages\Filter\ServiceTypesFilter;
use App\Model\Enum\CustomerMessagePurpose;
use App\Model\Table\CustomerMessagesTable;

/**
 * Registry of recipient filters available to the bulk message wizard.
 *
 * This is the single extension point for filters: register a new filter here
 * (key => factory) and reference its key from
 * {@see \App\Model\Enum\CustomerMessagePurpose::filterKeys()} to make it appear
 * for the relevant purpose(s).
 */
final class BulkRecipientFilterRegistry
{
    /**
     * @var array<string, callable(): \App\BulkMessages\Filter\BulkRecipientFilterInterface>
     */
    private array $factories;

    /**
     * @param \App\Model\Table\CustomerMessagesTable $customerMessages Root table passed to each filter.
     */
    public function __construct(CustomerMessagesTable $customerMessages)
    {
        $this->factories = [
            'active_services_contract' =>
                fn(): BulkRecipientFilterInterface => new ActiveServicesContractFilter($customerMessages),
            'billed_contract' => fn(): BulkRecipientFilterInterface => new BilledContractFilter($customerMessages),
            'label_ids' => fn(): BulkRecipientFilterInterface => new LabelsFilter($customerMessages),
            'not_label_ids' => fn(): BulkRecipientFilterInterface => new NotLabelsFilter($customerMessages),
            'access_point' => fn(): BulkRecipientFilterInterface => new AccessPointFilter($customerMessages),
            'registry_address_id' => fn(): BulkRecipientFilterInterface => new RegistryAddressFilter($customerMessages),
            'service_type_ids' => fn(): BulkRecipientFilterInterface => new ServiceTypesFilter($customerMessages),
            'service_ids' => fn(): BulkRecipientFilterInterface => new ServicesFilter($customerMessages),
        ];
    }

    /**
     * Return the ordered filters offered for the given purpose.
     *
     * @param \App\Model\Enum\CustomerMessagePurpose $purpose Selected purpose.
     * @return array<string, \App\BulkMessages\Filter\BulkRecipientFilterInterface>
     */
    public function forPurpose(CustomerMessagePurpose $purpose): array
    {
        $filters = [];
        foreach ($purpose->filterKeys() as $key) {
            $filter = $this->get($key);
            if ($filter !== null) {
                $filters[$key] = $filter;
            }
        }

        return $filters;
    }

    /**
     * The filter values a freshly picked purpose starts with, keyed by filter id.
     *
     * Seeded into the wizard state so the defaults hold even when the operator
     * never submits the filter step (which would otherwise leave the state
     * unfiltered and silently drop restrictions like the active-contract one).
     *
     * @param \App\Model\Enum\CustomerMessagePurpose $purpose Selected purpose.
     * @return array<string, mixed>
     */
    public function defaultsForPurpose(CustomerMessagePurpose $purpose): array
    {
        $defaults = [];
        foreach ($this->forPurpose($purpose) as $key => $filter) {
            $value = $filter->defaultValue();
            if ($value !== null) {
                $defaults[$key] = $value;
            }
        }

        return $defaults;
    }

    /**
     * Return a single filter instance by key, or null when unknown.
     *
     * @param string $key Filter key.
     * @return \App\BulkMessages\Filter\BulkRecipientFilterInterface|null
     */
    public function get(string $key): ?BulkRecipientFilterInterface
    {
        if (!isset($this->factories[$key])) {
            return null;
        }

        return ($this->factories[$key])();
    }
}
