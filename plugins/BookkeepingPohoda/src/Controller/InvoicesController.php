<?php
declare(strict_types=1);

namespace BookkeepingPohoda\Controller;

use BookkeepingPohoda\Controller\AppController;
use Cake\I18n\FrozenDate;
use Cake\ORM\Query;

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

        $taxes = $this->Customers->Taxes->find('list', ['order' => 'name']);
        
        $query = $this->request->getQuery();
        
        if ($this->request->is(['post'])) {
            $data = $this->request->getData();
            
            // DO THIS BETTER - REVERSE CHARGE
            if ($data['tax_rate_id'] == 5) {
                $reverse_charge = true;
                $innerfix = "8";
            } else {
                $reverse_charge = false;
                $innerfix = "9";
            }            
            
            $invoiced_month = new FrozenDate($this->request->getData('invoiced_month'));
            
            $customers = $this->Customers
                    ->find()
                    ->order('Customers.id')
                    ->where(['Customers.taxe_id' => $data['tax_rate_id']])
                    ->contain('Addresses')
                    ->contain('Contracts', function (Query $q) use ($invoiced_month) {
                        return $q
                                ->order('Contracts.id')
                                ->where([
                                    'OR' => [
                                        'Contracts.valid_from IS NULL',
                                        'Contracts.valid_from <=' => $invoiced_month->lastOfMonth(), //last day of month
                                    ],
                                    'OR' => [
                                        'Contracts.valid_until IS NULL',
                                        'Contracts.valid_until >=' => $invoiced_month->firstOfMonth(), //first day of month
                                    ],
                                ])
                                ->contain('Billings', function (Query $q) use ($invoiced_month) {
                                    return $q
                                            ->order('Billings.id')
                                            ->where([
                                                'Billings.active' => true,
                                                'OR' => [
                                                    'Billings.billing_from IS NULL',
                                                    'Billings.billing_from <=' => $invoiced_month->lastOfMonth(), //last day of month
                                                ],
                                                'OR' => [
                                                    'Billings.billing_until IS NULL',
                                                    'Billings.billing_until >=' => $invoiced_month->firstOfMonth(), //first day of month
                                                ],
                                            ])
                                            ->contain(['Services']);
                                });
                    });
            
            // verification data
            if ($data['csv_for_verification']->getSize() > 0) {
                $lines = explode(PHP_EOL, $data['csv_for_verification']->getStream()->getContents());
                $verification_data = [];
                
                foreach ($lines as $line) {
                    $parsed_line = explode(",", $line);
                    
                    if (!is_numeric(trim($parsed_line[0]))) continue;
                    
                    if (!isset($verification_data[trim($parsed_line[0])])) {
                        $verification_data[trim($parsed_line[0])]['csv']['total'] = 0;
                        $verification_data[trim($parsed_line[0])]['csv']['items'] = [];
                    }
                    
                    $item = new \Cake\ORM\Entity;
                    if (isset($parsed_line[1])) {
                        $item->period_total = trim($parsed_line[1]);
                    }
                    if (isset($parsed_line[2])) {
                        $item->name = trim($parsed_line[2]);
                    }
                    
                    $verification_data[trim($parsed_line[0])]['csv']['total'] += $item->period_total;
                    $verification_data[trim($parsed_line[0])]['csv']['items'][] = $item;
                    
                    unset($item);
                    unset($parsed_line);
                }
                unset($lines);
            }

            // billing data
            $billing_data = [];

            foreach ($customers as $customer) {
                foreach ($customer->contracts as $contract) {
                    foreach ($contract->billings as $billing) {
                        $billing->period_total = $billing->periodTotal($invoiced_month->firstOfMonth(), $invoiced_month->lastOfMonth());
                        
                        if (!isset($billing_data[$customer->number])) {
                            $billing_data[$customer->number]['total'] = 0;
                            $billing_data[$customer->number]['items'] = [];
                        }

                        $billing_data[$customer->number]['total'] += $billing->period_total;
                        $billing_data[$customer->number]['items'][] = $billing;
                        
                        unset($total);
                    }
                }

                // remove invoices with zero price
                if (isset($billing_data[$customer->number]) && $billing_data[$customer->number]['total'] == 0) {
                    unset($billing_data[$customer->number]);
                }

                // compare invoice with verification data if loaded and billing data available
                if (isset($verification_data) && isset($billing_data[$customer->number])) {
                    if (isset($verification_data[$customer->number])) {
                        if ($verification_data[$customer->number]['csv']['total'] == $billing_data[$customer->number]['total']) {
                            // remove from verification if OK
                            unset($verification_data[$customer->number]);
                        } else {
                            // add billing data to verification if not OK
                            $verification_data[$customer->number]['crm'] = $billing_data[$customer->number];
                        }
                    } else {
                        if (isset($billing_data[$customer->number])) {
                            // create missing verification data if there are billing data
                            $verification_data[$customer->number]['crm'] = $billing_data[$customer->number];
                        }
                    }
                }
            }
            
            if (isset($verification_data) && !empty($verification_data)) {
                $this->set('verification_data', $verification_data);
            }
        }
        
        if ($this->request->getParam('_ext') === 'dbf') {
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
                return $this->redirect(['action' => 'generate', '?' => $this->request->getQuery()]);
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
