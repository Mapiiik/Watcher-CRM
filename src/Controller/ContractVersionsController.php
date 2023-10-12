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
        // filter
        $conditions = [];
        if (isset($this->customer_id)) {
            $conditions += ['Contracts.customer_id' => $this->customer_id];
        }
        if (isset($this->contract_id)) {
            $conditions += ['ContractVersions.contract_id' => $this->contract_id];
        }

        // search
        $search = $this->getRequest()->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'ContractVersions.note ILIKE' => '%' . trim($search) . '%',
                    'Contracts.number ILIKE' => '%' . trim($search) . '%',
                ],
            ];
        }

        $this->paginate = [
            'order' => [
                'valid_from' => 'DESC',
            ],
        ];
        $contractVersions = $this->paginate($this->ContractVersions->find(
            'all',
            contain: [
                'Contracts',
            ],
            conditions: $conditions
        ));

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
            'Creators',
            'Modifiers',
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
        $contractVersion = $this->ContractVersions->newEmptyEntity();

        if (isset($this->contract_id)) {
            $contractVersion->contract_id = $this->contract_id;
        }

        if ($this->getRequest()->is('post')) {
            $contractVersion = $this->ContractVersions->patchEntity($contractVersion, $this->getRequest()->getData());
            if ($this->ContractVersions->save($contractVersion)) {
                $this->Flash->success(__('The contract version has been saved.'));

                return $this->afterAddRedirect(['action' => 'view', $contractVersion->id]);
            }
            $this->Flash->error(__('The contract version could not be saved. Please, try again.'));
        }
        $contracts = $this->ContractVersions->Contracts->find(
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
            $contracts->where(['Contracts.customer_id' => $this->customer_id]);
        }
        if (isset($this->contract_id)) {
            $contracts->where(['Contracts.id' => $this->contract_id]);
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
        $contractVersion = $this->ContractVersions->get($id, contain: []);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $contractVersion = $this->ContractVersions->patchEntity($contractVersion, $this->getRequest()->getData());
            if ($this->ContractVersions->save($contractVersion)) {
                $this->Flash->success(__('The contract version has been saved.'));

                return $this->afterEditRedirect(['action' => 'view', $contractVersion->id]);
            }
            $this->Flash->error(__('The contract version could not be saved. Please, try again.'));
        }
        $contracts = $this->ContractVersions->Contracts->find(
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
            $contracts->where(['Contracts.customer_id' => $this->customer_id]);
        }
        if (isset($this->contract_id)) {
            $contracts->where(['Contracts.id' => $this->contract_id]);
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
        $this->getRequest()->allowMethod(['post', 'delete']);
        $contractVersion = $this->ContractVersions->get($id);
        if ($this->ContractVersions->delete($contractVersion)) {
            $this->Flash->success(__('The contract version has been deleted.'));
        } else {
            $this->flashValidationErrors($contractVersion->getErrors());
            $this->Flash->error(__('The contract version could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }
}
