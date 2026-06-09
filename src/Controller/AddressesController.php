<?php
declare(strict_types=1);

namespace App\Controller;

use App\Addresses\ApiClient as AddressesApiClient;
use App\Model\Entity\Address;
use App\Model\Enum\AddressNumberType;
use Cake\Http\Response;
use RuntimeException;

/**
 * Addresses Controller
 *
 * @property \App\Model\Table\AddressesTable $Addresses
 */
class AddressesController extends AppController
{
    /**
     * Index method
     *
     * @return void Renders view
     */
    public function index(): void
    {
        // filter
        $conditions = [];
        if ($this->customer_id !== null) {
            $conditions = ['Addresses.customer_id' => $this->customer_id];
        }

        // search
        $search = $this->getRequest()->getQuery('search');
        if (!empty($search) && is_string($search)) {
            $conditions[] = [
                'OR' => [
                    'Addresses.company ILIKE' => '%' . trim($search) . '%',
                    'Addresses.title ILIKE' => '%' . trim($search) . '%',
                    'Addresses.first_name ILIKE' => '%' . trim($search) . '%',
                    'Addresses.last_name ILIKE' => '%' . trim($search) . '%',
                    'Addresses.suffix ILIKE' => '%' . trim($search) . '%',
                    'Addresses.street ILIKE' => '%' . trim($search) . '%',
                    'Addresses.number ILIKE' => '%' . trim($search) . '%',
                    'Addresses.city ILIKE' => '%' . trim($search) . '%',
                    'Addresses.zip ILIKE' => '%' . trim($search) . '%',
                    'Addresses.address_registry_reference' => trim($search),
                ],
            ];
        }

        $this->paginate = [
            'order' => [
                'id' => 'DESC',
            ],
        ];
        $addresses = $this->paginate($this->Addresses->find(
            'all',
            contain: [
                'Countries',
                'Customers',
            ],
            conditions: $conditions,
        ));

        $this->set(compact('addresses'));
    }

