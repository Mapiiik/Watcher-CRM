<?php
declare(strict_types=1);

namespace App\Bulk;

use App\Bulk\Filter\AccessPointFilter;
use App\Bulk\Filter\BulkRecipientFilterInterface;
use App\Bulk\Filter\LabelsFilter;
use App\Bulk\Filter\NotLabelsFilter;
use App\Bulk\Filter\RegistryAddressFilter;
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
     * @var array<string, callable(): \App\Bulk\Filter\BulkRecipientFilterInterface>
     */
    private array $factories;

    /**
     * @param \App\Model\Table\CustomerMessagesTable $customerMessages Root table passed to each filter.
     */
    public function __construct(CustomerMessagesTable $customerMessages)
    {
        $this->factories = [
            'label_ids' => fn(): BulkRecipientFilterInterface => new LabelsFilter($customerMessages),
            'not_label_ids' => fn(): BulkRecipientFilterInterface => new NotLabelsFilter($customerMessages),
            'access_point' => fn(): BulkRecipientFilterInterface => new AccessPointFilter($customerMessages),
            'registry_address_id' => fn(): BulkRecipientFilterInterface => new RegistryAddressFilter($customerMessages),
        ];
    }

    /**
     * Return the ordered filters offered for the given purpose.
     *
     * @param \App\Model\Enum\CustomerMessagePurpose $purpose Selected purpose.
     * @return array<string, \App\Bulk\Filter\BulkRecipientFilterInterface>
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
     * Return a single filter instance by key, or null when unknown.
     *
     * @param string $key Filter key.
     * @return \App\Bulk\Filter\BulkRecipientFilterInterface|null
     */
    public function get(string $key): ?BulkRecipientFilterInterface
    {
        if (!isset($this->factories[$key])) {
            return null;
        }

        return ($this->factories[$key])();
    }
}
