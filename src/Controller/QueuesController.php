<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Queues Controller
 *
 * @property \App\Model\Table\QueuesTable $Queues
 * @method \App\Model\Entity\Queue[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class QueuesController extends AppController
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
                    'Queues.name ILIKE' => '%' . trim($search) . '%',
                    'Queues.caption ILIKE' => '%' . trim($search) . '%',
                ],
            ];
        }

        $this->paginate = [
            'order' => [
                'name' => 'ASC',
            ],
        ];
        $queues = $this->paginate($this->Queues->find(
            'all',
            contain: [],
            conditions: $conditions
        ));

        $this->set(compact('queues'));
    }

    /**
     * View method
     *
     * @param string|null $id Queue id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $queue = $this->Queues->get($id, contain: [
            'Services' => ['ServiceTypes'],
            'Creators',
            'Modifiers',
        ]);

        $this->set(compact('queue'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $queue = $this->Queues->newEmptyEntity();
        if ($this->getRequest()->is('post')) {
            $queue = $this->Queues->patchEntity($queue, $this->getRequest()->getData());
            if ($this->Queues->save($queue)) {
                $this->Flash->success(__('The queue has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The queue could not be saved. Please, try again.'));
        }
        $this->set(compact('queue'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Queue id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $queue = $this->Queues->get($id, contain: []);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $queue = $this->Queues->patchEntity($queue, $this->getRequest()->getData());
            if ($this->Queues->save($queue)) {
                $this->Flash->success(__('The queue has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The queue could not be saved. Please, try again.'));
        }
        $this->set(compact('queue'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Queue id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $queue = $this->Queues->get($id);
        if ($this->Queues->delete($queue)) {
            $this->Flash->success(__('The queue has been deleted.'));
        } else {
            $this->Flash->error(__('The queue could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
