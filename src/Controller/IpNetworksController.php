<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\RemovedIpNetworksTable;
use Cake\Http\Response;
use Cake\I18n\DateTime;

/**
 * IpNetworks Controller
 *
 * @property \App\Model\Table\IpNetworksTable $IpNetworks
 */
class IpNetworksController extends AppController
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
            $conditions += ['IpNetworks.customer_id' => $this->customer_id];
        }
        if ($this->contract_id !== null) {
            $conditions += ['IpNetworks.contract_id' => $this->contract_id];
        }

        // search
        $search = $this->getRequest()->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'IpNetworks.ip_network::character varying ILIKE' => '%' . trim((string)$search) . '%',
                    'Contracts.number ILIKE' => '%' . trim((string)$search) . '%',
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
            conditions: $conditions,
        ));

        $this->set(compact('ipNetworks'));
    }

    /**
     * View method
     *
     * @param string|null $id IP Network id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): void
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
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(): ?Response
    {
        $ipNetwork = $this->IpNetworks->newEmptyEntity();

        if ($this->customer_id !== null) {
            $ipNetwork->customer_id = $this->customer_id;
        }
        if ($this->contract_id !== null) {
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

        if ($this->customer_id !== null) {
            $customers->where(['Customers.id' => $this->customer_id]);
            $contracts->where(['Contracts.customer_id' => $this->customer_id]);
        }
        if ($this->contract_id !== null) {
            $contracts->where(['Contracts.id' => $this->contract_id]);
        }

        $this->set(compact('ipNetwork', 'customers', 'contracts'));

        return null;
    }

    /**
     * Edit method
     *
     * @param string|null $id IP Network id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
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

        if ($this->customer_id !== null) {
            $customers->where(['Customers.id' => $this->customer_id]);
            $contracts->where(['Contracts.customer_id' => $this->customer_id]);
        }
        if ($this->contract_id !== null) {
            $contracts->where(['Contracts.id' => $this->contract_id]);
        }

        $this->set(compact('ipNetwork', 'customers', 'contracts'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id IP Network id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
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
    private function addToRemovedIpNetworks(?string $id = null): bool
    {
        $ipNetwork = $this->IpNetworks->get($id);

        /** @var \App\Model\Table\RemovedIpNetworksTable $removedIpNetworksTable */
        $removedIpNetworksTable = $this->fetchTable(RemovedIpNetworksTable::class);

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
