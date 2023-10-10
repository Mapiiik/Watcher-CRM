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
        // filter
        $conditions = [];
        if (isset($this->customer_id)) {
            $conditions = ['Phones.customer_id' => $this->customer_id];
        }

        // search
        $search = $this->getRequest()->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'Phones.phone ILIKE' => '%' . trim($search) . '%',
                ],
            ];
        }

        $this->paginate = [
            'order' => [
                'id' => 'DESC',
            ],
        ];
        $phones = $this->paginate($this->Phones->find(
            'all',
            contain: [
                'Customers',
            ],
            conditions: $conditions
        ));

        $this->set(compact('phones'));
    }

    /**
     * View method
     *
     * @param string|null $id Phone id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $phone = $this->Phones->get($id, contain: [
            'Customers',
            'Creators',
            'Modifiers',
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
        $phone = $this->Phones->newEmptyEntity();

        if (isset($this->customer_id)) {
            $phone->customer_id = $this->customer_id;
        }

        if ($this->getRequest()->is('post')) {
            $phone = $this->Phones->patchEntity($phone, $this->getRequest()->getData());
            if ($this->Phones->save($phone)) {
                $this->Flash->success(__('The phone has been saved.'));

                return $this->afterAddRedirect(['action' => 'view', $phone->id]);
            }
            $this->Flash->error(__('The phone could not be saved. Please, try again.'));
        }
        $customers = $this->Phones->Customers->find('list', order: [
            'company',
            'last_name',
            'first_name',
        ]);

        if (isset($this->customer_id)) {
            $customers->where(['id' => $this->customer_id]);
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
    public function edit(?string $id = null)
    {
        $phone = $this->Phones->get($id, contain: []);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $phone = $this->Phones->patchEntity($phone, $this->getRequest()->getData());
            if ($this->Phones->save($phone)) {
                $this->Flash->success(__('The phone has been saved.'));

                return $this->afterEditRedirect(['action' => 'view', $phone->id]);
            }
            $this->Flash->error(__('The phone could not be saved. Please, try again.'));
        }
        $customers = $this->Phones->Customers->find('list', order: [
            'company',
            'last_name',
            'first_name',
        ]);

        if (isset($this->customer_id)) {
            $customers->where(['Customers.id' => $this->customer_id]);
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
    public function delete(?string $id = null)
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $phone = $this->Phones->get($id);
        if ($this->Phones->delete($phone)) {
            $this->Flash->success(__('The phone has been deleted.'));
        } else {
            $this->Flash->error(__('The phone could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }
}
