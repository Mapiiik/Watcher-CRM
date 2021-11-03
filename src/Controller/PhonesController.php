<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Phones Controller
 *
 * @property \App\Model\Table\PhonesTable $Phones
 * @method \App\Model\Entity\Phone[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class PhonesController extends AppController
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
            $conditions = ['Phones.customer_id' => $customer_id];
        }

        $this->paginate = [
            'contain' => ['Customers'],
            'conditions' => $conditions,
        ];
        $phones = $this->paginate($this->Phones);

        $this->set(compact('phones'));
    }

    /**
     * View method
     *
     * @param string|null $id Phone id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $phone = $this->Phones->get($id, [
            'contain' => ['Customers'],
        ]);

        $this->set(compact('phone'));
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

        $phone = $this->Phones->newEmptyEntity();

        if (isset($customer_id)) {
            $phone = $this->Phones->patchEntity($phone, ['customer_id' => $customer_id]);
        }

        if ($this->request->is('post')) {
            $phone = $this->Phones->patchEntity($phone, $this->request->getData());
            if ($this->Phones->save($phone)) {
                $this->Flash->success(__('The phone has been saved.'));

                if (isset($customer_id)) {
                    return $this->redirect(['controller' => 'Customers', 'action' => 'view', $customer_id]);
                }

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The phone could not be saved. Please, try again.'));
        }
        $customers = $this->Phones->Customers->find('list', ['order' => ['company', 'first_name', 'last_name']]);

        if (isset($customer_id)) {
            $customers->where(['id' => $customer_id]);
        }

        $this->set(compact('phone', 'customers'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Phone id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $customer_id = $this->request->getParam('customer_id');
        $this->set('customer_id', $customer_id);

        $phone = $this->Phones->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $phone = $this->Phones->patchEntity($phone, $this->request->getData());
            if ($this->Phones->save($phone)) {
                $this->Flash->success(__('The phone has been saved.'));

                if (isset($customer_id)) {
                    return $this->redirect(['controller' => 'Customers', 'action' => 'view', $customer_id]);
                }

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The phone could not be saved. Please, try again.'));
        }
        $customers = $this->Phones->Customers->find('list', ['order' => ['company', 'first_name', 'last_name']]);

        if (isset($customer_id)) {
            $customers->where(['id' => $customer_id]);
        }

        $this->set(compact('phone', 'customers'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Phone id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $customer_id = $this->request->getParam('customer_id');

        $this->request->allowMethod(['post', 'delete']);
        $phone = $this->Phones->get($id);
        if ($this->Phones->delete($phone)) {
            $this->Flash->success(__('The phone has been deleted.'));
        } else {
            $this->Flash->error(__('The phone could not be deleted. Please, try again.'));
        }

        if (isset($customer_id)) {
            return $this->redirect(['controller' => 'Customers', 'action' => 'view', $customer_id]);
        }

        return $this->redirect(['action' => 'index']);
    }
}
