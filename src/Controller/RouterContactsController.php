<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * RouterContacts Controller
 *
 * @property \App\Model\Table\RouterContactsTable $RouterContacts
 * @method \App\Model\Entity\RouterContact[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class RouterContactsController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $this->paginate = [
            'contain' => ['Routers', 'Customers'],
        ];
        $routerContacts = $this->paginate($this->RouterContacts);

        $this->set(compact('routerContacts'));
    }

    /**
     * View method
     *
     * @param string|null $id Router Contact id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $routerContact = $this->RouterContacts->get($id, [
            'contain' => ['Routers', 'Customers'],
        ]);

        $this->set(compact('routerContact'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $routerContact = $this->RouterContacts->newEmptyEntity();
        if ($this->request->is('post')) {
            $routerContact = $this->RouterContacts->patchEntity($routerContact, $this->request->getData());
            if ($this->RouterContacts->save($routerContact)) {
                $this->Flash->success(__('The router contact has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The router contact could not be saved. Please, try again.'));
        }
        $routers = $this->RouterContacts->Routers->find('list', ['order' => 'name']);
        $customers = $this->RouterContacts->Customers->find('list', ['order' => ['company', 'first_name', 'last_name']]);
        $this->set(compact('routerContact', 'routers', 'customers'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Router Contact id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $routerContact = $this->RouterContacts->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $routerContact = $this->RouterContacts->patchEntity($routerContact, $this->request->getData());
            if ($this->RouterContacts->save($routerContact)) {
                $this->Flash->success(__('The router contact has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The router contact could not be saved. Please, try again.'));
        }
        $routers = $this->RouterContacts->Routers->find('list', ['order' => 'name']);
        $customers = $this->RouterContacts->Customers->find('list', ['order' => ['company', 'first_name', 'last_name']]);
        $this->set(compact('routerContact', 'routers', 'customers'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Router Contact id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $routerContact = $this->RouterContacts->get($id);
        if ($this->RouterContacts->delete($routerContact)) {
            $this->Flash->success(__('The router contact has been deleted.'));
        } else {
            $this->Flash->error(__('The router contact could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
