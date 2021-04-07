<?php
declare(strict_types=1);

namespace App\Controller;

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
        $this->paginate = [
            'contain' => ['Customers', 'Queues', 'Devices', 'Dealers', 'Brokerages', 'Routers', 'Contracts'],
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
            'contain' => ['Customers', 'Queues', 'Devices', 'Dealers', 'Brokerages', 'Routers', 'Contracts'],
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
        $ip = $this->Ips->newEmptyEntity();
        if ($this->request->is('post')) {
            $ip = $this->Ips->patchEntity($ip, $this->request->getData());
            if ($this->Ips->save($ip)) {
                $this->Flash->success(__('The ip has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The ip could not be saved. Please, try again.'));
        }
        $customers = $this->Ips->Customers->find('list', ['limit' => 200]);
        $queues = $this->Ips->Queues->find('list', ['limit' => 200]);
        $devices = $this->Ips->Devices->find('list', ['limit' => 200]);
        $dealers = $this->Ips->Dealers->find('list', ['limit' => 200]);
        $brokerages = $this->Ips->Brokerages->find('list', ['limit' => 200]);
        $routers = $this->Ips->Routers->find('list', ['limit' => 200]);
        $contracts = $this->Ips->Contracts->find('list', ['limit' => 200]);
        $this->set(compact('ip', 'customers', 'queues', 'devices', 'dealers', 'brokerages', 'routers', 'contracts'));
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
        $ip = $this->Ips->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $ip = $this->Ips->patchEntity($ip, $this->request->getData());
            if ($this->Ips->save($ip)) {
                $this->Flash->success(__('The ip has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The ip could not be saved. Please, try again.'));
        }
        $customers = $this->Ips->Customers->find('list', ['limit' => 200]);
        $queues = $this->Ips->Queues->find('list', ['limit' => 200]);
        $devices = $this->Ips->Devices->find('list', ['limit' => 200]);
        $dealers = $this->Ips->Dealers->find('list', ['limit' => 200]);
        $brokerages = $this->Ips->Brokerages->find('list', ['limit' => 200]);
        $routers = $this->Ips->Routers->find('list', ['limit' => 200]);
        $contracts = $this->Ips->Contracts->find('list', ['limit' => 200]);
        $this->set(compact('ip', 'customers', 'queues', 'devices', 'dealers', 'brokerages', 'routers', 'contracts'));
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
        if ($this->Ips->delete($ip)) {
            $this->Flash->success(__('The ip has been deleted.'));
        } else {
            $this->Flash->error(__('The ip could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
