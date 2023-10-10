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
        $nases = $this->paginate($this->Nas->find(
            'all',
            contain: [],
            conditions: []
        ));

        $this->set(compact('nases'));
    }

    /**
     * View method
     *
     * @param string|null $id Nas id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $nas = $this->Nas->get($id, contain: []);

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
        if ($this->getRequest()->is('post')) {
            $nas = $this->Nas->patchEntity($nas, $this->getRequest()->getData());
            if ($this->Nas->save($nas)) {
                $this->Flash->success(__d('radius', 'The RADIUS NAS has been saved.'));

                return $this->afterAddRedirect(['action' => 'view', $nas->id]);
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
    public function edit(?string $id = null)
    {
        $nas = $this->Nas->get($id, contain: []);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $nas = $this->Nas->patchEntity($nas, $this->getRequest()->getData());
            if ($this->Nas->save($nas)) {
                $this->Flash->success(__d('radius', 'The RADIUS NAS has been saved.'));

                return $this->afterEditRedirect(['action' => 'view', $nas->id]);
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
    public function delete(?string $id = null)
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $nas = $this->Nas->get($id);
        if ($this->Nas->delete($nas)) {
            $this->Flash->success(__d('radius', 'The RADIUS NAS has been deleted.'));
        } else {
            $this->Flash->error(__d('radius', 'The RADIUS NAS could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }
}
