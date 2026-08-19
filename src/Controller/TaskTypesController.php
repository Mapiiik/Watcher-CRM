<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;

/**
 * TaskTypes Controller
 *
 * @property \App\Model\Table\TaskTypesTable $TaskTypes
 */
class TaskTypesController extends AppController
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
                    'TaskTypes.name ILIKE' => '%' . trim((string)$search) . '%',
                ],
            ];
        }

        $this->paginate = [
            'order' => [
                'name' => 'ASC',
            ],
        ];
        $taskTypes = $this->paginate($this->TaskTypes->find(
            'all',
            contain: [],
            conditions: $conditions,
        ));

        $this->set(compact('taskTypes'));
    }

    /**
     * View method
     *
     * @param string|null $id Task Type id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): void
    {
        $taskType = $this->TaskTypes->get($id, contain: [
            'Tasks' => ['Customers', 'TaskStates', 'Users'],
            'ContractStates',
            'Creators',
            'Modifiers',
        ]);

        $this->set(compact('taskType'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(): ?Response
    {
        $taskType = $this->TaskTypes->newEmptyEntity();
        if ($this->getRequest()->is('post')) {
            $taskType = $this->TaskTypes->patchEntity($taskType, $this->getRequest()->getData());
            if ($this->TaskTypes->save($taskType)) {
                $this->Flash->success(__('The task type has been saved.'));

                return $this->afterAddRedirect(['action' => 'view', $taskType->id]);
            }
            $this->Flash->error(__('The task type could not be saved. Please, try again.'));
        }
        $this->set(compact('taskType'));

        return null;
    }

    /**
     * Edit method
     *
     * @param string|null $id Task Type id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        $taskType = $this->TaskTypes->get($id, contain: []);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $taskType = $this->TaskTypes->patchEntity($taskType, $this->getRequest()->getData());
            if ($this->TaskTypes->save($taskType)) {
                $this->Flash->success(__('The task type has been saved.'));

                return $this->afterEditRedirect(['action' => 'view', $taskType->id]);
            }
            $this->Flash->error(__('The task type could not be saved. Please, try again.'));
        }
        $this->set(compact('taskType'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Task Type id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $taskType = $this->TaskTypes->get($id);
        if ($this->TaskTypes->delete($taskType)) {
            $this->Flash->success(__('The task type has been deleted.'));
        } else {
            $this->flashValidationErrors($taskType->getErrors());
            $this->Flash->error(__('The task type could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }
}
