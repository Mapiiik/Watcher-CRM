<?php
declare(strict_types=1);

namespace Radius\Controller;

/**
 * Nas Controller
 *
 * @property \Radius\Model\Table\NasTable $Nas
 * @method \Radius\Model\Entity\Nas[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class NasController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $nases = $this->paginate($this->Nas);

        $this->set(compact('nases'));
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
        $nas = $this->Nas->get($id, [
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
        $nas = $this->Nas->newEmptyEntity();
        if ($this->request->is('post')) {
            $nas = $this->Nas->patchEntity($nas, $this->request->getData());
            if ($this->Nas->save($nas)) {
                $this->Flash->success(__d('radius', 'The RADIUS NAS has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__d('radius', 'The RADIUS NAS could not be saved. Please, try again.'));
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
        $nas = $this->Nas->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $nas = $this->Nas->patchEntity($nas, $this->request->getData());
            if ($this->Nas->save($nas)) {
                $this->Flash->success(__d('radius', 'The RADIUS NAS has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__d('radius', 'The RADIUS NAS could not be saved. Please, try again.'));
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
        $nas = $this->Nas->get($id);
        if ($this->Nas->delete($nas)) {
            $this->Flash->success(__d('radius', 'The RADIUS NAS has been deleted.'));
        } else {
            $this->Flash->error(__d('radius', 'The RADIUS NAS could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
