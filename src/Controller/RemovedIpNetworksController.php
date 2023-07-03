<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\I18n\DateTime;

/**
 * RemovedIpNetworks Controller
 *
 * @property \App\Model\Table\RemovedIpNetworksTable $RemovedIpNetworks
 * @method \App\Model\Entity\RemovedIpNetwork[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class RemovedIpNetworksController extends AppController
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

        $contract_id = $this->getRequest()->getParam('contract_id');
        $this->set('contract_id', $contract_id);

        // filter
        $conditions = [];
        if (isset($customer_id)) {
            $conditions += ['RemovedIpNetworks.customer_id' => $customer_id];
        }
        if (isset($contract_id)) {
            $conditions += ['RemovedIpNetworks.contract_id' => $contract_id];
        }

        // search
        $search = $this->request->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'RemovedIpNetworks.ip_network::character varying ILIKE' => '%' . trim($search) . '%',
                    'Contracts.number ILIKE' => '%' . trim($search) . '%',
                ],
            ];
        }

        $this->paginate = [
            'order' => [
                'id' => 'DESC',
            ],
        ];
        $removedIpNetworks = $this->paginate($this->RemovedIpNetworks->find(
            'all',
            contain: [
                'Contracts',
                'Customers',
            ],
            conditions: $conditions
        ));

        $types_of_use = $this->RemovedIpNetworks->types_of_use;

        $this->set(compact('removedIpNetworks', 'types_of_use'));
    }

    /**
     * View method
     *
     * @param string|null $id Removed Ip Network id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $removedIpNetwork = $this->RemovedIpNetworks->get($id, contain: [
            'Customers',
            'Contracts',
            'Creators',
            'Modifiers',
            'Removers',
        ]);

        $types_of_use = $this->RemovedIpNetworks->types_of_use;

        $this->set(compact('removedIpNetwork', 'types_of_use'));
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

        $contract_id = $this->getRequest()->getParam('contract_id');
        $this->set('contract_id', $contract_id);

        $removedIpNetwork = $this->RemovedIpNetworks->newEmptyEntity();

        if (isset($customer_id)) {
            $removedIpNetwork->customer_id = $customer_id;
        }
        if (isset($contract_id)) {
            $removedIpNetwork->contract_id = $contract_id;
        }

        if ($this->getRequest()->is('post')) {
            $removedIpNetwork = $this->RemovedIpNetworks
                ->patchEntity($removedIpNetwork, $this->getRequest()->getData());

            // TODO - add who and when deleted this
            $removedIpNetwork->removed = DateTime::now();
            $removedIpNetwork->removed_by = $this->getRequest()->getAttribute('identity')['id'] ?? null;

            if ($this->RemovedIpNetworks->save($removedIpNetwork)) {
                $this->Flash->success(__('The removed IP network has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The removed IP network could not be saved. Please, try again.'));
        }
        $customers = $this->RemovedIpNetworks->Customers->find('list', [
            'order' => ['company', 'last_name', 'first_name'],
        ]);
        $contracts = $this->RemovedIpNetworks->Contracts->find('list', [
            'order' => 'Contracts.number',
            'contain' => ['ServiceTypes', 'InstallationAddresses'],
        ]);

        if (isset($customer_id)) {
            $customers->where(['Customers.id' => $customer_id]);
            $contracts->where(['Contracts.customer_id' => $customer_id]);
        }
        if (isset($contract_id)) {
            $contracts->where(['Contracts.id' => $contract_id]);
        }

        $types_of_use = $this->RemovedIpNetworks->types_of_use;

        $this->set(compact('removedIpNetwork', 'customers', 'contracts', 'types_of_use'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Removed Ip Network id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $customer_id = $this->getRequest()->getParam('customer_id');
        $this->set('customer_id', $customer_id);

        $contract_id = $this->getRequest()->getParam('contract_id');
        $this->set('contract_id', $contract_id);

        $removedIpNetwork = $this->RemovedIpNetworks->get($id, contain: []);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $removedIpNetwork = $this->RemovedIpNetworks
                ->patchEntity($removedIpNetwork, $this->getRequest()->getData());

            if ($this->RemovedIpNetworks->save($removedIpNetwork)) {
                $this->Flash->success(__('The removed IP network has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The removed IP network could not be saved. Please, try again.'));
        }
        $customers = $this->RemovedIpNetworks->Customers->find('list', [
            'order' => ['company', 'last_name', 'first_name'],
        ]);
        $contracts = $this->RemovedIpNetworks->Contracts->find('list', [
            'order' => 'Contracts.number',
            'contain' => ['ServiceTypes', 'InstallationAddresses'],
        ]);

        if (isset($customer_id)) {
            $customers->where(['Customers.id' => $customer_id]);
            $contracts->where(['Contracts.customer_id' => $customer_id]);
        }
        if (isset($contract_id)) {
            $contracts->where(['Contracts.id' => $contract_id]);
        }

        $types_of_use = $this->RemovedIpNetworks->types_of_use;

        $this->set(compact('removedIpNetwork', 'customers', 'contracts', 'types_of_use'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Removed Ip Network id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $removedIpNetwork = $this->RemovedIpNetworks->get($id);
        if ($this->RemovedIpNetworks->delete($removedIpNetwork)) {
            $this->Flash->success(__('The removed IP network has been deleted.'));
        } else {
            $this->Flash->error(__('The removed IP network could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
