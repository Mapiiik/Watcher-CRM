<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * ServiceTypes Controller
 *
 * @property \App\Model\Table\ServiceTypesTable $ServiceTypes
 * @method \App\Model\Entity\ServiceType[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ServiceTypesController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $serviceTypes = $this->paginate($this->ServiceTypes);

        $this->set(compact('serviceTypes'));
    }

    /**
     * View method
     *
     * @param string|null $id Service Type id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $serviceType = $this->ServiceTypes->get($id, [
            'contain' => ['Contracts', 'Queues', 'Services'],
        ]);

        $this->set(compact('serviceType'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $serviceType = $this->ServiceTypes->newEmptyEntity();
        if ($this->request->is('post')) {
            $serviceType = $this->ServiceTypes->patchEntity($serviceType, $this->request->getData());
            if ($this->ServiceTypes->save($serviceType)) {
                $this->Flash->success(__('The service type has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The service type could not be saved. Please, try again.'));
        }
        $this->set(compact('serviceType'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Service Type id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $serviceType = $this->ServiceTypes->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $serviceType = $this->ServiceTypes->patchEntity($serviceType, $this->request->getData());
            if ($this->ServiceTypes->save($serviceType)) {
                $this->Flash->success(__('The service type has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The service type could not be saved. Please, try again.'));
        }
        $this->set(compact('serviceType'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Service Type id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $serviceType = $this->ServiceTypes->get($id);
        if ($this->ServiceTypes->delete($serviceType)) {
            $this->Flash->success(__('The service type has been deleted.'));
        } else {
            $this->Flash->error(__('The service type could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
