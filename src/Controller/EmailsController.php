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
        $customer_id = $this->request->getParam('customer_id');
        $this->set('customer_id', $customer_id);

        $conditions = [];
        if (isset($customer_id)) {
            $conditions = ['Emails.customer_id' => $customer_id];
        }

        $this->paginate = [
            'contain' => ['Customers'],
            'conditions' => $conditions,
        ];
        $emails = $this->paginate($this->Emails);

        $this->set(compact('emails'));
    }

    /**
     * View method
     *
     * @param string|null $id Email id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $email = $this->Emails->get($id, [
            'contain' => ['Customers'],
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
        $customer_id = $this->request->getParam('customer_id');
        $this->set('customer_id', $customer_id);

        $email = $this->Emails->newEmptyEntity();

        if (isset($customer_id)) {
            $email->customer_id = $customer_id;
        }

        if ($this->request->is('post')) {
            $email = $this->Emails->patchEntity($email, $this->request->getData());
            if ($this->Emails->save($email)) {
                $this->Flash->success(__('The email has been saved.'));

                if (isset($customer_id)) {
                    return $this->redirect(['controller' => 'Customers', 'action' => 'view', $customer_id]);
                }

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The email could not be saved. Please, try again.'));
        }
        $customers = $this->Emails->Customers->find('list', ['order' => ['company', 'first_name', 'last_name']]);

        if (isset($customer_id)) {
            $customers->where(['id' => $customer_id]);
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
    public function edit($id = null)
    {
        $customer_id = $this->request->getParam('customer_id');
        $this->set('customer_id', $customer_id);

        $email = $this->Emails->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $email = $this->Emails->patchEntity($email, $this->request->getData());
            if ($this->Emails->save($email)) {
                $this->Flash->success(__('The email has been saved.'));

                if (isset($customer_id)) {
                    return $this->redirect(['controller' => 'Customers', 'action' => 'view', $customer_id]);
                }

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The email could not be saved. Please, try again.'));
        }
        $customers = $this->Emails->Customers->find('list', ['order' => ['company', 'first_name', 'last_name']]);

        if (isset($customer_id)) {
            $customers->where(['id' => $customer_id]);
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
    public function delete($id = null)
    {
        $customer_id = $this->request->getParam('customer_id');

        $this->request->allowMethod(['post', 'delete']);
        $email = $this->Emails->get($id);
        if ($this->Emails->delete($email)) {
            $this->Flash->success(__('The email has been deleted.'));
        } else {
            $this->Flash->error(__('The email could not be deleted. Please, try again.'));
        }

        if (isset($customer_id)) {
            return $this->redirect(['controller' => 'Customers', 'action' => 'view', $customer_id]);
        }

        return $this->redirect(['action' => 'index']);
    }
}
