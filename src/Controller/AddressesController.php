<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Address;

/**
 * Addresses Controller
 *
 * @property \App\Model\Table\AddressesTable $Addresses
 * @method \App\Model\Entity\Address[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
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
        $customer_id = $this->getRequest()->getParam('customer_id');
        $this->set('customer_id', $customer_id);

        // filter
        $conditions = [];
        if (isset($customer_id)) {
            $conditions = ['Addresses.customer_id' => $customer_id];
        }

        // search
        $search = $this->request->getQuery('search');
        if (!empty($search)) {
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
            conditions: $conditions
        ));

        $types = $this->Addresses->types;

        $this->set(compact('addresses', 'types'));
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

        $types = $this->Addresses->types;
        $number_types = $this->Addresses->number_types;

        $this->set(compact('address', 'types', 'number_types'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $customer_id = $this->getRequest()->getParam('customer_id');
        $this->set('customer_id', $customer_id);

        $address = $this->Addresses->newEmptyEntity();

        if (isset($customer_id)) {
            $customer = $this->Addresses->Customers->get($customer_id);

            $address = $this->Addresses->patchEntity($address, $customer->toArray(), ['validate' => false]);
            $address->customer_id = $customer_id;
        }

        if ($this->getRequest()->is('post')) {
            $address = $this->Addresses->patchEntity($address, $this->getRequest()->getData());

            if ($this->getRequest()->getData('refresh') == 'refresh' || $address->hasErrors()) {
                // only refresh
            } else {
                // update RUIAN data
                $address->set($this->findRuianData($address));

                // set manual coordinate if defined
                if ($address->manual_coordinate_setting) {
                    $address->gps_y = $this->getRequest()->getData('gps_y');
                    $address->gps_x = $this->getRequest()->getData('gps_x');
                }

                if ($this->Addresses->save($address)) {
                    $this->Flash->success(__('The address has been saved.'));

                    return $this->redirect(['action' => 'index']);
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

        if (isset($customer_id)) {
            $customers->where(['id' => $customer_id]);
        }

        $types = $this->Addresses->types;
        $number_types = $this->Addresses->number_types;

        $this->set(compact('address', 'customers', 'countries', 'types', 'number_types'));
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
        $customer_id = $this->getRequest()->getParam('customer_id');
        $this->set('customer_id', $customer_id);

        $address = $this->Addresses->get($id);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $address = $this->Addresses->patchEntity($address, $this->getRequest()->getData());

            if ($this->getRequest()->getData('refresh') == 'refresh' || $address->hasErrors()) {
                // only refresh
            } else {
                // update RUIAN data
                $address->set($this->findRuianData($address));

                // set manual coordinate if defined
                if ($address->manual_coordinate_setting) {
                    $address->gps_y = $this->getRequest()->getData('gps_y');
                    $address->gps_x = $this->getRequest()->getData('gps_x');
                }

                if ($this->Addresses->save($address)) {
                    $this->Flash->success(__('The address has been saved.'));

                    return $this->redirect(['action' => 'index']);
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

        if (isset($customer_id)) {
            $customers->where(['id' => $customer_id]);
        }

        $types = $this->Addresses->types;
        $number_types = $this->Addresses->number_types;

        $this->set(compact('address', 'customers', 'countries', 'types', 'number_types'));
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
        $customer_id = $this->getRequest()->getParam('customer_id');

        $this->getRequest()->allowMethod(['post', 'delete']);
        $address = $this->Addresses->get($id);
        if ($this->Addresses->delete($address)) {
            $this->Flash->success(__('The address has been deleted.'));
        } else {
            $this->Flash->error(__('The address could not be deleted. Please, try again.'));
        }

        if (isset($customer_id)) {
            return $this->redirect(['controller' => 'Customers', 'action' => 'view', $customer_id]);
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Find RUIAN data for address
     *
     * @param \App\Model\Entity\Address $address Address to be find in RUIAN
     * @return array
     */
    private function findRuianData(Address $address): array
    {
        $typ_so = $address->number_type == 1 ? 'č.ev.' : 'č.p.';
        $cislo = explode('/', $address->number ?? '');
        $cislo_domovni = isset($cislo[0]) ? (int)$cislo[0] : null;
        $cislo_orientacni = isset($cislo[1]) ? (int)$cislo[1] : null;
        unset($cislo);

        $conditionsForSearches = [
            // search in RUIAN
            [
                'ulice_nazev IS' => $address->street,
                'typ_so' => $typ_so,
                'cislo_domovni IS' => $cislo_domovni,
                'cislo_orientacni IS' => $cislo_orientacni,
                'obec_nazev IS' => $address->city,
                'psc IS' => $address->zip,
            ],
            // search in RUIAN with city as MOP
            [
                'ulice_nazev IS' => $address->street,
                'typ_so' => $typ_so,
                'cislo_domovni IS' => $cislo_domovni,
                'cislo_orientacni IS' => $cislo_orientacni,
                'mop_nazev IS' => $address->city,
                'psc IS' => $address->zip,
            ],
            // search in RUIAN with city as MOMC
            [
                'ulice_nazev IS' => $address->street,
                'typ_so' => $typ_so,
                'cislo_domovni IS' => $cislo_domovni,
                'cislo_orientacni IS' => $cislo_orientacni,
                'momc_nazev IS' => $address->city,
                'psc IS' => $address->zip,
            ],
            // search in RUIAN with city as city part
            [
                'ulice_nazev IS' => $address->street,
                'typ_so' => $typ_so,
                'cislo_domovni IS' => $cislo_domovni,
                'cislo_orientacni IS' => $cislo_orientacni,
                'cast_obce_nazev IS' => $address->city,
                'psc IS' => $address->zip,
            ],
            // search in RUIAN with street as city part
            [
                'ulice_nazev' => '',
                'cast_obce_nazev IS' => $address->street,
                'typ_so' => $typ_so,
                'cislo_domovni IS' => $cislo_domovni,
                'cislo_orientacni IS' => $cislo_orientacni,
                'obec_nazev IS' => $address->city,
                'psc IS' => $address->zip,
            ],
        ];

        // search for all options
        foreach ($conditionsForSearches as $conditions) {
            $ruianAddresses = $this->fetchTable('Ruian.Addresses')->find('all', conditions: $conditions);

            $ruianAddresses->select([
                'ruian_gid' => 'kod_adm',
                'gps_y' => 'ST_Y(geometry)',
                'gps_x' => 'ST_X(geometry)',
            ]);

            if ($ruianAddresses->count() > 1) {
                $this->Flash->set(__('Multiple ({0}) RUIAN addresses found.', $ruianAddresses->count()));
            }

            if ($ruianAddresses->count() == 1) {
                $this->Flash->set(__('Address found in RUIAN.'));

                return $ruianAddresses->first()->toArray();
            }

            unset($ruianAddresses);
        }

        $this->Flash->error(__('Address could not be found in RUIAN.'));

        return [
            'ruian_gid' => null,
            'gps_y' => null,
            'gps_x' => null,
        ];
    }
}
