<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Brokerages Controller
 *
 * @property \App\Model\Table\BrokeragesTable $Brokerages
 * @method \App\Model\Entity\Brokerage[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class BrokeragesController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $brokerages = $this->paginate($this->Brokerages);

        $this->set(compact('brokerages'));
    }

    /**
     * View method
     *
     * @param string|null $id Brokerage id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $brokerage = $this->Brokerages->get($id, [
            'contain' => ['BrokerageDealers', 'Contracts'],
        ]);

        $this->set(compact('brokerage'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $brokerage = $this->Brokerages->newEmptyEntity();
        if ($this->request->is('post')) {
            $brokerage = $this->Brokerages->patchEntity($brokerage, $this->request->getData());
            if ($this->Brokerages->save($brokerage)) {
                $this->Flash->success(__('The brokerage has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The brokerage could not be saved. Please, try again.'));
        }
        $this->set(compact('brokerage'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Brokerage id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $brokerage = $this->Brokerages->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $brokerage = $this->Brokerages->patchEntity($brokerage, $this->request->getData());
            if ($this->Brokerages->save($brokerage)) {
                $this->Flash->success(__('The brokerage has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The brokerage could not be saved. Please, try again.'));
        }
        $this->set(compact('brokerage'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Brokerage id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $brokerage = $this->Brokerages->get($id);
        if ($this->Brokerages->delete($brokerage)) {
            $this->Flash->success(__('The brokerage has been deleted.'));
        } else {
            $this->Flash->error(__('The brokerage could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
