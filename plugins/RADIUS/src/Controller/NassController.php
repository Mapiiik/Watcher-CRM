<?php
declare(strict_types=1);

namespace RADIUS\Controller;

use RADIUS\Controller\AppController;

/**
 * Nass Controller
 *
 * @property \RADIUS\Model\Table\NassTable $Nass
 * @method \RADIUS\Model\Entity\Nas[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class NassController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $nass = $this->paginate($this->Nass);

        $this->set(compact('nass'));
    }

    /**
     * View method
     *
     * @param string|null $id Nas id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $nas = $this->Nass->get($id, [
            'contain' => [],
        ]);

        $this->set(compact('nas'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $nas = $this->Nass->newEmptyEntity();
        if ($this->request->is('post')) {
            $nas = $this->Nass->patchEntity($nas, $this->request->getData());
            if ($this->Nass->save($nas)) {
                $this->Flash->success(__('The nas has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The nas could not be saved. Please, try again.'));
        }
        $this->set(compact('nas'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Nas id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $nas = $this->Nass->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $nas = $this->Nass->patchEntity($nas, $this->request->getData());
            if ($this->Nass->save($nas)) {
                $this->Flash->success(__('The nas has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The nas could not be saved. Please, try again.'));
        }
        $this->set(compact('nas'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Nas id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $nas = $this->Nass->get($id);
        if ($this->Nass->delete($nas)) {
            $this->Flash->success(__('The nas has been deleted.'));
        } else {
            $this->Flash->error(__('The nas could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
