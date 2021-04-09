<?php
declare(strict_types=1);

namespace App\Controller;

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
        $this->paginate = [
            'contain' => ['Customers'],
        ];
        $logins = $this->paginate($this->Logins);

        $this->set(compact('logins'));
    }

    /**
     * View method
     *
     * @param string|null $id Login id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $login = $this->Logins->get($id, [
            'contain' => ['Customers'],
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
        if ($this->request->is('post')) {
            $login = $this->Logins->patchEntity($login, $this->request->getData());
            if ($this->Logins->save($login)) {
                $this->Flash->success(__('The login has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The login could not be saved. Please, try again.'));
        }
        $customers = $this->Logins->Customers->find('list', ['order' => ['company', 'first_name', 'last_name']]);
        $this->set(compact('login', 'customers'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Login id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $login = $this->Logins->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $login = $this->Logins->patchEntity($login, $this->request->getData());
            if ($this->Logins->save($login)) {
                $this->Flash->success(__('The login has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The login could not be saved. Please, try again.'));
        }
        $customers = $this->Logins->Customers->find('list', ['order' => ['company', 'first_name', 'last_name']]);
        $this->set(compact('login', 'customers'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Login id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $login = $this->Logins->get($id);
        if ($this->Logins->delete($login)) {
            $this->Flash->success(__('The login has been deleted.'));
        } else {
            $this->Flash->error(__('The login could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
