<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;

/**
 * TaskStates Controller
 *
 * @property \App\Model\Table\TaskStatesTable $TaskStates
 */
class TaskStatesController extends AppController
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
                    'TaskStates.name ILIKE' => '%' . trim((string)$search) . '%',
                ],
            ];
        }

        $this->paginate = [
            'order' => [
                'name' => 'ASC',
            ],
        ];
        $taskStates = $this->paginate($this->TaskStates->find(
            'all',
            contain: [],
            conditions: $conditions,
        ));

        $this->set(compact('taskStates'));
    }

    /**
     * View method
     *
     * @param string|null $id Task State id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): void
    {
        $taskState = $this->TaskStates->get($id, contain: [
            'Creators',
            'Modifiers',
            'Tasks' => [
                'Customers',
                'TaskStates',
                'TaskTypes',
                'Users',
            ],
        ]);

        $this->set(compact('taskState'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(): ?Response
    {
        $taskState = $this->TaskStates->newEmptyEntity();
        if ($this->getRequest()->is('post')) {
            $taskState = $this->TaskStates->patchEntity($taskState, $this->getRequest()->getData());
            if ($this->TaskStates->save($taskState)) {
                $this->Flash->success(__('The task state has been saved.'));

                return $this->afterAddRedirect(['action' => 'view', $taskState->id]);
            }
            $this->Flash->error(__('The task state could not be saved. Please, try again.'));
        }
        $this->set(compact('taskState'));

        return null;
    }

    /**
     * Edit method
     *
     * @param string|null $id Task State id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        $taskState = $this->TaskStates->get($id, contain: []);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $taskState = $this->TaskStates->patchEntity($taskState, $this->getRequest()->getData());
            if ($this->TaskStates->save($taskState)) {
                $this->Flash->success(__('The task state has been saved.'));

                return $this->afterEditRedirect(['action' => 'view', $taskState->id]);
            }
            $this->Flash->error(__('The task state could not be saved. Please, try again.'));
        }
        $this->set(compact('taskState'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Task State id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $taskState = $this->TaskStates->get($id);
        if ($this->TaskStates->delete($taskState)) {
            $this->Flash->success(__('The task state has been deleted.'));
        } else {
            $this->flashValidationErrors($taskState->getErrors());
            $this->Flash->error(__('The task state could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }
}
