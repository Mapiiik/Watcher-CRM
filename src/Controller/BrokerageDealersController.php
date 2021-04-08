<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * BrokerageDealers Controller
 *
 * @property \App\Model\Table\BrokerageDealersTable $BrokerageDealers
 * @method \App\Model\Entity\BrokerageDealer[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class BrokerageDealersController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $this->paginate = [
            'contain' => ['Dealers', 'Brokerages'],
        ];
        $brokerageDealers = $this->paginate($this->BrokerageDealers);

        $this->set(compact('brokerageDealers'));
    }

    /**
     * View method
     *
     * @param string|null $id Brokerage Dealer id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $brokerageDealer = $this->BrokerageDealers->get($id, [
            'contain' => ['Dealers', 'Brokerages'],
        ]);

        $this->set(compact('brokerageDealer'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $brokerageDealer = $this->BrokerageDealers->newEmptyEntity();
        if ($this->request->is('post')) {
            $brokerageDealer = $this->BrokerageDealers->patchEntity($brokerageDealer, $this->request->getData());
            if ($this->BrokerageDealers->save($brokerageDealer)) {
                $this->Flash->success(__('The brokerage dealer has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The brokerage dealer could not be saved. Please, try again.'));
        }
        $dealers = $this->BrokerageDealers->Dealers->find('list', ['limit' => 200]);
        $brokerages = $this->BrokerageDealers->Brokerages->find('list', ['limit' => 200]);
        $this->set(compact('brokerageDealer', 'dealers', 'brokerages'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Brokerage Dealer id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $brokerageDealer = $this->BrokerageDealers->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $brokerageDealer = $this->BrokerageDealers->patchEntity($brokerageDealer, $this->request->getData());
            if ($this->BrokerageDealers->save($brokerageDealer)) {
                $this->Flash->success(__('The brokerage dealer has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The brokerage dealer could not be saved. Please, try again.'));
        }
        $dealers = $this->BrokerageDealers->Dealers->find('list', ['limit' => 200]);
        $brokerages = $this->BrokerageDealers->Brokerages->find('list', ['limit' => 200]);
        $this->set(compact('brokerageDealer', 'dealers', 'brokerages'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Brokerage Dealer id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $brokerageDealer = $this->BrokerageDealers->get($id);
        if ($this->BrokerageDealers->delete($brokerageDealer)) {
            $this->Flash->success(__('The brokerage dealer has been deleted.'));
        } else {
            $this->Flash->error(__('The brokerage dealer could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
