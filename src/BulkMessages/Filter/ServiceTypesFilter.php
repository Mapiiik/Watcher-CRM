<?php
declare(strict_types=1);

namespace App\BulkMessages\Filter;

use Cake\Validation\Validation;

/**
 * Filters customers by the service type of one of their contracts.
 *
 * The coarse counterpart of {@see ServicesFilter}: it looks at the contract's
 * own `service_type_id` instead of what is actually billed on it, so it needs no
 * billing period reasoning. Selecting several types widens the reach (OR
 * semantics), it does not require all of them.
 *
 * Being contract-scoped, the selection is correlated with the other
 * contract-scoped filters on a *single* contract, so the filter reads as
 * "an active contract on the selected access point of this service type".
 */
final class ServiceTypesFilter extends AbstractBulkRecipientFilter implements ContractScopedFilterInterface
{
    /**
     * @inheritDoc
     */
    public function id(): string
    {
        return 'service_type_ids';
    }

    /**
     * @inheritDoc
     */
    public function controls(mixed $value): array
    {
        return [
            [
                'name' => $this->id(),
                'options' => [
                    'label' => __('Service types (has any of the selected)'),
                    'options' => $this->serviceTypeOptions(),
                    'multiple' => true,
                    'empty' => false,
                    'val' => $this->serviceTypeIds($value),
                ],
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function buildValue(array $data): mixed
    {
        $ids = $this->serviceTypeIds($data[$this->id()] ?? null);

        return $ids === [] ? null : $ids;
    }

    /**
     * @inheritDoc
     */
    public function containedContractConditions(mixed $value): ?array
    {
        $ids = $this->serviceTypeIds($value);
        if ($ids === []) {
            return null;
        }

        // narrows the preview's contained contracts to the selected service
        // types, so a matched customer's contracts of other types do not surface
        // access-point groups this filter excludes
        return ['Contracts.service_type_id IN' => $ids];
    }

    /**
     * @inheritDoc
     */
    public function describe(mixed $value): ?string
    {
        return $this->describeSelection(
            __('Service types'),
            $this->serviceTypeOptions(),
            $this->serviceTypeIds($value),
        );
    }

    /**
     * Available service types as a value => name list.
     *
     * @return array<array-key, string>
     */
    private function serviceTypeOptions(): array
    {
        return $this->customerMessages->Customers->Contracts->ServiceTypes
            ->find('list', order: ['name'])
            ->toArray();
    }

    /**
     * Normalise a submitted/stored value to a list of valid service type uuids.
     *
     * @param mixed $value Submitted or stored filter value.
     * @return list<string>
     */
    private function serviceTypeIds(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            $value,
            static fn(mixed $id): bool => is_string($id) && Validation::uuid($id),
        )));
    }
}
