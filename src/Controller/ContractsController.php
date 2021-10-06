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
        $customer_id = $this->request->getParam('customer_id');
        $this->set('customer_id', $customer_id);
        
        $conditions = [];
        if (isset($customer_id)) {
            $conditions = ['Contracts.customer_id' => $customer_id];
        }

        $this->paginate = [
            'contain' => ['Customers', 'InstallationAddresses', 'ServiceTypes', 'InstallationTechnicians', 'Brokerages'],
            'conditions' => $conditions,
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
            'contain' => ['Customers', 'InstallationAddresses', 'ServiceTypes', 'InstallationTechnicians', 'Brokerages', 'Billings' => ['Services'], 'BorrowedEquipments' => ['EquipmentTypes'], 'Ips', 'RemovedIps', 'SoldEquipments' => ['EquipmentTypes']],
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
        $customer_id = $this->request->getParam('customer_id');
        $this->set('customer_id', $customer_id);

        $contract = $this->Contracts->newEmptyEntity();

        if (isset($customer_id)) $contract = $this->Contracts->patchEntity($contract, ['customer_id' => $customer_id]);
        
        if ($this->request->is('post')) {
            $contract = $this->Contracts->patchEntity($contract, $this->request->getData());
            if ($this->Contracts->save($contract)) {
                $this->Flash->success(__('The contract has been saved.'));
                
                $this->updateNumber($contract->id);

                return $this->redirect(['action' => 'view', $contract->id]);
            }
            $this->Flash->error(__('The contract could not be saved. Please, try again.'));
        }
        $customers = $this->Contracts->Customers->find('list', ['order' => ['company', 'first_name', 'last_name']]);
        $installationAddresses = $this->Contracts->InstallationAddresses->find('list', ['order' => ['company', 'first_name', 'last_name']]);
        $serviceTypes = $this->Contracts->ServiceTypes->find('list', ['order' => 'id']);
        $installationTechnicians = $this->Contracts->InstallationTechnicians->find('list', ['order' => ['company', 'first_name', 'last_name']]);
        $brokerages = $this->Contracts->Brokerages->find('list', ['order' => 'name']);

        if (isset($customer_id)) {
            $customers->where(['id' => $customer_id]);
            $installationAddresses->where([['customer_id' => $customer_id]]);
        }

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
        $customer_id = $this->request->getParam('customer_id');
        $this->set('customer_id', $customer_id);

        $contract = $this->Contracts->get($id, [
            'contain' => [],
        ]);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $contract = $this->Contracts->patchEntity($contract, $this->request->getData());
            if ($this->Contracts->save($contract)) {
                $this->Flash->success(__('The contract has been saved.'));

                if (isset($customer_id)) return $this->redirect(['controller' => 'Customers', 'action' => 'view', $customer_id]);
                
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The contract could not be saved. Please, try again.'));
        }
        $customers = $this->Contracts->Customers->find('list', ['order' => ['company', 'first_name', 'last_name']]);
        $installationAddresses = $this->Contracts->InstallationAddresses->find('list', ['order' => ['company', 'first_name', 'last_name']]);
        $serviceTypes = $this->Contracts->ServiceTypes->find('list', ['order' => 'id']);
        $installationTechnicians = $this->Contracts->InstallationTechnicians->find('list', ['order' => ['company', 'first_name', 'last_name']]);
        $brokerages = $this->Contracts->Brokerages->find('list', ['order' => 'name']);

        if (isset($customer_id)) {
            $customers->where(['id' => $customer_id]);
            $installationAddresses->where([['customer_id' => $customer_id]]);
        }

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
        $customer_id = $this->request->getParam('customer_id');

        $this->request->allowMethod(['post', 'delete']);
        $contract = $this->Contracts->get($id);
        if ($this->Contracts->delete($contract)) {
            $this->Flash->success(__('The contract has been deleted.'));
        } else {
            $this->Flash->error(__('The contract could not be deleted. Please, try again.'));
        }

        if (isset($customer_id)) return $this->redirect(['controller' => 'Customers', 'action' => 'view', $customer_id]);
                
        return $this->redirect(['action' => 'index']);
    }
    
    private function updateNumber($id = null)
    {
        $contract = $this->Contracts->get($id);
        $service_type = $this->Contracts->ServiceTypes->get($contract->service_type_id);
        
        $query = $this->Contracts->query();
        $query->update()
            ->set(['number = (' . $service_type->contract_number_format . ')'])
            ->where(['id' => $contract->id]);
        
        if ($query->execute()) {
            $this->Flash->success(__('The contract number has been updated.'));
            return true;
        }

        $this->Flash->error(__('The contract number could not be updated. Please, try again.'));
        return false;
    }

    /**
     * Print method
     *
     * @param string|null $id Contract id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function print($id = null, $type = null)
    {
        $customer_id = $this->request->getParam('customer_id');
        $this->set('customer_id', $customer_id);

        $contract = $this->Contracts->get($id, [
            'contain' => ['Customers' => ['Emails', 'Phones', 'Addresses'], 'InstallationAddresses', 'ServiceTypes', 'InstallationTechnicians', 'Brokerages', 'Billings' => ['Services'], 'BorrowedEquipments' => ['EquipmentTypes'], 'Ips', 'RemovedIps', 'SoldEquipments' => ['EquipmentTypes']],
        ]);
        
        $query = $this->getRequest()->getQuery();

        if ($this->request->getParam('_ext') === 'pdf') {
            switch ($type) {
            case 'contract-termination':
                if (!$contract->has('valid_until')) {
                    $this->Flash->error(__('Please set a date until which the contract is valid.'));
                    return $this->redirect(['action' => 'edit', $id]);
                }
            case 'contract-amendment':
                $contract->valid_from = \Cake\I18n\FrozenDate::createFromDate(11111, 11, 1);
            case 'contract-new-x':
                if (!$contract->has('conclusion_date')) {
                    $this->Flash->error(__('Please set the date of conclusion of the original contract.'));
                    return $this->redirect(['action' => 'edit', $id]);
                }
            case 'contract-new':
                if (!$contract->has('valid_from')) {
                    $this->Flash->error(__('Please set the date from which the contract is valid.'));
                    return $this->redirect(['action' => 'edit', $id]);
                }
                break;

            default:
                $this->Flash->error(__('Invalid type of document requested.'));
                return $this->redirect(['action' => 'print', $id, '?' => $query]);
            }

            // filter and split billings
            $contract->individual_billings = [];
            $contract->standard_billings = []; 

            foreach ($contract->billings as $billing) {
                // skip non active items
                if (!$billing->active) {
                    continue;
                }
                if ($billing->has('billing_from') && $billing->billing_from > $contract->valid_from) {
                    continue;
                }
                if ($billing->has('billing_until') && $billing->billing_until < $contract->valid_from) {
                    continue;
                }

                // split by individual/standard price
                if ($billing->has('price')) {
                    $contract->individual_billings[] = $billing;
                } else {
                    $contract->standard_billings[] = $billing;
                }
            }
        }


        $this->set(compact('contract', 'type', 'query'));
    }
}
