<?php
declare(strict_types=1);

namespace App\BulkMessages\Filter;

use App\Model\Entity\Service;
use Cake\Validation\Validation;

/**
 * Filters customers by the services billed on one of their contracts.
 *
 * A contract qualifies when it has a billing for any of the selected services
 * that is not historical — i.e. still open (`billing_until IS NULL`) or ending
 * in the current month or later, the same "active or future" scope the contract
 * and customer views use for their billing lists. Selecting several services
 * widens the reach (OR semantics), it does not require all of them.
 *
 * Being contract-scoped, the selection is correlated with the other
 * contract-scoped filters on a *single* contract, so the filter reads as
 * "an active contract on the selected access point billing this service".
 *
 * For a coarser cut by the contract's own service type see
 * {@see ServiceTypesFilter}.
 */
final class ServicesFilter extends AbstractBulkRecipientFilter implements ContractScopedFilterInterface
{
    /**
     * @inheritDoc
     */
    public function id(): string
    {
        return 'service_ids';
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
                    'label' => __('Services (billed, has any of the selected)'),
                    'options' => $this->serviceOptions(),
                    'multiple' => true,
                    'empty' => false,
                    'val' => $this->serviceIds($value),
                ],
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function buildValue(array $data): mixed
    {
        $ids = $this->serviceIds($data[$this->id()] ?? null);

        return $ids === [] ? null : $ids;
    }

    /**
     * @inheritDoc
     */
    public function containedContractConditions(mixed $value): ?array
    {
        $ids = $this->serviceIds($value);
        if ($ids === []) {
            return null;
        }

        // narrows the preview's contained contracts to those billing a selected
        // service, so a matched customer's other contracts do not surface
        // access-point groups this filter excludes
        $billings = $this->customerMessages->Customers->Contracts->Billings
            // active or future only — historical billings say nothing about
            // which services a customer has today
            ->find('activeOrFuture')
            ->select(['Billings.contract_id'])
            ->distinct()
            ->where(['Billings.service_id IN' => $ids]);

        return ['Contracts.id IN' => $billings];
    }

    /**
     * Available services as a value => name list, grouped by service type so the
     * multiselect stays readable when several types offer similar names.
     *
     * @return array<array-key, array<array-key, string>>
     */
    private function serviceOptions(): array
    {
        return $this->customerMessages->Customers->Contracts->Billings->Services
            ->find(
                'list',
                valueField: 'name',
                groupField: static fn(Service $service): string => $service->service_type->name
                    ?? __('Without a service type'),
                order: [
                    'ServiceTypes.name',
                    'Services.name',
                ],
            )
            ->contain(['ServiceTypes'])
            ->toArray();
    }

    /**
     * Normalise a submitted/stored value to a list of valid service uuids.
     *
     * @param mixed $value Submitted or stored filter value.
     * @return list<string>
     */
    private function serviceIds(mixed $value): array
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
