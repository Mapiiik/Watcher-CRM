<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Labels Controller
 *
 * @property \App\Model\Table\LabelsTable $Labels
 * @method \App\Model\Entity\Label[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class LabelsController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $this->paginate = [
            'contain' => ['CustomerLabels'],
        ];
        $labels = $this->paginate($this->Labels);

        $this->set(compact('labels'));
    }

    /**
     * View method
     *
     * @param string|null $id Label id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $label = $this->Labels->get($id, [
            'contain' => [
                'CustomerLabels' => ['Customers'],
            ],
        ]);

        $this->set(compact('label'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $label = $this->Labels->newEmptyEntity();
        if ($this->getRequest()->is('post')) {
            $label = $this->Labels->patchEntity($label, $this->getRequest()->getData());
            if ($this->Labels->save($label)) {
                $this->Flash->success(__('The label has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The label could not be saved. Please, try again.'));
        }
        $this->set(compact('label'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Label id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $label = $this->Labels->get($id, [
            'contain' => [],
        ]);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $label = $this->Labels->patchEntity($label, $this->getRequest()->getData());
            if ($this->Labels->save($label)) {
                $this->Flash->success(__('The label has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The label could not be saved. Please, try again.'));
        }
        $this->set(compact('label'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Label id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $label = $this->Labels->get($id);
        if ($this->Labels->delete($label)) {
            $this->Flash->success(__('The label has been deleted.'));
        } else {
            $this->Flash->error(__('The label could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
