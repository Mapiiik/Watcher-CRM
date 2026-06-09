<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;

/**
 * Queues Controller
 *
 * @property \App\Model\Table\QueuesTable $Queues
 */
class QueuesController extends AppController
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
                    'Queues.name ILIKE' => '%' . trim((string)$search) . '%',
                    'Queues.caption ILIKE' => '%' . trim((string)$search) . '%',
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
            conditions: $conditions,
        ));

        $this->set(compact('queues'));
    }

    /**
     * View method
     *
     * @param string|null $id Queue id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): void
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
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(): ?Response
    {
        $queue = $this->Queues->newEmptyEntity();
        if ($this->getRequest()->is('post')) {
            $queue = $this->Queues->patchEntity($queue, $this->getRequest()->getData());
            if ($this->Queues->save($queue)) {
                $this->Flash->success(__('The queue has been saved.'));

                return $this->afterAddRedirect(['action' => 'view', $queue->id]);
            }
            $this->Flash->error(__('The queue could not be saved. Please, try again.'));
        }
        $this->set(compact('queue'));

        return null;
    }

    /**
     * Edit method
     *
     * @param string|null $id Queue id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        $queue = $this->Queues->get($id, contain: []);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $queue = $this->Queues->patchEntity($queue, $this->getRequest()->getData());
            if ($this->Queues->save($queue)) {
                $this->Flash->success(__('The queue has been saved.'));

                return $this->afterEditRedirect(['action' => 'view', $queue->id]);
            }
            $this->Flash->error(__('The queue could not be saved. Please, try again.'));
        }
        $this->set(compact('queue'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Queue id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $queue = $this->Queues->get($id);
        if ($this->Queues->delete($queue)) {
            $this->Flash->success(__('The queue has been deleted.'));
        } else {
            $this->flashValidationErrors($queue->getErrors());
            $this->Flash->error(__('The queue could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }
}