    /**
     * View method
     *
     * @param string|null $id Address id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): void
    {
        $address = $this->Addresses->get($id, contain: [
            'Countries',
            'Customers',
            'Creators',
            'Modifiers',
        ]);

        $this->set(compact('address'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(): ?Response
    {
        $address = $this->Addresses->newEmptyEntity();
        $address->country_id = $this->Addresses->getSchema()->getColumn('country_id')['default'] ?? null;

        if ($this->customer_id !== null) {
            $customer = $this->Addresses->Customers->get($this->customer_id);

            $address = $this->Addresses->patchEntity($address, $customer->toArray(), ['validate' => false]);
            $address->customer_id = $customer->id;
        }

        if ($this->getRequest()->is('post')) {
            $address = $this->Addresses->patchEntity($address, $this->getRequest()->getData());

            if ($this->getRequest()->getData('refresh') == 'refresh' || $address->hasErrors()) {
                // only refresh

                // perform a lookup to pre-fill the address fields based on the selected address registry entry
                $addressRegistryKey = $this->getRequest()->getData('address_registry_search');
                if (!empty($addressRegistryKey) && is_string($addressRegistryKey)) {
                    try {
                        $address = $this->Addresses->patchEntity(
                            $address,
                            $this->loadPatchDataFromAddressesRegistry($addressRegistryKey),
                            ['validate' => false],
                        );
                    } catch (RuntimeException $e) {
                        $this->Flash->error(__(
                            'Could not retrieve address from national address registry: {0}',
                            $e->getMessage(),
                        ));
                    }
                }
            } else {
                // update national address registry data
                $address->patch($this->findNationalAddressRegistryData($address));

                // set manual coordinate if defined
                if ($address->manual_coordinate_setting) {
                    $address->gps_y = $this->getRequest()->getData('gps_y');
                    $address->gps_x = $this->getRequest()->getData('gps_x');
                }

                if ($this->Addresses->save($address)) {
                    $this->Flash->success(__('The address has been saved.'));

                    return $this->afterAddRedirect(['action' => 'view', $address->id]);
                }
                $this->Flash->error(__('The address could not be saved. Please, try again.'));
            }
        }
        $customers = $this->Addresses->Customers->find('list', order: [
            'company',
            'last_name',
            'first_name',
        ]);
        $countries = $this->Addresses->Countries->find('list', order: [
            'name',
        ]);

        // set country code for address search widget (Select2)
        $searchCountryCode = $this->getSearchCountryCodeForAddress($address);

        if ($this->customer_id !== null) {
            $customers->where(['Customers.id' => $this->customer_id]);
        }

        $this->set(compact('address', 'customers', 'countries', 'searchCountryCode'));

        return null;
    }

    /**
     * Edit method
     *
     * @param string|null $id Address id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        $address = $this->Addresses->get($id);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $address = $this->Addresses->patchEntity($address, $this->getRequest()->getData());

            if ($this->getRequest()->getData('refresh') == 'refresh' || $address->hasErrors()) {
                // only refresh

                // perform a lookup to pre-fill the address fields based on the selected address registry entry
                $addressRegistryKey = $this->getRequest()->getData('address_registry_search');
                if (!empty($addressRegistryKey) && is_string($addressRegistryKey)) {
                    try {
                        $address = $this->Addresses->patchEntity(
                            $address,
                            $this->loadPatchDataFromAddressesRegistry($addressRegistryKey),
                            ['validate' => false],
                        );
                    } catch (RuntimeException $e) {
                        $this->Flash->error(__(
                            'Could not retrieve address from national address registry: {0}',
                            $e->getMessage(),
                        ));
                    }
                }
            } else {
                // update national address registry data
                $address->patch($this->findNationalAddressRegistryData($address));

                // set manual coordinate if defined
                if ($address->manual_coordinate_setting) {
                    $address->gps_y = $this->getRequest()->getData('gps_y');
                    $address->gps_x = $this->getRequest()->getData('gps_x');
                }

                if ($this->Addresses->save($address)) {
                    $this->Flash->success(__('The address has been saved.'));

                    return $this->afterEditRedirect(['action' => 'view', $address->id]);
                }
                $this->Flash->error(__('The address could not be saved. Please, try again.'));
            }
        }
        $customers = $this->Addresses->Customers->find('list', order: [
            'company',
            'last_name',
            'first_name',
        ]);
        $countries = $this->Addresses->Countries->find('list', order: [
            'name',
        ]);

        // set country code for address search widget (Select2)
        $searchCountryCode = $this->getSearchCountryCodeForAddress($address);

        if ($this->customer_id !== null) {
            $customers->where(['Customers.id' => $this->customer_id]);
        }

        $this->set(compact('address', 'customers', 'countries', 'searchCountryCode'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Address id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $address = $this->Addresses->get($id);
        if ($this->Addresses->delete($address)) {
            $this->Flash->success(__('The address has been deleted.'));
        } else {
            $this->flashValidationErrors($address->getErrors());
            $this->Flash->error(__('The address could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }

    /**
     * Returns the country code for the address search widget, if the address's country is supported by the national address registry API.
     *
     * @param \App\Model\Entity\Address $address The address for which to get the search country code.
     * @return string|null The search country code or null if not applicable (lowercase).
     */
    private function getSearchCountryCodeForAddress(Address $address): ?string
    {
        if ($address->country_id === null) {
            return null;
        }

        $countryCode = $this->Addresses->Countries->get($address->country_id)->code;
        if ($countryCode === null) {
            return null;
        }

        // check if the country is supported by the national address registry API
        $supportedCountries = $this->loadSupportedCountriesForAddressRegistry();
        if (in_array(strtoupper($countryCode), $supportedCountries, true)) {
            return strtolower($countryCode); // use lowercase country code for the search widget as expected by the API
        }

        return null;
    }

    /**
     * Loads the list of supported countries for the national address registry from the API metadata.
     *
     * @return array List of supported country codes (e.g., ['CZ', 'HR']).
     *      Returns an empty array if the metadata could not be retrieved or is invalid.
     */
    private function loadSupportedCountriesForAddressRegistry(): array
    {
        try {
            $addressesMeta = AddressesApiClient::metaFromCache();
        } catch (RuntimeException $e) {
            $this->Flash->error(__(
                'Could not retrieve national address registry metadata: {0}',
                $e->getMessage(),
            ));

            return [];
        }

        if (!isset($addressesMeta['supported_countries']) || !is_array($addressesMeta['supported_countries'])) {
            $this->Flash->error(__(
                'National address registry lookup is not available.'
                . ' Could not retrieve supported countries list.',
            ));

            return [];
        }

        return array_map(strtoupper(...), $addressesMeta['supported_countries']);
    }

