<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;

/**
 * Emails Controller
 *
 * @property \App\Model\Table\EmailsTable $Emails
 */
class EmailsController extends AppController
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
        if ($this->customer_id !== null) {
            $conditions = ['Emails.customer_id' => $this->customer_id];
        }

        // search
        $search = $this->getRequest()->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'Emails.email ILIKE' => '%' . trim((string)$search) . '%',
                ],
            ];
        }

        $this->paginate = [
            'order' => [
                'id' => 'DESC',
            ],
        ];
        $emails = $this->paginate($this->Emails->find(
            'all',
            contain: [
                'Customers',
            ],
            conditions: $conditions,
        ));

        $this->set(compact('emails'));
    }

    /**
     * View method
     *
     * @param string|null $id Email id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): void
    {
        $email = $this->Emails->get($id, contain: [
            'Customers',
            'Creators',
            'Modifiers',
        ]);

        $this->set(compact('email'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(): ?Response
    {
        $email = $this->Emails->newEmptyEntity();

        if ($this->customer_id !== null) {
            $email->customer_id = $this->customer_id;
        }

        if ($this->getRequest()->is('post')) {
            $email = $this->Emails->patchEntity(
                $email,
                $this->dataWithAdditionalParameters($this->Emails, $this->getRequest()->getData()),
            );
            if ($this->Emails->save($email)) {
                $this->Flash->success(__('The email has been saved.'));

                return $this->afterAddRedirect(['action' => 'view', $email->id]);
            }
            $this->Flash->error(__('The email could not be saved. Please, try again.'));
        }
        $customers = $this->Emails->Customers->find('list', order: [
            'company',
            'last_name',
            'first_name',
        ]);

        if ($this->customer_id !== null) {
            $customers->where(['Customers.id' => $this->customer_id]);
        }

        $this->set(compact('email', 'customers'));

        return null;
    }

    /**
     * Edit method
     *
     * @param string|null $id Email id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        $email = $this->Emails->get($id, contain: []);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $email = $this->Emails->patchEntity($email, $this->getRequest()->getData());
            if ($this->Emails->save($email)) {
                $this->Flash->success(__('The email has been saved.'));

                return $this->afterEditRedirect(['action' => 'view', $email->id]);
            }
            $this->Flash->error(__('The email could not be saved. Please, try again.'));
        }
        $customers = $this->Emails->Customers->find('list', order: [
            'company',
            'last_name',
            'first_name',
        ]);

        if ($this->customer_id !== null) {
            $customers->where(['Customers.id' => $this->customer_id]);
        }

        $this->set(compact('email', 'customers'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Email id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $email = $this->Emails->get($id);
        if ($this->Emails->delete($email)) {
            $this->Flash->success(__('The email has been deleted.'));
        } else {
            $this->flashValidationErrors($email->getErrors());
            $this->Flash->error(__('The email could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }
}
