<?php
declare(strict_types=1);

namespace Radius\Controller;

use Cake\Http\Response;

/**
 * Nas Controller
 *
 * @property \Radius\Model\Table\NasTable $Nas
 */
class NasController extends AppController
{
    /**
     * Index method
     *
     * @return void Renders view
     */
    public function index(): void
    {
        $nases = $this->paginate($this->Nas->find(
            'all',
            contain: [],
            conditions: [],
        ));

        $this->set(compact('nases'));
    }

    /**
     * View method
     *
     * @param string|null $id Nas id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): void
    {
        $nas = $this->Nas->get($id, contain: []);

        $this->set(compact('nas'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(): ?Response
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

        return null;
    }

    /**
     * Edit method
     *
     * @param string|null $id Nas id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
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

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Nas id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $nas = $this->Nas->get($id);
        if ($this->Nas->delete($nas)) {
            $this->Flash->success(__d('radius', 'The RADIUS NAS has been deleted.'));
        } else {
            $this->flashValidationErrors($nas->getErrors());
            $this->Flash->error(__d('radius', 'The RADIUS NAS could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }
}
