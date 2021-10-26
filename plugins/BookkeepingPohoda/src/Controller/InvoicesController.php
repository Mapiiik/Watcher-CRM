<?php
declare(strict_types=1);

namespace BookkeepingPohoda\Controller;

use BookkeepingPohoda\Controller\AppController;
use Cake\I18n\FrozenDate;
use Cake\ORM\Query;
use Cake\ORM\Entity;

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
    
    private function getQueryForBillingDataForMonth(FrozenDate $invoiced_month, int $tax_rate_id): Query {
        return $this->getTableLocator()->get('Customers')
                ->find()
                ->order('Customers.id')
                ->where(['Customers.taxe_id' => $tax_rate_id])
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
                                        ->formatResults(function (\Cake\Collection\CollectionInterface $billings) use ($invoiced_month) {
                                                return $billings->map(function ($billing) use ($invoiced_month) {
                                                    $billing['period_total'] = $billing->periodTotal($invoiced_month->firstOfMonth(), $invoiced_month->lastOfMonth());
                                                    return $billing;
                                                });                                        
                                        })
                                        ->contain(['Services']);
                                });
                    });
    }
    
    /**
     * GenerateInvoices method
     *
     * @return \Cake\Http\Response|null|void Renders generateInvoices
     */
    public function generate()
    {
        $tax_rates = $this->getTableLocator()->get('Taxes')->find('list', ['order' => 'name'])->toArray();
        
        if ($this->request->is(['post'])) {
            $invoiced_month = new FrozenDate($this->request->getData('invoiced_month'));
            $tax_rate_id = (int)$this->request->getData('tax_rate_id');
            $csv_for_verification = $this->request->getData('csv_for_verification');
            
            // VERIFICATION DATA CHECK
            if ($csv_for_verification->getSize() > 0) {
                // load verification data from CSV
                $lines = explode(PHP_EOL, $csv_for_verification->getStream()->getContents());
                $verification_data = [];
                
                foreach ($lines as $line) {
                    $parsed_line = explode(",", $line);
                    $customer_number = trim($parsed_line[0]);
                    
                    if (!is_numeric($customer_number)) continue; // if there is no customer cumber in first column, skip the line
                    
                    if (!isset($verification_data[$customer_number])) {
                        $verification_data[$customer_number]['csv']['total'] = 0;
                        $verification_data[$customer_number]['csv']['items'] = [];
                    }
                    
                    $item = new Entity;
                    if (isset($parsed_line[1])) {
                        $item->period_total = trim($parsed_line[1]);
                    }
                    if (isset($parsed_line[2])) {
                        $item->name = trim($parsed_line[2]);
                    }
                    
                    $verification_data[$customer_number]['csv']['total'] += $item->period_total;
                    $verification_data[$customer_number]['csv']['items'][] = $item;
                    
                    unset($item);
                    unset($customer_number);
                    unset($parsed_line);
                }
                unset($lines);
                
                // compare verification data with CRM billings
                foreach ($this->getQueryForBillingDataForMonth($invoiced_month, $tax_rate_id) as $customer) {
                    // declare billing data
                    $billing_data['total'] = 0;
                    $billing_data['items'] = [];
                    
                    foreach ($customer->contracts as $contract) {
                        foreach ($contract->billings as $billing) {
                            $billing_data['total'] += $billing->period_total;
                            $billing_data['items'][] = $billing;
                        }
                    }
                    
                    // compare billing data with verification data
                    if (isset($verification_data[$customer->number])) {
                        if ($verification_data[$customer->number]['csv']['total'] == $billing_data['total']) {
                            // remove from verification if OK
                            unset($verification_data[$customer->number]);
                        } else {
                            // add billing data to verification if not OK
                            $verification_data[$customer->number]['customer'] = $customer;
                            $verification_data[$customer->number]['crm'] = $billing_data;
                        }
                    } else {
                        if ($billing_data['total'] <> 0) {
                            // create missing verification data if there are non zero billing items
                            $verification_data[$customer->number]['customer'] = $customer;
                            $verification_data[$customer->number]['crm'] = $billing_data;
                        }
                    }
                    
                    // clear billing_data for this customer
                    unset($billing_data);
                }
            }
            
            if (isset($verification_data) && !empty($verification_data)) {
                $this->set('verification_data', $verification_data);
            } else {
                return $this->redirect(['action' => 'generate', '_ext' => 'dbf' , '?' => ['invoiced_month' => $invoiced_month->i18nFormat('yyyy-MM'), 'tax_rate_id' => $tax_rate_id]]);
            }
        }
        
        // DOWNLOAD INVOICES
        if ($this->request->getParam('_ext') === 'dbf') {
            $invoiced_month = new FrozenDate($this->request->getQuery('invoiced_month'));
            $tax_rate_id = (int)$this->request->getQuery('tax_rate_id');

            // DO THIS BETTER - REVERSE CHARGE
            if ($tax_rate_id == 5) {
                $reverse_charge = true;
                $innerfix = "8";
            } else {
                $reverse_charge = false;
                $innerfix = "9";
            }            
            
            // invoice number index
            $index = 1;
            
            $invoices = [];
            
            foreach ($this->getQueryForBillingDataForMonth($invoiced_month, $tax_rate_id) as $customer) {
                // declare customer billing data
                $billing_customer['total'] = 0;
                $billing_customer['items'] = [];

                foreach ($customer->contracts as $contract) {
                    // declare contract billing data
                    $billing_contract['total'] = 0;
                    $billing_contract['items'] = [];                    
                    
                    foreach ($contract->billings as $billing) {
                        if ($billing->separate_invoice)
                        {
                            $invoice = $this->Invoices->newEmptyEntity();
                            $invoice->number = sprintf('%02d', $invoiced_month->year - 1980) . $innerfix . $invoiced_month->month . sprintf('%04d', $index);
                            $invoice->customer = $customer;
                            $invoice->variable_symbol = $customer->number;
                            $invoice->creation_date = $invoiced_month->lastOfMonth();
                            $invoice->due_date = $invoiced_month->lastOfMonth()->addDays(10);
                            $invoice->text = $billing->name;
                            $invoice->internal_note = 'separate';
                            $invoice->total = $billing->period_total;
                            $invoice->items[] = $billing;
                            $invoices[] = $invoice;
                            unset($invoice);
                            $index++;
                        }
                        else
                        {
                            $billing_contract['total'] += $billing->period_total;
                            $billing_contract['items'][] = $billing;
                        }
                    }
                    
                    if ($contract->separate_invoice)
                    {
                        $invoice = $this->Invoices->newEmptyEntity();
                        $invoice->number = sprintf('%02d', $invoiced_month->year - 1980) . $innerfix . $invoiced_month->month . sprintf('%04d', $index);
                        $invoice->customer = $customer;
                        $invoice->variable_symbol = $customer->number;
                        $invoice->creation_date = $invoiced_month->lastOfMonth();
                        $invoice->due_date = $invoiced_month->lastOfMonth()->addDays(10);
                        $invoice->text = "Faktura za poskytované služby dle smlouvy {$contract->number} - {$invoiced_month->month}/{$invoiced_month->year}";
                        $invoice->internal_note = 'separate';
                        $invoice->total = $billing_contract['total'];
                        $invoice->items = $billing_contract['items'];
                        $invoices[] = $invoice;
                        unset($invoice);
                        $index++;
                    }
                    else
                    {
                        $billing_customer['total'] += $billing_contract['total'];
                        $billing_customer['items'] += $billing_contract['items'];
                    }

                    unset($billing_contract);
                }

                if ($billing_customer['total'] <> 0)
                {
                    $invoice = $this->Invoices->newEmptyEntity();
                    $invoice->number = sprintf('%02d', $invoiced_month->year - 1980) . $innerfix . $invoiced_month->month . sprintf('%04d', $index);
                    $invoice->customer = $customer;
                    $invoice->variable_symbol = $customer->number;
                    $invoice->creation_date = $invoiced_month->lastOfMonth();
                    $invoice->due_date = $invoiced_month->lastOfMonth()->addDays(10);
                    $invoice->text = "Faktura za poskytované služby dle smlouvy - {$invoiced_month->month}/{$invoiced_month->year}";
                    $invoice->total = $billing_customer['total'];
                    $invoice->items = $billing_customer['items'];
                    $invoices[] = $invoice;
                    $index++;
                };

                unset($billing_customer);
            }
            
            $this->set(compact('invoices', 'tax_rate_id', 'invoiced_month', 'reverse_charge'));
        }
        
        $this->set(compact('tax_rates'));
    }    
}
