<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * TaxRates Controller
 *
 * @property \App\Model\Table\TaxRatesTable $TaxRates
 * @method \App\Model\Entity\TaxRate[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class TaxRatesController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $taxRates = $this->paginate($this->TaxRates);

        $this->set(compact('taxRates'));
    }

    /**
     * View method
     *
     * @param string|null $id Tax Rate id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $taxRate = $this->TaxRates->get($id, [
            'contain' => ['Customers'],
        ]);

        $this->set(compact('taxRate'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $taxRate = $this->TaxRates->newEmptyEntity();
        if ($this->request->is('post')) {
            $taxRate = $this->TaxRates->patchEntity($taxRate, $this->request->getData());
            if ($this->TaxRates->save($taxRate)) {
                $this->Flash->success(__('The tax rate has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The tax rate could not be saved. Please, try again.'));
        }
        $this->set(compact('taxRate'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Tax Rate id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $taxRate = $this->TaxRates->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $taxRate = $this->TaxRates->patchEntity($taxRate, $this->request->getData());
            if ($this->TaxRates->save($taxRate)) {
                $this->Flash->success(__('The tax rate has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The tax rate could not be saved. Please, try again.'));
        }
        $this->set(compact('taxRate'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Tax Rate id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $taxRate = $this->TaxRates->get($id);
        if ($this->TaxRates->delete($taxRate)) {
            $this->Flash->success(__('The tax rate has been deleted.'));
        } else {
            $this->Flash->error(__('The tax rate could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