    /**
     * Loads patch data from the national address registry based on the provided ID.
     *
     * @param string $addressRegistryKey Ecpected format: "source|reference" (e.g., "cz|12345678").
     * @return array The patch data for the address.
     * @throws \RuntimeException If the address is not found in the national address registry.
     */
    private function loadPatchDataFromAddressesRegistry(string $addressRegistryKey): array
    {
        // expect format "source|reference", e.g. "cz|12345678"
        [
            $addressRegistrySource,
            $addressRegistryReference,
        ] = explode('|', $addressRegistryKey, limit: 2) + [null, null];

        if (
            in_array($addressRegistrySource, [null, '', '0'], true)
            || in_array($addressRegistryReference, [null, '', '0'], true)
        ) {
            throw new RuntimeException('Invalid address registry key format: ' . $addressRegistryKey);
        }

        $addressRegistryData = AddressesApiClient::byIdFromCache(
            source: $addressRegistrySource,
            registryId: $addressRegistryReference,
        );

        if ($addressRegistryData == null) {
            throw new RuntimeException('Empty response from address registry API for ID: ' . $addressRegistryKey);
        }

        return [
            'street' => $addressRegistryData['street'] ?? null,
            'number' => $addressRegistryData['house_number'] ?? null,
            'number_type' => $addressRegistryData['number_type'] === 'registration'
                ? AddressNumberType::Registration->value
                : AddressNumberType::House->value,
            'city' => $addressRegistryData['city'] ?? null,
            'zip' => $addressRegistryData['postal_code'] ?? null,
        ];
    }

    /**
     * Find National Address Registry Data (CZ RUIAN, HR DGU, etc.)
     *
     * Return contract:
     *  - non-empty array → patch onto entity (may contain nulls when the
     *    registry authoritatively says "not in registry")
     *  - empty array     → transient failure, do not touch entity
     *
     * @param \App\Model\Entity\Address $address Address to be find in national address registry
     * @return array<string, mixed> array (address_registry_reference, address_registry_source, gps_y, gps_x)
     *     or [] when the lookup couldn't run (transient error, no country code).
     */
    private function findNationalAddressRegistryData(Address $address): array
    {
        $notFoundResult = [
            'address_registry_reference' => null,
            'address_registry_source' => null,
            'gps_y' => null,
            'gps_x' => null,
        ];

        // determine country code
        if ($address->country === null) {
            $countryCode = $this->Addresses->Countries->get($address->country_id)->code;
        } else {
            $countryCode = $address->country->code;
        }

        // if country code is not defined, we cannot do the lookup
        if ($countryCode === null) {
            $this->Flash->warning(__('Country code is not defined for the address country.'));

            return [];
        }

        // use uppercase country code for consistency (e.g. 'CZ', 'HR')
        $countryCode = strtoupper($countryCode);

        // check if the country is supported by the national address registry API
        $supportedCountries = $this->loadSupportedCountriesForAddressRegistry();

        if (!in_array($countryCode, $supportedCountries, true)) {
            $this->Flash->info(__(
                'National address registry lookup is not supported for country code: {0}.',
                $countryCode,
            ));

            return $notFoundResult;
        }

        // do the lookup
        try {
            $response = AddressesApiClient::lookup([
                'country' => strtolower($countryCode),
                'street' => $address->street,
                'number' => $address->number,
                'number_type' => $address->number_type === AddressNumberType::Registration
                    ? 'registration' : 'house',
                'city' => $address->city,
                'postal_code' => $address->zip,
            ]);

            if ($response['ambiguous']) {
                $this->Flash->info(__(
                    'Multiple ({0}) addresses found in national ({1}) address registry.',
                    count($response['matches']),
                    $countryCode,
                ));
            }

            if (count($response['matches']) === 1) {
                $match = $response['matches'][0];
                $this->Flash->info(__(
                    'Address found in national ({0}) address registry.',
                    $countryCode,
                ));

                return [
                    'address_registry_reference' => $match['registry_ref'],
                    'address_registry_source' => $match['source'],
                    'gps_y' => $match['geometry']['coordinates'][1], // lat
                    'gps_x' => $match['geometry']['coordinates'][0], // lon
                ];
            }
        } catch (RuntimeException $e) {
            $this->Flash->error(__(
                'Error during national ({0}) address registry lookup: {1}',
                $countryCode,
                $e->getMessage(),
            ));

            return [];
        }

        // no match found
        $this->Flash->error(__(
            'Address could not be found in national ({0}) address registry.',
            $countryCode,
        ));

        return $notFoundResult;
    }

