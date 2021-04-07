<?php
declare(strict_types=1);

namespace App\Controller;

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
        $this->paginate = [
            'contain' => ['Customers', 'Queues', 'Devices', 'Dealers', 'Brokerages', 'Contracts'],
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
            'contain' => ['Customers', 'Queues', 'Devices', 'Dealers', 'Brokerages', 'Contracts'],
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
        if ($this->request->is('post')) {
            $removedIp = $this->RemovedIps->patchEntity($removedIp, $this->request->getData());
            if ($this->RemovedIps->save($removedIp)) {
                $this->Flash->success(__('The removed ip has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The removed ip could not be saved. Please, try again.'));
        }
        $customers = $this->RemovedIps->Customers->find('list', ['limit' => 200]);
        $queues = $this->RemovedIps->Queues->find('list', ['limit' => 200]);
        $devices = $this->RemovedIps->Devices->find('list', ['limit' => 200]);
        $dealers = $this->RemovedIps->Dealers->find('list', ['limit' => 200]);
        $brokerages = $this->RemovedIps->Brokerages->find('list', ['limit' => 200]);
        $contracts = $this->RemovedIps->Contracts->find('list', ['limit' => 200]);
        $this->set(compact('removedIp', 'customers', 'queues', 'devices', 'dealers', 'brokerages', 'contracts'));
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
        $removedIp = $this->RemovedIps->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $removedIp = $this->RemovedIps->patchEntity($removedIp, $this->request->getData());
            if ($this->RemovedIps->save($removedIp)) {
                $this->Flash->success(__('The removed ip has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The removed ip could not be saved. Please, try again.'));
        }
        $customers = $this->RemovedIps->Customers->find('list', ['limit' => 200]);
        $queues = $this->RemovedIps->Queues->find('list', ['limit' => 200]);
        $devices = $this->RemovedIps->Devices->find('list', ['limit' => 200]);
        $dealers = $this->RemovedIps->Dealers->find('list', ['limit' => 200]);
        $brokerages = $this->RemovedIps->Brokerages->find('list', ['limit' => 200]);
        $contracts = $this->RemovedIps->Contracts->find('list', ['limit' => 200]);
        $this->set(compact('removedIp', 'customers', 'queues', 'devices', 'dealers', 'brokerages', 'contracts'));
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
        $this->request->allowMethod(['post', 'delete']);
        $removedIp = $this->RemovedIps->get($id);
        if ($this->RemovedIps->delete($removedIp)) {
            $this->Flash->success(__('The removed ip has been deleted.'));
        } else {
            $this->Flash->error(__('The removed ip could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
