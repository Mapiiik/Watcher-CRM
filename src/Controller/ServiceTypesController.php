<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * ServiceTypes Controller
 *
 * @property \App\Model\Table\ServiceTypesTable $ServiceTypes
 * @method \App\Model\Entity\ServiceType[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ServiceTypesController extends AppController
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

        // search
        $search = $this->getRequest()->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'ServiceTypes.name ILIKE' => '%' . trim($search) . '%',
                ],
            ];
        }

        $this->paginate = [
            'order' => [
                'name' => 'ASC',
            ],
        ];

        $serviceTypes = $this->paginate($this->ServiceTypes->find(
            'all',
            contain: [],
            conditions: $conditions
        ));

        $this->set(compact('serviceTypes'));
    }

    /**
     * View method
     *
     * @param string|null $id Service Type id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $serviceType = $this->ServiceTypes->get($id, contain: [
            'Contracts' => [
                'Customers',
                'ContractStates',
                'InstallationAddresses',
                'InstallationTechnicians',
                'UninstallationTechnicians',
                'Commissions',
            ],
            'Services' => ['Queues'],
            'Creators',
            'Modifiers',
        ]);

        $this->set(compact('serviceType'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $serviceType = $this->ServiceTypes->newEmptyEntity();
        if ($this->getRequest()->is('post')) {
            $serviceType = $this->ServiceTypes->patchEntity($serviceType, $this->getRequest()->getData());
            if ($this->ServiceTypes->save($serviceType)) {
                $this->Flash->success(__('The service type has been saved.'));

                return $this->afterAddRedirect(['action' => 'view', $serviceType->id]);
            }
            $this->Flash->error(__('The service type could not be saved. Please, try again.'));
        }
        $this->set(compact('serviceType'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Service Type id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $serviceType = $this->ServiceTypes->get($id, contain: []);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $serviceType = $this->ServiceTypes->patchEntity($serviceType, $this->getRequest()->getData());
            if ($this->ServiceTypes->save($serviceType)) {
                $this->Flash->success(__('The service type has been saved.'));

                return $this->afterEditRedirect(['action' => 'view', $serviceType->id]);
            }
            $this->Flash->error(__('The service type could not be saved. Please, try again.'));
        }
        $this->set(compact('serviceType'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Service Type id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $serviceType = $this->ServiceTypes->get($id);
        if ($this->ServiceTypes->delete($serviceType)) {
            $this->Flash->success(__('The service type has been deleted.'));
        } else {
            $this->flashValidationErrors($serviceType->getErrors());
            $this->Flash->error(__('The service type could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }
}
