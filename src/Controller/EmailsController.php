<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Emails Controller
 *
 * @property \App\Model\Table\EmailsTable $Emails
 * @method \App\Model\Entity\Email[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class EmailsController extends AppController
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
            $conditions = ['Emails.customer_id' => $this->customer_id];
        }

        // search
        $search = $this->getRequest()->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'Emails.email ILIKE' => '%' . trim($search) . '%',
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
            conditions: $conditions
        ));

        $this->set(compact('emails'));
    }

    /**
     * View method
     *
     * @param string|null $id Email id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
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
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $email = $this->Emails->newEmptyEntity();

        if (isset($this->customer_id)) {
            $email->customer_id = $this->customer_id;
        }

        if ($this->getRequest()->is('post')) {
            $email = $this->Emails->patchEntity($email, $this->getRequest()->getData());
            if ($this->Emails->save($email)) {
                $this->Flash->success(__('The email has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The email could not be saved. Please, try again.'));
        }
        $customers = $this->Emails->Customers->find('list', order: [
            'company',
            'last_name',
            'first_name',
        ]);

        if (isset($this->customer_id)) {
            $customers->where(['Customers.id' => $this->customer_id]);
        }

        $this->set(compact('email', 'customers'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Email id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $email = $this->Emails->get($id, contain: []);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $email = $this->Emails->patchEntity($email, $this->getRequest()->getData());
            if ($this->Emails->save($email)) {
                $this->Flash->success(__('The email has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The email could not be saved. Please, try again.'));
        }
        $customers = $this->Emails->Customers->find('list', order: [
            'company',
            'last_name',
            'first_name',
        ]);

        if (isset($this->customer_id)) {
            $customers->where(['Customers.id' => $this->customer_id]);
        }

        $this->set(compact('email', 'customers'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Email id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $email = $this->Emails->get($id);
        if ($this->Emails->delete($email)) {
            $this->Flash->success(__('The email has been deleted.'));
        } else {
            $this->Flash->error(__('The email could not be deleted. Please, try again.'));
        }

        if (isset($this->customer_id)) {
            return $this->redirect(['controller' => 'Customers', 'action' => 'view', $this->customer_id]);
        }

        return $this->redirect(['action' => 'index']);
    }
}
