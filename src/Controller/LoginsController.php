<?php
declare(strict_types=1);

namespace App\Controller;

use App\Strings;

/**
 * Logins Controller
 *
 * @property \App\Model\Table\LoginsTable $Logins
 * @method \App\Model\Entity\Login[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class LoginsController extends AppController
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
            $conditions = ['Logins.customer_id' => $this->customer_id];
        }

        // search
        $search = $this->getRequest()->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'Logins.login ILIKE' => '%' . trim($search) . '%',
                ],
            ];
        }

        $this->paginate = [
            'order' => [
                'id' => 'DESC',
            ],
        ];
        $logins = $this->paginate($this->Logins->find(
            'all',
            contain: [
                'Customers',
            ],
            conditions: $conditions
        ));

        $this->set(compact('logins'));
    }

    /**
     * View method
     *
     * @param string|null $id Login id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $login = $this->Logins->get($id, contain: [
            'Customers',
            'Creators',
            'Modifiers',
        ]);

        $this->set(compact('login'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $login = $this->Logins->newEmptyEntity();

        if (isset($this->customer_id)) {
            $login->customer_id = $this->customer_id;
        }

        if ($this->getRequest()->is('post')) {
            $login = $this->Logins->patchEntity($login, $this->getRequest()->getData());
            if ($this->Logins->save($login)) {
                $this->Flash->success(__('The login has been saved.'));

                return $this->afterAddRedirect(['action' => 'view', $login->id]);
            }
            $this->Flash->error(__('The login could not be saved. Please, try again.'));
        }
        $customers = $this->Logins->Customers->find('list', order: [
            'company',
            'last_name',
            'first_name',
        ]);

        $new_login = '';
        if (isset($this->customer_id)) {
            $customers->where(['Customers.id' => $this->customer_id]);

            // START find free login
            $customer = $this->Logins->Customers->get($this->customer_id);
            $new_login = strtolower(Strings::removeAccents($customer->last_name . '.' . $customer->first_name));

            $i = 1;
            $test_login = $new_login;
            while ($this->Logins->exists(['login' => $test_login])) {
                $i++;
                $test_login = $new_login . '.' . $i;
            }
            $new_login = $test_login;
            unset($test_login);
            unset($i);
            // END find free login
        }

        $this->set(compact('login', 'customers'));

        // new available login
        $this->set('new_login', $new_login);

        // generate new password
        $this->set('new_password', Strings::generatePassword(8));
    }

    /**
     * Edit method
     *
     * @param string|null $id Login id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $login = $this->Logins->get($id, contain: []);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            // change password if is set new
            if (strlen($this->getRequest()->getData()['new_password']) > 0) {
                $login->password = $this->getRequest()->getData()['new_password'];
            }

            $login = $this->Logins->patchEntity($login, $this->getRequest()->getData());
            if ($this->Logins->save($login)) {
                $this->Flash->success(__('The login has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The login could not be saved. Please, try again.'));
        }
        $customers = $this->Logins->Customers->find('list', order: [
            'company',
            'last_name',
            'first_name',
        ]);

        if (isset($this->customer_id)) {
            $customers->where(['Customers.id' => $this->customer_id]);
        }

        $this->set(compact('login', 'customers'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Login id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $login = $this->Logins->get($id);
        if ($this->Logins->delete($login)) {
            $this->Flash->success(__('The login has been deleted.'));
        } else {
            $this->Flash->error(__('The login could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }
}
