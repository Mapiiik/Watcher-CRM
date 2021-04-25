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
        $customer_id = $this->request->getParam('customer_id');
        $this->set('customer_id', $customer_id);
        
        $contract_id = $this->request->getParam('contract_id');
        $this->set('contract_id', $contract_id);

        $conditions = [];
        if (isset($customer_id)) {
            $conditions += ['Ips.customer_id' => $customer_id];
        }
        if (isset($contract_id)) {
            $conditions += ['Ips.contract_id' => $contract_id];
        }

        $this->paginate = [
            'contain' => ['Customers', 'Contracts'],
            'conditions' => $conditions,
        ];
        $ips = $this->paginate($this->Ips);

        $this->set(compact('ips'));
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
            'contain' => ['Customers', 'Contracts'],
        ]);

        $this->set(compact('ip'));
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

        $ip = $this->Ips->newEmptyEntity();

        $conditions = [];
        if (isset($customer_id)) {
            $ip = $this->Ips->patchEntity($ip, ['customer_id' => $customer_id]);
            $conditions += ['customer_id' => $customer_id];
        }
        if (isset($contract_id)) {
            $ip = $this->Ips->patchEntity($ip, ['contract_id' => $contract_id]);
        }

        if ($this->request->is('post')) {
            $ip = $this->Ips->patchEntity($ip, $this->request->getData());
            if ($this->Ips->save($ip)) {
                $this->Flash->success(__('The ip has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The ip could not be saved. Please, try again.'));
        }
        $customers = $this->Ips->Customers->find('list', ['order' => ['company', 'first_name', 'last_name']]);
        $contracts = $this->Ips->Contracts->find('list', ['order' => 'number', 'conditions' => $conditions]);
        $this->set(compact('ip', 'customers', 'contracts'));
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
        $customer_id = $this->request->getParam('customer_id');
        $this->set('customer_id', $customer_id);
        
        $contract_id = $this->request->getParam('contract_id');
        $this->set('contract_id', $contract_id);

        $ip = $this->Ips->get($id, [
            'contain' => [],
        ]);

        $conditions = [];
        if (isset($customer_id)) {
            $conditions += ['customer_id' => $customer_id];
        }
        
        if ($this->request->is(['patch', 'post', 'put'])) {
            $ip = $this->Ips->patchEntity($ip, $this->request->getData());
            if ($this->Ips->save($ip)) {
                $this->Flash->success(__('The ip has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The ip could not be saved. Please, try again.'));
        }
        $customers = $this->Ips->Customers->find('list', ['order' => ['company', 'first_name', 'last_name']]);
        $contracts = $this->Ips->Contracts->find('list', ['order' => 'number', 'conditions' => $conditions]);
        $this->set(compact('ip', 'customers', 'contracts'));
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
        $this->request->allowMethod(['post', 'delete']);
        $ip = $this->Ips->get($id);
        
        if ($this->addToRemovedIps($id))
        {
            if ($this->Ips->delete($ip)) {
                $this->Flash->success(__('The ip has been deleted.'));
            } else {
                $this->Flash->error(__('The ip could not be deleted. Please, try again.'));
            }
        }

        return $this->redirect(['action' => 'index']);
    }
    
    private function addToRemovedIps ($id = null)
    {
        $ip = $this->Ips->get($id);
        
        $this->RemovedIps = $this->getTableLocator()->get('RemovedIps');
        
        $removedIp = $this->RemovedIps->newEmptyEntity();
        $removedIp = $this->RemovedIps->patchEntity($removedIp, $ip->toArray());
        
        // TODO - add who and when deleted this
        $removedIp->removed = FrozenTime::now();
        $removedIp->removed_by = $_SESSION['login_id'];
        
        if ($this->RemovedIps->save($removedIp)) {
            $this->Flash->success(__('The removed ip has been saved.'));
            return true;
        }
        
        debug($removedIp);

        $this->Flash->error(__('The removed ip could not be saved. Please, try again.'));
        return false;
    }
}
