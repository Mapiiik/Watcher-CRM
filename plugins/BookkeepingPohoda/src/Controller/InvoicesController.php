<?php
declare(strict_types=1);

namespace BookkeepingPohoda\Controller;

use Cake\Collection\CollectionInterface;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\I18n\FrozenDate;
use Cake\ORM\Query;
use stdClass;

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
            'order' => [
                'Invoices.id' => 'DESC',
            ],
        ];
        $invoices = $this->paginate($this->Invoices);

        // get debts
        $query = $this->Invoices->find();
        $query = $query
            ->select([
                'debt' => $query->func()->sum('Invoices.debt'),
            ]);

        $this->set('total_debt', $query->first()['debt']);
        $this->set(
            'total_overdue_debt',
            $query
                ->where(['Invoices.due_date < NOW()'])
                ->first()['debt']
        );

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
            'contain' => [
                'Customers',
                'Creators',
                'Modifiers',
            ],
        ]);

        $this->set(compact('invoice'));
    }

    /**
     * Download method
     *
     * @param string|null $id Invoice id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function download($id = null)
    {
        $invoice = $this->Invoices->get($id, [
            'contain' => ['Customers'],
        ]);

        $filename = env('DATA_ROOT', DS . 'data' . DS) . 'invoices' . DS
                    . 'Faktura_' . $invoice->number . '.pdf';

        $response = $this->response->withFile($filename, [
            'download' => true,
            'name' => 'Faktura-' . $invoice->number
                    . '-VS' . $invoice->variable_symbol . '.pdf',
        ]);

        return $response;
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
                $this->Flash->success(__d('bookkeeping_pohoda', 'The invoice has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__d('bookkeeping_pohoda', 'The invoice could not be saved. Please, try again.'));
        }
        $customers = $this->Invoices->Customers->find('list', ['order' => ['company', 'first_name', 'last_name']]);
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
                $this->Flash->success(__d('bookkeeping_pohoda', 'The invoice has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__d('bookkeeping_pohoda', 'The invoice could not be saved. Please, try again.'));
        }
        $customers = $this->Invoices->Customers->find('list', ['order' => ['company', 'first_name', 'last_name']]);
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
            $this->Flash->success(__d('bookkeeping_pohoda', 'The invoice has been deleted.'));
        } else {
            $this->Flash->error(__d('bookkeeping_pohoda', 'The invoice could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Send by email method
     *
     * @return \Cake\Http\Response|null|void Redirects successful edit, renders view otherwise.
     */
    public function sendByEmail()
    {
        if ($this->request->is(['post']) && !empty($this->request->getData('creation_date'))) {
            $count = $this->Invoices->updateAll(
                [ // fields
                    'send_by_email' => true,
                ],
                [ // conditions
                    'send_by_email' => false,
                    'creation_date' => new FrozenDate($this->request->getData('creation_date')),
                ]
            );

            if ($count > 0) {
                $this->Flash->success(__d(
                    'bookkeeping_pohoda',
                    'The invoices has been marked to be sent by email ({0}).',
                    $count
                ));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->warning(__d(
                'bookkeeping_pohoda',
                'No invoices could be marked to be sent by email. Please, try again.'
            ));
        }
    }

    /**
     * get Query with billing data for selected month
     *
     * @param \Cake\I18n\FrozenDate $invoiced_month Month for billing
     * @param int $tax_rate_id month Id of tax rate for billing
     * @return \Cake\ORM\Query
     */
    private function getQueryForBillingDataForMonth(FrozenDate $invoiced_month, int $tax_rate_id): Query
    {
        return $this->fetchTable('Customers')
                ->find()
                ->order('Customers.id')
                ->where(['Customers.tax_rate_id' => $tax_rate_id])
                ->contain('Addresses')
                ->contain('Contracts', function (Query $q) use ($invoiced_month) {
                    return $q
                            ->order('Contracts.id')
/* ignore for now, use only dates from billings (sometimes we want to bill even without contract)
                            ->where([
                                'OR' => [
                                    'Contracts.valid_from IS NULL',
                                    'Contracts.valid_from <=' => $invoiced_month->lastOfMonth(), //last day of month
                                ],
                            ])
                            ->andWhere([
                                'OR' => [
                                    'Contracts.valid_until IS NULL',
                                    'Contracts.valid_until >=' => $invoiced_month->firstOfMonth(), //first day of month
                                ],
                            ])
*/
                            ->contain('ServiceTypes')
                            ->contain('Billings', function (Query $q) use ($invoiced_month) {
                                return $q
                                        ->order('Billings.id')
                                        ->where([
                                            'Billings.billing_from <=' => $invoiced_month->lastOfMonth(), //last day of month
                                        ])
                                        ->andWhere([
                                            'OR' => [
                                                'Billings.billing_until IS NULL',
                                                'Billings.billing_until >=' => $invoiced_month->firstOfMonth(), //first day of month
                                            ],
                                        ])
                                        ->formatResults(
                                            function (CollectionInterface $billings) use ($invoiced_month) {
                                                return $billings->map(function ($billing) use ($invoiced_month) {
                                                    $billing['period_total'] = $billing->periodTotal(
                                                        $invoiced_month->firstOfMonth(),
                                                        $invoiced_month->lastOfMonth()
                                                    );

                                                    return $billing;
                                                });
                                            }
                                        )
                                        ->contain(['Services']);
                            });
                });
    }

    /**
     * Generate method
     *
     * @return \Cake\Http\Response|null|void Renders generateInvoices
     */
    public function generate()
    {
        $tax_rates = $this->fetchTable('TaxRates')->find('list', ['order' => 'name'])->toArray();

        if ($this->request->is(['post'])) {
            $invoiced_month = new FrozenDate($this->request->getData('invoiced_month'));
            $tax_rate = $this->fetchTable('TaxRates')->get($this->request->getData('tax_rate_id'));
            /** @var \Laminas\Diactoros\UploadedFile $csv_for_verification */
            $csv_for_verification = $this->request->getData('csv_for_verification');

            // VERIFICATION DATA CHECK
            if ($csv_for_verification->getSize() > 0) {
                // load verification data from CSV
                $lines = explode(PHP_EOL, $csv_for_verification->getStream()->getContents());
                $verification_data = [];

                foreach ($lines as $line) {
                    $parsed_line = explode(',', $line);
                    $customer_number = trim($parsed_line[0]);

                    if (!is_numeric($customer_number)) {
                        continue; // if there is no customer cumber in first column, skip the line
                    }

                    if (!isset($verification_data[$customer_number])) {
                        $verification_data[$customer_number]['csv']['total'] = 0;
                        $verification_data[$customer_number]['csv']['items'] = [];
                    }

                    $item = new stdClass();
                    $item->period_total = isset($parsed_line[1]) ? trim($parsed_line[1]) : '';
                    $item->name = isset($parsed_line[2]) ? trim($parsed_line[2]) : '';

                    $verification_data[$customer_number]['csv']['total'] += $item->period_total;
                    $verification_data[$customer_number]['csv']['items'][] = $item;

                    unset($item);
                    unset($customer_number);
                    unset($parsed_line);
                }
                unset($lines);

                // compare verification data with CRM billings
                foreach ($this->getQueryForBillingDataForMonth($invoiced_month, $tax_rate->id) as $customer) {
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
                return $this->redirect([
                    'action' => 'generate',
                    '_ext' => $this->request->getData('output_format'),
                    '?' => [
                        'invoiced_month' => $invoiced_month->i18nFormat('yyyy-MM'),
                        'tax_rate_id' => $tax_rate->id,
                    ],
                ]);
            }
        }

        // DOWNLOAD INVOICES
        if ($this->request->getParam('_ext') === 'dbf' || $this->request->getParam('_ext') === 'xml') {
            $invoiced_month = new FrozenDate($this->request->getQuery('invoiced_month'));

            /** @var \App\Model\Entity\TaxRate $tax_rate */
            $tax_rate = $this->fetchTable('TaxRates')->get($this->request->getQuery('tax_rate_id'));

            if ($tax_rate->reverse_charge) {
                $prefix = 10000000 * ($invoiced_month->year - 1980)
                        + 1000000 * 8
                        + 10000 * $invoiced_month->month;
            } else {
                $prefix = 10000000 * ($invoiced_month->year - 1980)
                        + 1000000 * 9
                        + 10000 * $invoiced_month->month;
            }

            // invoice number index
            $index = 1;

            $invoices = [];

            foreach ($this->getQueryForBillingDataForMonth($invoiced_month, $tax_rate->id) as $customer) {
                // declare customer billing data
                $billing_customer['total'] = 0;
                $billing_customer['items'] = [];

                foreach ($customer->contracts as $contract) {
                    // declare contract billing data
                    $billing_contract['total'] = 0;
                    $billing_contract['items'] = [];

                    foreach ($contract->billings as $billing) {
                        if ($billing->separate_invoice && $billing->period_total <> 0) {
                            $invoice = $this->Invoices->newEmptyEntity();
                            $invoice->number = $prefix + $index;
                            $invoice->customer = $customer;
                            $invoice->variable_symbol = $customer->number;
                            $invoice->creation_date = $invoiced_month->lastOfMonth();
                            $invoice->due_date = $invoiced_month->lastOfMonth()->addDays(10);
                            $invoice->text = $billing->name
                                . ' za obdob?? ' . $invoiced_month->i18nFormat('MM/yyyy');
                            $invoice->internal_note = 'separate';
                            $invoice->total = $billing->period_total;
                            //$invoice->items = [$billing];
                            $invoice->items = [];
                            $invoices[] = $invoice;
                            unset($invoice);
                            $index++;
                        } else {
                            $billing_contract['total'] += $billing->period_total;
                            $billing_contract['items'][] = $billing;
                        }
                    }

                    if ($contract->separate_invoice && $billing_contract['total'] <> 0) {
                        $invoice = $this->Invoices->newEmptyEntity();
                        $invoice->number = $prefix + $index;
                        $invoice->customer = $customer;
                        $invoice->variable_symbol = $customer->number;
                        $invoice->creation_date = $invoiced_month->lastOfMonth();
                        $invoice->due_date = $invoiced_month->lastOfMonth()->addDays(10);
                        if ($contract->invoice_text) {
                            $invoice->text = strtr($contract->invoice_text, [
                                '{number}' => $contract->number,
                                '{month}' => $invoiced_month->i18nFormat('MM/yyyy'),
                            ]);
                        } else {
                            $invoice->text = 'Faktura za poskytovan?? slu??by dle smlouvy '
                                . $contract->number
                                . ' za obdob?? ' . $invoiced_month->i18nFormat('MM/yyyy');
                        }
                        $invoice->internal_note = 'separate';
                        $invoice->total = $billing_contract['total'];
                        $invoice->items = $contract->invoice_with_items ? $billing_contract['items'] : [];
                        $invoices[] = $invoice;
                        unset($invoice);
                        $index++;
                    } else {
                        $billing_customer['total'] += $billing_contract['total'];
                        $billing_customer['items'] = array_merge(
                            array_values($billing_customer['items']),
                            array_values($billing_contract['items'])
                        );
                    }

                    unset($billing_contract);
                }

                if ($billing_customer['total'] <> 0) {
                    $invoice = $this->Invoices->newEmptyEntity();
                    $invoice->number = $prefix + $index;
                    $invoice->customer = $customer;
                    $invoice->variable_symbol = $customer->number;
                    $invoice->creation_date = $invoiced_month->lastOfMonth();
                    $invoice->due_date = $invoiced_month->lastOfMonth()->addDays(10);
                    $invoice->text = 'Faktura za poskytovan?? slu??by dle smlouvy'
                        . ' za obdob?? ' . $invoiced_month->i18nFormat('MM/yyyy');
                    $invoice->total = $billing_customer['total'];
                    $invoice->items = $customer->invoice_with_items ? $billing_customer['items'] : [];
                    $invoices[] = $invoice;
                    unset($invoice);
                    $index++;
                }

                unset($billing_customer);
            }

            $this->set(compact('invoices', 'tax_rate', 'invoiced_month'));
        }

        $this->set(compact('tax_rates'));
    }

    /**
     * Import from DBF method
     *
     * @return \Cake\Http\Response|null|void Renders generateInvoices
     */
    public function importFromDBF()
    {
        if ($this->request->is(['post'])) {
            /** @var \Laminas\Diactoros\UploadedFile $dbf_for_import */
            $dbf_for_import = $this->request->getData('dbf_for_import');

            $created = 0;
            $modified = 0;
            // VERIFICATION DATA CHECK
            if ($dbf_for_import->getSize() > 0) {
                $dbase = dbase_open($_FILES['dbf_for_import']['tmp_name'], 0);

                $record_count = dbase_numrecords($dbase);
                for ($record_number = 1; $record_number <= $record_count; $record_number++) {
                    // right! record #s begin with 1, don't forget <=
                    $record = dbase_get_record_with_names($dbase, $record_number);
                    foreach ($record as $key => $value) {
                        if (is_string($value)) {
                            $record[$key] = trim(iconv('CP852', 'UTF-8', $value));
                        } else {
                            $record[$key] = $value;
                        }
                    }

                    // check that all columns are present
                    if (
                        !(
                            isset($record['CISLO'])
                            && isset($record['VARSYM'])
                            && isset($record['DATUM'])
                            && isset($record['DATSPLAT'])
                            && isset($record['STEXT'])
                            && isset($record['KCCELKEM'])
                            && isset($record['KCLIKV'])
                            && isset($record['DATLIKV'])
                        )
                    ) {
                        $this->Flash->error(__d(
                            'bookkeeping_pohoda',
                            'The import file is missing some required columns.'
                        ));

                        return null;
                    }

                    if (
                        ((int)env('CUSTOMER_SERIES', '0') < (int)$record['VARSYM']) &&
                        ((int)$record['VARSYM'] < (int)env('CUSTOMER_SERIES', '0') + 50000)
                    ) {
                        try {
                            $invoice = $this->Invoices->setPrimaryKey('number')->get($record['CISLO']);
                        } catch (RecordNotFoundException $e) {
                            $invoice = $this->Invoices->newEntity(['number' => $record['CISLO']]);
                        }

                        $invoice->customer_id = (int)$record['VARSYM'] - (int)env('CUSTOMER_SERIES', '0');
                        $invoice->variable_symbol = (int)$record['VARSYM'];
                        $invoice->creation_date = $record['DATUM'];
                        $invoice->due_date = $record['DATSPLAT'];
                        $invoice->text = $record['STEXT'];
                        $invoice->total = $record['KCCELKEM'];
                        $invoice->debt = $record['KCLIKV'];
                        $invoice->payment_date = $record['DATLIKV'] <> '' ? $record['DATLIKV'] : null;

                        if ($invoice->isNew()) {
                            $created++;
                        } else {
                            $modified++;
                        }

                        $this->Invoices->save($invoice);

                        if ($invoice->hasErrors()) {
                            $this->Flash->error(__d(
                                'bookkeeping_pohoda',
                                'Invoice {0} could not be loaded.',
                                $invoice->number
                            ));
                        }
                    }

                    if ($record_number == $record_count) {
                        $this->Flash->success(__d(
                            'bookkeeping_pohoda',
                            'Successfully imported {0} invoices. Created {1}, modified {2} and skipped {3} records.',
                            $record_count,
                            $created,
                            $modified,
                            $record_count - $created - $modified
                        ));
                    }
                }
                // close database
                dbase_close($dbase);
                //remove file
                unlink($_FILES['dbf_for_import']['tmp_name']);
            }
        }
    }
}
