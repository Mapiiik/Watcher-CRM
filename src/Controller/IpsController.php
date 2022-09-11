<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\I18n\FrozenTime;

/**
 * Ips Controller
 *
 * @property \App\Model\Table\IpsTable $Ips
 * @method \App\Model\Entity\Ip[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class IpsController extends AppController
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
            $conditions += ['Ips.customer_id' => $customer_id];
        }
        if (isset($contract_id)) {
            $conditions += ['Ips.contract_id' => $contract_id];
        }

        // search
        $search = $this->request->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'Ips.ip::character varying ILIKE' => '%' . trim($search) . '%',
                    'Contracts.number ILIKE' => '%' . trim($search) . '%',
                ],
            ];
        }

        $this->paginate = [
            'contain' => ['Customers', 'Contracts'],
            'order' => ['id' => 'DESC'],
            'conditions' => $conditions,
        ];
        $ips = $this->paginate($this->Ips);

        $types_of_use = $this->Ips->types_of_use;

        $this->set(compact('ips', 'types_of_use'));
    }

    /**
     * View method
     *
     * @param string|null $id Ip id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $ip = $this->Ips->get($id, [
            'contain' => [
                'Customers',
                'Contracts',
                'Creators',
                'Modifiers',
            ],
        ]);

        $types_of_use = $this->Ips->types_of_use;

        $this->set(compact('ip', 'types_of_use'));
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

        $ip = $this->Ips->newEmptyEntity();

        if (isset($customer_id)) {
            $ip->customer_id = $customer_id;
        }
        if (isset($contract_id)) {
            $ip->contract_id = $contract_id;
        }

        if ($this->getRequest()->is('post')) {
            $ip = $this->Ips->patchEntity($ip, $this->getRequest()->getData());
            if ($this->Ips->save($ip)) {
                $this->Flash->success(__('The ip has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The ip could not be saved. Please, try again.'));
        }
        $customers = $this->Ips->Customers->find('list', [
            'order' => ['company', 'last_name', 'first_name'],
        ]);
        $contracts = $this->Ips->Contracts->find('list', [
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

        $types_of_use = $this->Ips->types_of_use;

        $this->set(compact('ip', 'customers', 'contracts', 'types_of_use'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Ip id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $customer_id = $this->getRequest()->getParam('customer_id');
        $this->set('customer_id', $customer_id);

        $contract_id = $this->getRequest()->getParam('contract_id');
        $this->set('contract_id', $contract_id);

        $ip = $this->Ips->get($id, [
            'contain' => [],
        ]);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $ip = $this->Ips->patchEntity($ip, $this->getRequest()->getData());
            if ($this->Ips->save($ip)) {
                $this->Flash->success(__('The ip has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The ip could not be saved. Please, try again.'));
        }
        $customers = $this->Ips->Customers->find('list', ['order' => ['company', 'last_name', 'first_name']]);
        $contracts = $this->Ips->Contracts->find('list', [
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

        $types_of_use = $this->Ips->types_of_use;

        $this->set(compact('ip', 'customers', 'contracts', 'types_of_use'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Ip id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $customer_id = $this->getRequest()->getParam('customer_id');
        $contract_id = $this->getRequest()->getParam('contract_id');

        $this->getRequest()->allowMethod(['post', 'delete']);
        $ip = $this->Ips->get($id);

        if ($this->addToRemovedIps($id)) {
            if ($this->Ips->delete($ip)) {
                $this->Flash->success(__('The ip has been deleted.'));
            } else {
                $this->Flash->error(__('The ip could not be deleted. Please, try again.'));
            }
        }

        if (isset($contract_id)) {
            return $this->redirect(['controller' => 'Contracts', 'action' => 'view', $contract_id]);
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Add IP to removed IPs table (usage before delete)
     *
     * @param string|null $id Ip id.
     * @return bool
     */
    private function addToRemovedIps($id = null)
    {
        $ip = $this->Ips->get($id);

        /** @var \App\Model\Table\RemovedIpsTable $removedIpsTable */
        $removedIpsTable = $this->fetchTable('RemovedIps');

        $removedIp = $removedIpsTable->newEmptyEntity();
        $removedIp = $removedIpsTable->patchEntity($removedIp, $ip->toArray());

        // TODO - add who and when deleted this
        $removedIp->removed = FrozenTime::now();
        $removedIp->removed_by = $this->getRequest()->getAttribute('identity')['id'] ?? null;

        if ($removedIpsTable->save($removedIp)) {
            $this->Flash->success(__('The removed ip has been saved.'));

            return true;
        }

        $this->Flash->error(__('The removed ip could not be saved. Please, try again.'));

        return false;
    }
}
