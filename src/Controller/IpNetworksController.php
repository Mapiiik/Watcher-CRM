<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\I18n\DateTime;

/**
 * IpNetworks Controller
 *
 * @property \App\Model\Table\IpNetworksTable $IpNetworks
 * @method \App\Model\Entity\IpNetwork[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class IpNetworksController extends AppController
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
            $conditions += ['IpNetworks.customer_id' => $this->customer_id];
        }
        if (isset($this->contract_id)) {
            $conditions += ['IpNetworks.contract_id' => $this->contract_id];
        }

        // search
        $search = $this->getRequest()->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'IpNetworks.ip_network::character varying ILIKE' => '%' . trim($search) . '%',
                    'Contracts.number ILIKE' => '%' . trim($search) . '%',
                ],
            ];
        }

        $this->paginate = [
            'order' => [
                'id' => 'DESC',
            ],
        ];
        $ipNetworks = $this->paginate($this->IpNetworks->find(
            'all',
            contain: [
                'Contracts',
                'Customers',
            ],
            conditions: $conditions
        ));

        $this->set(compact('ipNetworks'));
    }

    /**
     * View method
     *
     * @param string|null $id IP Network id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $ipNetwork = $this->IpNetworks->get($id, contain: [
            'Contracts',
            'Customers',
            'Creators',
            'Modifiers',
        ]);

        $this->set(compact('ipNetwork'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $ipNetwork = $this->IpNetworks->newEmptyEntity();

        if (isset($this->customer_id)) {
            $ipNetwork->customer_id = $this->customer_id;
        }
        if (isset($this->contract_id)) {
            $ipNetwork->contract_id = $this->contract_id;
        }

        if ($this->getRequest()->is('post')) {
            $ipNetwork = $this->IpNetworks->patchEntity($ipNetwork, $this->getRequest()->getData());
            if ($this->IpNetworks->save($ipNetwork)) {
                $this->Flash->success(__('The IP network has been saved.'));

                return $this->afterAddRedirect(['action' => 'view', $ipNetwork->id]);
            }
            $this->Flash->error(__('The IP network could not be saved. Please, try again.'));
        }
        $customers = $this->IpNetworks->Customers->find(
            'list',
            order: [
                'company',
                'last_name',
                'first_name',
            ],
        );
        $contracts = $this->IpNetworks->Contracts->find(
            'list',
            contain: [
                'InstallationAddresses',
                'ServiceTypes',
            ],
            order: [
                'Contracts.number',
            ],
        );

        if (isset($this->customer_id)) {
            $customers->where(['Customers.id' => $this->customer_id]);
            $contracts->where(['Contracts.customer_id' => $this->customer_id]);
        }
        if (isset($this->contract_id)) {
            $contracts->where(['Contracts.id' => $this->contract_id]);
        }

        $this->set(compact('ipNetwork', 'customers', 'contracts'));
    }

    /**
     * Edit method
     *
     * @param string|null $id IP Network id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $ipNetwork = $this->IpNetworks->get($id);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $ipNetwork = $this->IpNetworks->patchEntity($ipNetwork, $this->getRequest()->getData());
            if ($this->IpNetworks->save($ipNetwork)) {
                $this->Flash->success(__('The IP network has been saved.'));

                return $this->afterEditRedirect(['action' => 'view', $ipNetwork->id]);
            }
            $this->Flash->error(__('The IP network could not be saved. Please, try again.'));
        }
        $customers = $this->IpNetworks->Customers->find(
            'list',
            order: [
                'company',
                'last_name',
                'first_name',
            ],
        );
        $contracts = $this->IpNetworks->Contracts->find(
            'list',
            contain: [
                'InstallationAddresses',
                'ServiceTypes',
            ],
            order: [
                'Contracts.number',
            ],
        );

        if (isset($this->customer_id)) {
            $customers->where(['Customers.id' => $this->customer_id]);
            $contracts->where(['Contracts.customer_id' => $this->customer_id]);
        }
        if (isset($this->contract_id)) {
            $contracts->where(['Contracts.id' => $this->contract_id]);
        }

        $this->set(compact('ipNetwork', 'customers', 'contracts'));
    }

    /**
     * Delete method
     *
     * @param string|null $id IP Network id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $ipNetwork = $this->IpNetworks->get($id);

        if ($this->addToRemovedIpNetworks($id)) {
            if ($this->IpNetworks->delete($ipNetwork)) {
                $this->Flash->success(__('The IP network has been deleted.'));
            } else {
                $this->flashValidationErrors($ipNetwork->getErrors());
                $this->Flash->error(__('The IP network could not be deleted. Please, try again.'));
            }
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }

    /**
     * Add IP network to removed IP networks table (usage before delete)
     *
     * @param string|null $id IP Network id.
     * @return bool
     */
    private function addToRemovedIpNetworks(?string $id = null)
    {
        $ipNetwork = $this->IpNetworks->get($id);

        /** @var \App\Model\Table\RemovedIpNetworksTable $removedIpNetworksTable */
        $removedIpNetworksTable = $this->fetchTable('RemovedIpNetworks');

        $removedIpNetwork = $removedIpNetworksTable->newEmptyEntity();
        $removedIpNetwork = $removedIpNetworksTable->patchEntity($removedIpNetwork, $ipNetwork->toArray());

        // TODO - add who and when deleted this
        $removedIpNetwork->removed = DateTime::now();
        $removedIpNetwork->removed_by = $this->getRequest()->getAttribute('identity')['id'] ?? null;

        if ($removedIpNetworksTable->save($removedIpNetwork)) {
            $this->Flash->success(__('The removed IP network has been saved.'));

            return true;
        }

        $this->Flash->error(__('The removed IP network could not be saved. Please, try again.'));

        return false;
    }
}
