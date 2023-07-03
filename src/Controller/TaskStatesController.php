<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * TaskStates Controller
 *
 * @property \App\Model\Table\TaskStatesTable $TaskStates
 * @method \App\Model\Entity\TaskState[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class TaskStatesController extends AppController
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
        $search = $this->request->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'TaskStates.name ILIKE' => '%' . trim($search) . '%',
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
            conditions: $conditions
        ));

        $this->set(compact('taskStates'));
    }

    /**
     * View method
     *
     * @param string|null $id Task State id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $taskState = $this->TaskStates->get($id, contain: [
            'Tasks' => ['Customers', 'Dealers', 'TaskStates', 'TaskTypes'],
            'Creators',
            'Modifiers',
        ]);

        $this->set(compact('taskState'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $taskState = $this->TaskStates->newEmptyEntity();
        if ($this->getRequest()->is('post')) {
            $taskState = $this->TaskStates->patchEntity($taskState, $this->getRequest()->getData());
            if ($this->TaskStates->save($taskState)) {
                $this->Flash->success(__('The task state has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The task state could not be saved. Please, try again.'));
        }
        $this->set(compact('taskState'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Task State id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $taskState = $this->TaskStates->get($id, contain: []);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $taskState = $this->TaskStates->patchEntity($taskState, $this->getRequest()->getData());
            if ($this->TaskStates->save($taskState)) {
                $this->Flash->success(__('The task state has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The task state could not be saved. Please, try again.'));
        }
        $this->set(compact('taskState'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Task State id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $taskState = $this->TaskStates->get($id);
        if ($this->TaskStates->delete($taskState)) {
            $this->Flash->success(__('The task state has been deleted.'));
        } else {
            $this->Flash->error(__('The task state could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