    /**
     * Bulk-update national address registry data for every address.
     *
     * Always patches `address_registry_reference` / `address_registry_source`
     * (may be set to null when the registry authoritatively reports no match
     * or the country isn't covered by the registry). Updates GPS too — except
     * when the address has `manual_coordinate_setting`, in which case the
     * existing coordinates are preserved.
     *
     * Implementation: one cached metaFromCache + chunked POSTs to
     * `lookupBatch` (50 items per chunk) instead of N individual lookups.
     * Transient API failures (network / HTTP errors) are non-destructive: the
     * affected chunk is skipped and its addresses keep their existing data.
     *
     * Scoped to the current customer when `$this->customer_id` is set,
     * otherwise iterates over every address.
     *
     * @return \Cake\Http\Response|null Redirects back to the index on completion.
     */
    public function updateAllFromNationalAddressRegistries(): ?Response
    {
        $this->getRequest()->allowMethod(['post']);
        set_time_limit(0); // batched external API calls may take a while

        /** @var \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Address> $query */
        $query = $this->Addresses->find()->contain('Countries');
        if ($this->customer_id !== null) {
            $query->where(['Addresses.customer_id' => $this->customer_id]);
        }

        // Fetch supported-countries metadata once; bail out on transient failure.
        $supportedCountries = $this->loadSupportedCountriesForAddressRegistry();
        if ($supportedCountries === []) {
            return $this->redirect(['action' => 'index']);
        }

        $updated = 0;
        $unchanged = 0;
        $skipped = 0;
        $failed = 0;

        // Apply a result `$data` to `$address`, honoring manual_coordinate_setting,
        // and update the counter buckets above accordingly.
        $applyResult = function (
            Address $address,
            array $data,
        ) use (
            &$updated,
            &$unchanged,
            &$failed,
        ): void {
            if ($address->manual_coordinate_setting) {
                unset($data['gps_y'], $data['gps_x']);
            }
            $address->patch($data);

            if (!$address->isDirty()) {
                $unchanged++;

                return;
            }
            if ($this->Addresses->save($address)) {
                $updated++;
            } else {
                $failed++;
            }
        };

        $clearedResult = [
            'address_registry_reference' => null,
            'address_registry_source' => null,
            'gps_y' => null,
            'gps_x' => null,
        ];

        // Phase 1 — partition addresses into:
        //   * skipped (no country / unresolvable code)
        //   * cleared in place (country present but unsupported by registry)
        //   * queued for batch lookup (country supported)
        /** @var list<\App\Model\Entity\Address> $lookupQueue */
        $lookupQueue = [];
        /** @var list<array<string, mixed>> $batchItems */
        $batchItems = [];

        foreach ($query->all() as $address) {
            $countryCode = $address->country->code;
            if ($countryCode === null) {
                // can't look up without a country
                $skipped++;
                continue;
            }
            $countryCode = strtoupper($countryCode);

            if (!in_array($countryCode, $supportedCountries, true)) {
                // country not covered by the registry → clear stale refs
                $applyResult($address, $clearedResult);
                continue;
            }

            $lookupQueue[] = $address;
            $batchItems[] = [
                'country' => strtolower($countryCode),
                'street' => $address->street,
                'number' => $address->number,
                'number_type' => $address->number_type === AddressNumberType::Registration
                    ? 'registration' : 'house',
                'city' => $address->city,
                'postal_code' => $address->zip,
            ];
        }

        // Phase 2 — batch lookup in chunks; map results back parallel-position.
        $chunkSize = 50;
        $offset = 0;
        foreach (array_chunk($batchItems, $chunkSize) as $chunk) {
            try {
                $response = AddressesApiClient::lookupBatch($chunk);
            } catch (RuntimeException) {
                // transient failure for this chunk → keep existing data
                $skipped += count($chunk);
                $offset += count($chunk);
                continue;
            }

            $results = $response['results'] ?? null;
            if (!is_array($results)) {
                $skipped += count($chunk);
                $offset += count($chunk);
                continue;
            }

            foreach ($results as $i => $result) {
                $address = $lookupQueue[$offset + $i] ?? null;
                if ($address === null) {
                    continue;
                }

                $matches = $result['matches'] ?? [];
                if (count($matches) === 1) {
                    $match = $matches[0];
                    $applyResult($address, [
                        'address_registry_reference' => $match['registry_ref'],
                        'address_registry_source' => $match['source'],
                        'gps_y' => $match['geometry']['coordinates'][1], // lat
                        'gps_x' => $match['geometry']['coordinates'][0], // lon
                    ]);
                } else {
                    // 0 matches or ambiguous → registry has nothing definitive, clear refs
                    $applyResult($address, $clearedResult);
                }
            }

            $offset += count($chunk);
        }

        $this->Flash->success(__(
            'Addresses updated from national address registries: {0} updated, {1} unchanged, {2} skipped, {3} failed.',
            $updated,
            $unchanged,
            $skipped,
            $failed,
        ));

        return $this->redirect(['action' => 'index']);
    }
}
