<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;

/**
 * ConnectionProfiles Controller
 *
 * @property \App\Model\Table\ConnectionProfilesTable $ConnectionProfiles
 */
class ConnectionProfilesController extends AppController
{
    /**
     * Index method
     *
     * @return void Renders view
     */
    public function index(): void
    {
        // filter
        $conditions = [];

        // search
        $search = $this->getRequest()->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'ConnectionProfiles.radius_group ILIKE' => '%' . trim((string)$search) . '%',
                    'ConnectionProfiles.name ILIKE' => '%' . trim((string)$search) . '%',
                ],
            ];
        }

        $this->paginate = [
            'order' => [
                'name' => 'ASC',
            ],
        ];
        $connectionProfiles = $this->paginate($this->ConnectionProfiles->find(
            'all',
            contain: [],
            conditions: $conditions,
        ));

        $this->set(compact('connectionProfiles'));
    }

    /**
     * View method
     *
     * @param string|null $id Connection profile id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): void
    {
        $connectionProfile = $this->ConnectionProfiles->get($id, contain: [
            'Services' => ['ServiceTypes'],
            'Creators',
            'Modifiers',
        ]);

        $this->set(compact('connectionProfile'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(): ?Response
    {
        $connectionProfile = $this->ConnectionProfiles->newEmptyEntity();
        if ($this->getRequest()->is('post')) {
            $connectionProfile = $this->ConnectionProfiles->patchEntity(
                $connectionProfile,
                $this->getRequest()->getData(),
            );
            if ($this->ConnectionProfiles->save($connectionProfile)) {
                $this->Flash->success(__('The connection profile has been saved.'));

                return $this->afterAddRedirect(['action' => 'view', $connectionProfile->id]);
            }
            $this->Flash->error(__('The connection profile could not be saved. Please, try again.'));
        }
        $this->set(compact('connectionProfile'));

        return null;
    }

    /**
     * Edit method
     *
     * @param string|null $id Connection profile id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        $connectionProfile = $this->ConnectionProfiles->get($id, contain: []);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $connectionProfile = $this->ConnectionProfiles->patchEntity(
                $connectionProfile,
                $this->getRequest()->getData(),
            );
            if ($this->ConnectionProfiles->save($connectionProfile)) {
                $this->Flash->success(__('The connection profile has been saved.'));

                return $this->afterEditRedirect(['action' => 'view', $connectionProfile->id]);
            }
            $this->Flash->error(__('The connection profile could not be saved. Please, try again.'));
        }
        $this->set(compact('connectionProfile'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Connection profile id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $connectionProfile = $this->ConnectionProfiles->get($id);
        if ($this->ConnectionProfiles->delete($connectionProfile)) {
            $this->Flash->success(__('The connection profile has been deleted.'));
        } else {
            $this->flashValidationErrors($connectionProfile->getErrors());
            $this->Flash->error(__('The connection profile could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }
}
