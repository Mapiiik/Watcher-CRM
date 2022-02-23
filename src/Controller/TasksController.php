<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Tasks Controller
 *
 * @property \App\Model\Table\TasksTable $Tasks
 * @method \App\Model\Entity\Task[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class TasksController extends AppController
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
            $conditions = ['Tasks.customer_id' => $customer_id];
        }

        $this->paginate = [
            'contain' => ['TaskTypes', 'Customers', 'Dealers', 'TaskStates', 'Routers'],
            'conditions' => $conditions,
            'order' => [
                'Tasks.id' => 'desc',
            ],
        ];

        $tasks = $this->paginate($this->Tasks);

        $this->set(compact('tasks'));
    }

    /**
     * View method
     *
     * @param string|null $id Task id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $task = $this->Tasks->get($id, [
            'contain' => ['TaskTypes', 'Customers', 'Dealers', 'TaskStates', 'Routers'],
        ]);

        $this->set(compact('task'));
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

        $task = $this->Tasks->newEmptyEntity();

        if (isset($customer_id)) {
            $task->customer_id = $customer_id;
        }

        if ($this->request->is('post')) {
            $task = $this->Tasks->patchEntity($task, $this->request->getData());
            if ($this->Tasks->save($task)) {
                $this->Flash->success(__('The task has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The task could not be saved. Please, try again.'));
        }
        $taskTypes = $this->Tasks->TaskTypes->find('list', ['order' => 'name']);
        $customers = $this->Tasks->Customers->find('list', ['order' => ['company', 'first_name', 'last_name']]);
        $dealers = $this->Tasks->Dealers->find('list', ['order' => ['company', 'first_name', 'last_name']]);
        $taskStates = $this->Tasks->TaskStates->find('list', ['order' => 'name']);
        $routers = $this->Tasks->Routers->find('list', ['order' => 'name']);

        if (isset($customer_id)) {
            $customers->where(['id' => $customer_id]);
        }

        $this->set(compact('task', 'taskTypes', 'customers', 'dealers', 'taskStates', 'routers'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Task id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $customer_id = $this->request->getParam('customer_id');
        $this->set('customer_id', $customer_id);

        $task = $this->Tasks->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $task = $this->Tasks->patchEntity($task, $this->request->getData());
            if ($this->Tasks->save($task)) {
                $this->Flash->success(__('The task has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The task could not be saved. Please, try again.'));
        }
        $taskTypes = $this->Tasks->TaskTypes->find('list', ['order' => 'name']);
        $customers = $this->Tasks->Customers->find('list', ['order' => ['company', 'first_name', 'last_name']]);
        $dealers = $this->Tasks->Dealers->find('list', ['order' => ['company', 'first_name', 'last_name']]);
        $taskStates = $this->Tasks->TaskStates->find('list', ['order' => 'name']);
        $routers = $this->Tasks->Routers->find('list', ['order' => 'name']);

        if (isset($customer_id)) {
            $customers->where(['id' => $customer_id]);
        }

        $this->set(compact('task', 'taskTypes', 'customers', 'dealers', 'taskStates', 'routers'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Task id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $task = $this->Tasks->get($id);
        if ($this->Tasks->delete($task)) {
            $this->Flash->success(__('The task has been deleted.'));
        } else {
            $this->Flash->error(__('The task could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
