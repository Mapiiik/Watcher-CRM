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
        $taskStates = $this->paginate($this->TaskStates);

        $this->set(compact('taskStates'));
    }

    /**
     * View method
     *
     * @param string|null $id Task State id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $taskState = $this->TaskStates->get($id, [
            'contain' => ['Tasks'],
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
        if ($this->request->is('post')) {
            $taskState = $this->TaskStates->patchEntity($taskState, $this->request->getData());
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
    public function edit($id = null)
    {
        $taskState = $this->TaskStates->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $taskState = $this->TaskStates->patchEntity($taskState, $this->request->getData());
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
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $taskState = $this->TaskStates->get($id);
        if ($this->TaskStates->delete($taskState)) {
            $this->Flash->success(__('The task state has been deleted.'));
        } else {
            $this->Flash->error(__('The task state could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
