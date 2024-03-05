<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * CustomerMessages Controller
 *
 * @property \App\Model\Table\CustomerMessagesTable $CustomerMessages
 */
class CustomerMessagesController extends AppController
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
        if (isset($this->customer_id)) {
            $conditions = ['CustomerMessages.customer_id' => $this->customer_id];
        }

        // search
        $search = $this->getRequest()->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'CustomerMessages.subject ILIKE' => '%' . trim($search) . '%',
                    'CustomerMessages.body ILIKE' => '%' . trim($search) . '%',
                ],
            ];
        }

        $query = $this->CustomerMessages->find()
            ->contain([
                'Customers',
            ])
            ->where($conditions);

        $customerMessages = $this->paginate($query);

        $this->set(compact('customerMessages'));
    }

    /**
     * View method
     *
     * @param string|null $id Customer Message id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $customerMessage = $this->CustomerMessages->get($id, contain: [
            'Customers',
            'Creators',
            'Modifiers',
        ]);
        $this->set(compact('customerMessage'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $customerMessage = $this->CustomerMessages->newEmptyEntity();
        if ($this->request->is('post')) {
            $customerMessage = $this->CustomerMessages->patchEntity($customerMessage, $this->request->getData());
            if ($this->CustomerMessages->save($customerMessage)) {
                $this->Flash->success(__('The customer message has been saved.'));

                return $this->afterAddRedirect(['action' => 'view', $customerMessage->id]);
            }
            debug($customerMessage->getErrors());
            $this->Flash->error(__('The customer message could not be saved. Please, try again.'));
        }
        $customers = $this->CustomerMessages->Customers->find('list', order: [
            'company',
            'last_name',
            'first_name',
        ])->all();
        $this->set(compact('customerMessage', 'customers'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Customer Message id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $customerMessage = $this->CustomerMessages->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $customerMessage = $this->CustomerMessages->patchEntity($customerMessage, $this->request->getData());
            if ($this->CustomerMessages->save($customerMessage)) {
                $this->Flash->success(__('The customer message has been saved.'));

                return $this->afterEditRedirect(['action' => 'view', $customerMessage->id]);
            }
            $this->Flash->error(__('The customer message could not be saved. Please, try again.'));
        }
        $customers = $this->CustomerMessages->Customers->find('list', order: [
            'company',
            'last_name',
            'first_name',
        ])->all();
        $this->set(compact('customerMessage', 'customers'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Customer Message id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $customerMessage = $this->CustomerMessages->get($id);
        if ($this->CustomerMessages->delete($customerMessage)) {
            $this->Flash->success(__('The customer message has been deleted.'));
        } else {
            $this->flashValidationErrors($customerMessage->getErrors());
            $this->Flash->error(__('The customer message could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
