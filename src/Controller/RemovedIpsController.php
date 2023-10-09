<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\I18n\DateTime;

/**
 * RemovedIps Controller
 *
 * @property \App\Model\Table\RemovedIpsTable $RemovedIps
 * @method \App\Model\Entity\RemovedIp[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class RemovedIpsController extends AppController
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
            $conditions += ['RemovedIps.customer_id' => $this->customer_id];
        }
        if (isset($this->contract_id)) {
            $conditions += ['RemovedIps.contract_id' => $this->contract_id];
        }

        // search
        $search = $this->getRequest()->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'RemovedIps.ip::character varying ILIKE' => '%' . trim($search) . '%',
                    'Contracts.number ILIKE' => '%' . trim($search) . '%',
                ],
            ];
        }

        $this->paginate = [
            'order' => [
                'id' => 'DESC',
            ],
        ];
        $removedIps = $this->paginate($this->RemovedIps->find(
            'all',
            contain: [
                'Contracts',
                'Customers',
            ],
            conditions: $conditions
        ));

        $this->set(compact('removedIps'));
    }

    /**
     * View method
     *
     * @param string|null $id Removed Ip id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $removedIp = $this->RemovedIps->get($id, contain: [
            'Customers',
            'Contracts',
            'Creators',
            'Modifiers',
            'Removers',
        ]);

        $this->set(compact('removedIp'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $removedIp = $this->RemovedIps->newEmptyEntity();

        if (isset($this->customer_id)) {
            $removedIp->customer_id = $this->customer_id;
        }
        if (isset($this->contract_id)) {
            $removedIp->contract_id = $this->contract_id;
        }

        if ($this->getRequest()->is('post')) {
            $removedIp = $this->RemovedIps->patchEntity($removedIp, $this->getRequest()->getData());

            // TODO - add who and when deleted this
            $removedIp->removed = DateTime::now();
            $removedIp->removed_by = $this->getRequest()->getAttribute('identity')['id'] ?? null;

            if ($this->RemovedIps->save($removedIp)) {
                $this->Flash->success(__('The removed IP address has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The removed IP address could not be saved. Please, try again.'));
        }
        $customers = $this->RemovedIps->Customers->find(
            'list',
            order: [
                'company',
                'last_name',
                'first_name',
            ],
        );
        $contracts = $this->RemovedIps->Contracts->find(
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

        $this->set(compact('removedIp', 'customers', 'contracts'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Removed Ip id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $removedIp = $this->RemovedIps->get($id, contain: []);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $removedIp = $this->RemovedIps->patchEntity($removedIp, $this->getRequest()->getData());
            if ($this->RemovedIps->save($removedIp)) {
                $this->Flash->success(__('The removed IP address has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The removed IP address could not be saved. Please, try again.'));
        }
        $customers = $this->RemovedIps->Customers->find('list', order: [
            'company',
            'last_name',
            'first_name',
        ]);
        $contracts = $this->RemovedIps->Contracts->find(
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

        $this->set(compact('removedIp', 'customers', 'contracts'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Removed Ip id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $removedIp = $this->RemovedIps->get($id);
        if ($this->RemovedIps->delete($removedIp)) {
            $this->Flash->success(__('The removed IP address has been deleted.'));
        } else {
            $this->Flash->error(__('The removed IP address could not be deleted. Please, try again.'));
        }

        if (isset($this->contract_id)) {
            return $this->redirect(['controller' => 'Contracts', 'action' => 'view', $this->contract_id]);
        }

        return $this->redirect(['action' => 'index']);
    }
}
