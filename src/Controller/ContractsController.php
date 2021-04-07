<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Contracts Controller
 *
 * @property \App\Model\Table\ContractsTable $Contracts
 * @method \App\Model\Entity\Contract[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ContractsController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $this->paginate = [
            'contain' => ['Customers', 'InstallationAddresses', 'ServiceTypes', 'InstallationTechnicians', 'Brokerages'],
        ];
        $contracts = $this->paginate($this->Contracts);

        $this->set(compact('contracts'));
    }

    /**
     * View method
     *
     * @param string|null $id Contract id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $contract = $this->Contracts->get($id, [
            'contain' => ['Customers', 'InstallationAddresses', 'ServiceTypes', 'InstallationTechnicians', 'Brokerages', 'Billings', 'BorrowedEquipments', 'Ips', 'RemovedIps', 'SoldEquipments'],
        ]);

        $this->set(compact('contract'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $contract = $this->Contracts->newEmptyEntity();
        if ($this->request->is('post')) {
            $contract = $this->Contracts->patchEntity($contract, $this->request->getData());
            if ($this->Contracts->save($contract)) {
                $this->Flash->success(__('The contract has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The contract could not be saved. Please, try again.'));
        }
        $customers = $this->Contracts->Customers->find('list', ['limit' => 200]);
        $installationAddresses = $this->Contracts->InstallationAddresses->find('list', ['limit' => 200]);
        $serviceTypes = $this->Contracts->ServiceTypes->find('list', ['limit' => 200]);
        $installationTechnicians = $this->Contracts->InstallationTechnicians->find('list', ['limit' => 200]);
        $brokerages = $this->Contracts->Brokerages->find('list', ['limit' => 200]);
        $this->set(compact('contract', 'customers', 'installationAddresses', 'serviceTypes', 'installationTechnicians', 'brokerages'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Contract id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $contract = $this->Contracts->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $contract = $this->Contracts->patchEntity($contract, $this->request->getData());
            if ($this->Contracts->save($contract)) {
                $this->Flash->success(__('The contract has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The contract could not be saved. Please, try again.'));
        }
        $customers = $this->Contracts->Customers->find('list', ['limit' => 200]);
        $installationAddresses = $this->Contracts->InstallationAddresses->find('list', ['limit' => 200]);
        $serviceTypes = $this->Contracts->ServiceTypes->find('list', ['limit' => 200]);
        $installationTechnicians = $this->Contracts->InstallationTechnicians->find('list', ['limit' => 200]);
        $brokerages = $this->Contracts->Brokerages->find('list', ['limit' => 200]);
        $this->set(compact('contract', 'customers', 'installationAddresses', 'serviceTypes', 'installationTechnicians', 'brokerages'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Contract id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $contract = $this->Contracts->get($id);
        if ($this->Contracts->delete($contract)) {
            $this->Flash->success(__('The contract has been deleted.'));
        } else {
            $this->Flash->error(__('The contract could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
