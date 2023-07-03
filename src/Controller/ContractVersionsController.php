<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * ContractVersions Controller
 *
 * @property \App\Model\Table\ContractVersionsTable $ContractVersions
 * @method \App\Model\Entity\ContractVersion[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ContractVersionsController extends AppController
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
            $conditions += ['Contracts.customer_id' => $customer_id];
        }
        if (isset($contract_id)) {
            $conditions += ['ContractVersions.contract_id' => $contract_id];
        }

        // search
        $search = $this->request->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'ContractVersions.note ILIKE' => '%' . trim($search) . '%',
                    'Contracts.number ILIKE' => '%' . trim($search) . '%',
                ],
            ];
        }

        $this->paginate = [
            'contain' => ['Contracts'],
            'order' => ['valid_from' => 'DESC'],
            'conditions' => $conditions,
        ];

        $contractVersions = $this->paginate($this->ContractVersions);

        $this->set(compact('contractVersions'));
    }

    /**
     * View method
     *
     * @param string|null $id Contract Version id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $contractVersion = $this->ContractVersions->get($id, contain: [
            'Contracts' => [
                'InstallationAddresses',
                'ServiceTypes',
            ],
        ]);

        $this->set(compact('contractVersion'));
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

        $contractVersion = $this->ContractVersions->newEmptyEntity();

        if (isset($contract_id)) {
            $contractVersion->contract_id = $contract_id;
        }

        if ($this->request->is('post')) {
            $contractVersion = $this->ContractVersions->patchEntity($contractVersion, $this->request->getData());
            if ($this->ContractVersions->save($contractVersion)) {
                $this->Flash->success(__('The contract version has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The contract version could not be saved. Please, try again.'));
        }
        $contracts = $this->ContractVersions->Contracts->find('list', [
            'order' => 'Contracts.number',
            'contain' => ['ServiceTypes', 'InstallationAddresses'],
        ]);

        if (isset($customer_id)) {
            $contracts->where(['Contracts.customer_id' => $customer_id]);
        }
        if (isset($contract_id)) {
            $contracts->where(['Contracts.id' => $contract_id]);
        }

        $this->set(compact('contractVersion', 'contracts'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Contract Version id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $customer_id = $this->getRequest()->getParam('customer_id');
        $this->set('customer_id', $customer_id);

        $contract_id = $this->getRequest()->getParam('contract_id');
        $this->set('contract_id', $contract_id);

        $contractVersion = $this->ContractVersions->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $contractVersion = $this->ContractVersions->patchEntity($contractVersion, $this->request->getData());
            if ($this->ContractVersions->save($contractVersion)) {
                $this->Flash->success(__('The contract version has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The contract version could not be saved. Please, try again.'));
        }
        $contracts = $this->ContractVersions->Contracts->find('list', [
            'order' => 'Contracts.number',
            'contain' => ['ServiceTypes', 'InstallationAddresses'],
        ]);

        if (isset($customer_id)) {
            $contracts->where(['Contracts.customer_id' => $customer_id]);
        }
        if (isset($contract_id)) {
            $contracts->where(['Contracts.id' => $contract_id]);
        }

        $this->set(compact('contractVersion', 'contracts'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Contract Version id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $contractVersion = $this->ContractVersions->get($id);
        if ($this->ContractVersions->delete($contractVersion)) {
            $this->Flash->success(__('The contract version has been deleted.'));
        } else {
            $this->Flash->error(__('The contract version could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
