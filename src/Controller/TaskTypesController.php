<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * TaskTypes Controller
 *
 * @property \App\Model\Table\TaskTypesTable $TaskTypes
 * @method \App\Model\Entity\TaskType[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class TaskTypesController extends AppController
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
                    'TaskTypes.name ILIKE' => '%' . trim($search) . '%',
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
            conditions: $conditions
        ));

        $this->set(compact('taskTypes'));
    }

    /**
     * View method
     *
     * @param string|null $id Task Type id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $taskType = $this->TaskTypes->get($id, contain: [
            'Tasks' => ['Customers', 'Dealers', 'TaskStates'],
            'Creators',
            'Modifiers',
        ]);

        $this->set(compact('taskType'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $taskType = $this->TaskTypes->newEmptyEntity();
        if ($this->getRequest()->is('post')) {
            $taskType = $this->TaskTypes->patchEntity($taskType, $this->getRequest()->getData());
            if ($this->TaskTypes->save($taskType)) {
                $this->Flash->success(__('The task type has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The task type could not be saved. Please, try again.'));
        }
        $this->set(compact('taskType'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Task Type id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $taskType = $this->TaskTypes->get($id, contain: []);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $taskType = $this->TaskTypes->patchEntity($taskType, $this->getRequest()->getData());
            if ($this->TaskTypes->save($taskType)) {
                $this->Flash->success(__('The task type has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The task type could not be saved. Please, try again.'));
        }
        $this->set(compact('taskType'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Task Type id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $taskType = $this->TaskTypes->get($id);
        if ($this->TaskTypes->delete($taskType)) {
            $this->Flash->success(__('The task type has been deleted.'));
        } else {
            $this->Flash->error(__('The task type could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
