<?php
declare(strict_types=1);

namespace App\Bulk\Filter;

use App\Addresses\Resolver as AddressesResolver;
use RuntimeException;

/**
 * Filters customers by the national address registry reference of a contract's
 * installation address.
 *
 * The submitted value is expected in the "source|reference" format
 * (e.g. "cz|12345678"), matching the option keys built from the registry.
 */
final class RegistryAddressFilter extends AbstractBulkRecipientFilter
{
    /**
     * @inheritDoc
     */
    public function id(): string
    {
        return 'registry_address_id';
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
                    'label' => __('Installation Address (registry)'),
                    'options' => $this->addressOptions(),
                    'empty' => true,
                    'val' => is_string($value) ? $value : null,
                ],
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function buildValue(array $data): mixed
    {
        $value = $data[$this->id()] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @inheritDoc
     */
    public function conditions(mixed $value): ?array
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        // expect format "source|reference", e.g. "cz|12345678"
        [
            $address_registry_source,
            $address_registry_reference,
        ] = explode('|', $value, limit: 2) + [null, null];

        $filterQuery = $this->customerMessages->Customers->Contracts
            ->find()
            ->select(['customer_id'])
            ->contain(['InstallationAddresses'])
            ->distinct()
            ->where([
                'InstallationAddresses.address_registry_reference IS' => $address_registry_reference,
                'InstallationAddresses.address_registry_source IS' => $address_registry_source,
            ]);

        return ['Customers.id IN' => $filterQuery];
    }

    /**
     * Registry installation addresses as a value => label list.
     *
     * @return array<array-key, string>
     */
    private function addressOptions(): array
    {
        /** @var \Cake\Datasource\ResultSetInterface<int, \App\Model\Entity\Address> $installationAddresses */
        $installationAddresses = $this->customerMessages->Customers->Contracts->InstallationAddresses
            ->find()
            ->where([
                'address_registry_source IS NOT' => null,
                'address_registry_reference IS NOT' => null,
            ])
            ->all();

        try {
            return AddressesResolver::dropdownMap($installationAddresses);
        } catch (RuntimeException $e) {
            $this->warning = __(
                'Could not retrieve addresses from national address registry: {0}',
                $e->getMessage(),
            );

            return [];
        }
    }
}
