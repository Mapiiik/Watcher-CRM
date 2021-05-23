<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\I18n\FrozenTime;

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
        $customer_id = $this->request->getParam('customer_id');
        $this->set('customer_id', $customer_id);
        
        $contract_id = $this->request->getParam('contract_id');
        $this->set('contract_id', $contract_id);

        $conditions = [];
        if (isset($customer_id)) {
            $conditions += ['RemovedIps.customer_id' => $customer_id];
        }
        if (isset($contract_id)) {
            $conditions += ['RemovedIps.contract_id' => $contract_id];
        }

        $this->paginate = [
            'contain' => ['Customers', 'Contracts'],
            'conditions' => $conditions,
        ];
        $removedIps = $this->paginate($this->RemovedIps);

        $this->set(compact('removedIps'));
    }

    /**
     * View method
     *
     * @param string|null $id Removed Ip id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $removedIp = $this->RemovedIps->get($id, [
            'contain' => ['Customers', 'Contracts'],
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
        $customer_id = $this->request->getParam('customer_id');
        $this->set('customer_id', $customer_id);
        
        $contract_id = $this->request->getParam('contract_id');
        $this->set('contract_id', $contract_id);

        $removedIp = $this->RemovedIps->newEmptyEntity();

        if (isset($customer_id)) $removedIp = $this->RemovedIps->patchEntity($removedIp, ['customer_id' => $customer_id]);
        if (isset($contract_id)) $removedIp = $this->RemovedIps->patchEntity($removedIp, ['contract_id' => $contract_id]);

        if ($this->request->is('post')) {
            $removedIp = $this->RemovedIps->patchEntity($removedIp, $this->request->getData());

            // TODO - add who and when deleted this
            $removedIp->removed = FrozenTime::now();
            $removedIp->removed_by = $this->request->getSession()->read('Auth.id');
            
            if ($this->RemovedIps->save($removedIp)) {
                $this->Flash->success(__('The removed ip has been saved.'));

                if (isset($contract_id)) return $this->redirect(['controller' => 'Contracts', 'action' => 'view', $contract_id]);
                
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The removed ip could not be saved. Please, try again.'));
        }
        $customers = $this->RemovedIps->Customers->find('list', ['order' => ['company', 'first_name', 'last_name']]);
        $contracts = $this->RemovedIps->Contracts->find('list', ['order' => 'number']);

        if (isset($customer_id)) {
            $customers->where(['id' => $customer_id]);
            $contracts->where(['customer_id' => $customer_id]);
        }
        if (isset($contract_id)) {
            $contracts->where(['id' => $contract_id]);
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
    public function edit($id = null)
    {
        $customer_id = $this->request->getParam('customer_id');
        $this->set('customer_id', $customer_id);
        
        $contract_id = $this->request->getParam('contract_id');
        $this->set('contract_id', $contract_id);

        $removedIp = $this->RemovedIps->get($id, [
            'contain' => [],
        ]);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $removedIp = $this->RemovedIps->patchEntity($removedIp, $this->request->getData());
            if ($this->RemovedIps->save($removedIp)) {
                $this->Flash->success(__('The removed ip has been saved.'));

                if (isset($contract_id)) return $this->redirect(['controller' => 'Contracts', 'action' => 'view', $contract_id]);
                
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The removed ip could not be saved. Please, try again.'));
        }
        $customers = $this->RemovedIps->Customers->find('list', ['order' => ['company', 'first_name', 'last_name']]);
        $contracts = $this->RemovedIps->Contracts->find('list', ['order' => 'number']);

        if (isset($customer_id)) {
            $customers->where(['id' => $customer_id]);
            $contracts->where(['customer_id' => $customer_id]);
        }
        if (isset($contract_id)) {
            $contracts->where(['id' => $contract_id]);
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
    public function delete($id = null)
    {
        $customer_id = $this->request->getParam('customer_id');
        $contract_id = $this->request->getParam('contract_id');

        $this->request->allowMethod(['post', 'delete']);
        $removedIp = $this->RemovedIps->get($id);
        if ($this->RemovedIps->delete($removedIp)) {
            $this->Flash->success(__('The removed ip has been deleted.'));
        } else {
            $this->Flash->error(__('The removed ip could not be deleted. Please, try again.'));
        }

        if (isset($contract_id)) return $this->redirect(['controller' => 'Contracts', 'action' => 'view', $contract_id]);
                
        return $this->redirect(['action' => 'index']);
    }
}
