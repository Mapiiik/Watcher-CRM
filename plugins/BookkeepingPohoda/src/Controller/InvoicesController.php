<?php
declare(strict_types=1);

namespace BookkeepingPohoda\Controller;

use BookkeepingPohoda\Controller\AppController;

/**
 * Invoices Controller
 *
 * @property \BookkeepingPohoda\Model\Table\InvoicesTable $Invoices
 * @method \BookkeepingPohoda\Model\Entity\Invoice[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class InvoicesController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $this->paginate = [
            'contain' => ['Customers'],
        ];
        $invoices = $this->paginate($this->Invoices);

        $this->set(compact('invoices'));
    }

    /**
     * View method
     *
     * @param string|null $id Invoice id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $invoice = $this->Invoices->get($id, [
            'contain' => ['Customers'],
        ]);

        $this->set(compact('invoice'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $invoice = $this->Invoices->newEmptyEntity();
        if ($this->request->is('post')) {
            $invoice = $this->Invoices->patchEntity($invoice, $this->request->getData());
            if ($this->Invoices->save($invoice)) {
                $this->Flash->success(__('The invoice has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The invoice could not be saved. Please, try again.'));
        }
        $customers = $this->Invoices->Customers->find('list', ['limit' => 200]);
        $this->set(compact('invoice', 'customers'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Invoice id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $invoice = $this->Invoices->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $invoice = $this->Invoices->patchEntity($invoice, $this->request->getData());
            if ($this->Invoices->save($invoice)) {
                $this->Flash->success(__('The invoice has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The invoice could not be saved. Please, try again.'));
        }
        $customers = $this->Invoices->Customers->find('list', ['limit' => 200]);
        $this->set(compact('invoice', 'customers'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Invoice id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $invoice = $this->Invoices->get($id);
        if ($this->Invoices->delete($invoice)) {
            $this->Flash->success(__('The invoice has been deleted.'));
        } else {
            $this->Flash->error(__('The invoice could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
    
    /**
     * GenerateInvoices method
     *
     * @return \Cake\Http\Response|null|void Renders generateInvoices
     */
    public function generate()
    {
        $this->Customers = $this->getTableLocator()->get('Customers');
        
        $query = $this->request->getQuery();
        
        $taxes = $this->Customers->Taxes->find('list', ['order' => 'name']);

        if (isset($query['tax_rate_id'])) {
            $tax_rate_id = $query['tax_rate_id'];
        }

        if ($this->request->getParam('_ext') === 'dbf') {
            debug($this->request->getData());
/*            switch ($type) {
            case 'gdpr-new':
            case 'gdpr-change':
                break;

            default:
                $this->Flash->error(__('Invalid type of document requested.'));
                return $this->redirect(['action' => 'print', $id, '?' => $query]);
            }
*/
                $this->Flash->error(__('Invalid type of document requested.'));
                return $this->redirect(['action' => 'generate', '?' => $this->request->getData()]);
/*            
            // filter and split billings
            $contract->individual_billings = [];
            $contract->standard_billings = []; 

            foreach ($contract->billings as $billing) {
                // skip non active items
                if (!$billing->active) {
                    continue;
                }
                if ($billing->has('billing_from') && $billing->billing_from > $contract->valid_from) {
                    continue;
                }
                if ($billing->has('billing_until') && $billing->billing_until < $contract->valid_from) {
                    continue;
                }

                // split by individual/standard price
                if ($billing->has('price')) {
                    $contract->individual_billings[] = $billing;
                } else {
                    $contract->standard_billings[] = $billing;
                }
            }
*/
        }
        
        $this->set(compact('taxes'));
    }    
}
