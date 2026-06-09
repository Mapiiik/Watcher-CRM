<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;

/**
 * Services Controller
 *
 * @property \App\Model\Table\ServicesTable $Services
 */
class ServicesController extends AppController
{
    /**
     * Index method
     *
     * @return void Renders view
     */
    public function index(): void
    {
        // filter
        $conditions = [];

        // search
        $search = $this->getRequest()->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'Services.name ILIKE' => '%' . trim((string)$search) . '%',
                ],
            ];
        }

        $this->paginate = [
            'order' => [
                'name' => 'ASC',
            ],
        ];

        $services = $this->paginate($this->Services->find(
            'all',
            contain: [
                'Queues',
                'ServiceTypes',
            ],
            conditions: $conditions,
        ));

        $this->set(compact('services'));
    }

    /**
     * View method
     *
     * @param string|null $id Service id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): void
    {
        $service = $this->Services->get($id, contain: [
            'ServiceTypes',
            'Queues',
            'Billings' => [
                'Contracts' => ['ContractStates'],
                'Customers',
                'Services',
            ],
            'Creators',
            'Modifiers',
        ]);

        $this->set(compact('service'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(): ?Response
    {
        $service = $this->Services->newEmptyEntity();
        if ($this->getRequest()->is('post')) {
            $service = $this->Services->patchEntity($service, $this->getRequest()->getData());
            if ($this->Services->save($service)) {
                $this->Flash->success(__('The service has been saved.'));

                return $this->afterAddRedirect(['action' => 'view', $service->id]);
            }
            $this->Flash->error(__('The service could not be saved. Please, try again.'));
        }
        $serviceTypes = $this->Services->ServiceTypes->find('list', order: [
            'name',
        ]);
        $queues = $this->Services->Queues->find(
            'list',
            valueField: [
                'name',
                'caption',
            ],
            valueSeparator: ' | ',
            order: [
                'name',
            ],
        );
        $this->set(compact('service', 'serviceTypes', 'queues'));

        return null;
    }

    /**
     * Edit method
     *
     * @param string|null $id Service id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        $service = $this->Services->get($id, contain: []);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $service = $this->Services->patchEntity($service, $this->getRequest()->getData());
            if ($this->Services->save($service)) {
                $this->Flash->success(__('The service has been saved.'));

                return $this->afterEditRedirect(['action' => 'view', $service->id]);
            }
            $this->Flash->error(__('The service could not be saved. Please, try again.'));
        }
        $serviceTypes = $this->Services->ServiceTypes->find('list', order: [
            'name',
        ]);
        $queues = $this->Services->Queues->find(
            'list',
            valueField: [
                'name',
                'caption',
            ],
            valueSeparator: ' | ',
            order: [
                'name',
            ],
        );
        $this->set(compact('service', 'serviceTypes', 'queues'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Service id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $service = $this->Services->get($id);
        if ($this->Services->delete($service)) {
            $this->Flash->success(__('The service has been deleted.'));
        } else {
            $this->flashValidationErrors($service->getErrors());
            $this->Flash->error(__('The service could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }
}
