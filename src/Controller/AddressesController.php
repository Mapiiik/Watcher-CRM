<?php
declare(strict_types=1);

namespace App\Controller;

use App\Addresses\ApiClient;
use App\Model\Entity\Address;
use App\Model\Enum\AddressNumberType;
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
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        // filter
        $conditions = [];
        if (isset($this->customer_id)) {
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
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
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
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $address = $this->Addresses->newEmptyEntity();

        if (isset($this->customer_id)) {
            $customer = $this->Addresses->Customers->get($this->customer_id);

            $address = $this->Addresses->patchEntity($address, $customer->toArray(), ['validate' => false]);
            $address->customer_id = $customer->id;
        }

        if ($this->getRequest()->is('post')) {
            $address = $this->Addresses->patchEntity($address, $this->getRequest()->getData());

            if ($this->getRequest()->getData('refresh') == 'refresh' || $address->hasErrors()) {
                // only refresh
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

        if (isset($this->customer_id)) {
            $customers->where(['Customers.id' => $this->customer_id]);
        }

        $this->set(compact('address', 'customers', 'countries'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Address id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $address = $this->Addresses->get($id);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $address = $this->Addresses->patchEntity($address, $this->getRequest()->getData());

            if ($this->getRequest()->getData('refresh') == 'refresh' || $address->hasErrors()) {
                // only refresh
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

        if (isset($this->customer_id)) {
            $customers->where(['Customers.id' => $this->customer_id]);
        }

        $this->set(compact('address', 'customers', 'countries'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Address id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
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
     * Find National Address Registry Data (CZ RUIAN, HR DGU, etc.)
     *
     * @param \App\Model\Entity\Address $address Address to be find in RUIAN
     * @return array<string, mixed> array (address_registry_reference, address_registry_source, gps_y, gps_x)
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

            return $notFoundResult;
        }

        // use uppercase country code for consistency (e.g. 'CZ', 'HR')
        $countryCode = strtoupper($countryCode);

        // check if the country is supported by the national address registry API
        try {
            $addressesMeta = ApiClient::metaFromCache();
        } catch (RuntimeException $e) {
            $this->Flash->error(__(
                'Could not retrieve national address registry metadata: {0}',
                $e->getMessage(),
            ));

            return $notFoundResult;
        }

        if (!isset($addressesMeta['supported_countries']) || !is_array($addressesMeta['supported_countries'])) {
            $this->Flash->error(__(
                'National address registry lookup is not available. Could not retrieve supported countries list.',
            ));

            return $notFoundResult;
        }
        $supportedCountries = array_map('strtoupper', $addressesMeta['supported_countries']); // → ['CZ', 'HR']

        if (!in_array($countryCode, $supportedCountries, true)) {
            $this->Flash->info(__(
                'National address registry lookup is not supported for country code: {0}.',
                $countryCode,
            ));

            return $notFoundResult;
        }

        // do the lookup
        try {
            $response = ApiClient::lookup([
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

            return $notFoundResult;
        }

        // no match found
        $this->Flash->error(__(
            'Address could not be found in national ({0}) address registry.',
            $countryCode,
        ));

        return $notFoundResult;
    }
}
