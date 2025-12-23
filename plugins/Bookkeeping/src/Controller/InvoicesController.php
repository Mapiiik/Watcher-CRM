<?php
declare(strict_types=1);

namespace Bookkeeping\Controller;

use App\Model\Table\CustomersTable;
use App\Model\Table\TaxRatesTable;
use Bookkeeping\Model\Enum\InvoiceImportFormat;
use Bookkeeping\Service\BookkeepingService;
use Bookkeeping\View\DbfView;
use Bookkeeping\View\XmlView;
use Cake\I18n\Date;
use Cake\Validation\Validation;
use Exception;
use Override;
use PhpCollective\DecimalObject\Decimal;
use Settings\Utility\Settings;
use Throwable;

/**
 * Invoices Controller
 *
 * @property \Bookkeeping\Model\Table\InvoicesTable $Invoices
 */
class InvoicesController extends AppController
{
    /**
     * Returns supported output types
     */
    #[Override]
    public function viewClasses(): array
    {
        if ($this->getRequest()->getParam('_ext') === 'dbf' || $this->getRequest()->getParam('_ext') === 'xml') {
            return [
                DbfView::class,
                XmlView::class,
            ];
        }

        return [];
    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        // filter
        $conditions = [];

        // search
        $search = $this->getRequest()->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'Customers.company ILIKE' => '%' . trim($search) . '%',
                    'Customers.title ILIKE' => '%' . trim($search) . '%',
                    'Customers.first_name ILIKE' => '%' . trim($search) . '%',
                    'Customers.last_name ILIKE' => '%' . trim($search) . '%',
                    'Customers.suffix ILIKE' => '%' . trim($search) . '%',
                    'Invoices.number::text ILIKE' => '%' . trim($search) . '%',
                    'Invoices.variable_symbol::text ILIKE' => '%' . trim($search) . '%',
                ],
            ];
        }

        $this->paginate = [
            'order' => [
                'creation_date' => 'DESC',
            ],
        ];

        $invoices = $this->paginate($this->Invoices->find(
            'all',
            contain: [
                'Customers',
            ],
            conditions: $conditions,
        ));

        // notify about unsent invoices
        $unsentInvoices = $this->Invoices
            ->find()
            ->where([
                'send_by_email' => true,
                'email_sent IS NULL',
            ])
            ->count();

        if ($unsentInvoices > 0) {
            $this->Flash->warning(__dn(
                'bookkeeping',
                'Invoice to send in the queue, {0} email left.',
                'Invoices to send in the queue, {0} emails left.',
                $unsentInvoices,
                $unsentInvoices,
            ));
        }

        // get debts
        $query = $this->Invoices->find();
        $query = $query
            ->select([
                'debt' => $query->func()->sum('Invoices.debt'),
            ]);

        $this->set('total_debt', $query->first()['debt'] ?? 0);
        $this->set(
            'total_overdue_debt',
            $query
                ->where(['Invoices.due_date < NOW()'])
                ->first()['debt'] ?? 0,
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
    public function view(?string $id = null)
    {
        $invoice = $this->Invoices->get($id, contain: [
            'Customers',
            'Creators',
            'Modifiers',
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
    public function download(?string $id = null)
    {
        $invoice = $this->Invoices->get($id, contain: [
            'Customers',
        ]);

        $filePath = (new BookkeepingService())->getInvoicePdfPath($invoice);

        $response = $this->response->withFile($filePath, [
            'download' => true,
            'name' => basename($filePath),
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
        if ($this->getRequest()->is('post')) {
            $invoice = $this->Invoices->patchEntity($invoice, $this->getRequest()->getData());
            if ($this->Invoices->save($invoice)) {
                $this->Flash->success(__d('bookkeeping', 'The invoice has been saved.'));

                return $this->afterAddRedirect(['action' => 'view', $invoice->id]);
            }
            $this->Flash->error(__d('bookkeeping', 'The invoice could not be saved. Please, try again.'));
        }
        $customers = $this->Invoices->Customers->find('list', order: [
            'company',
            'last_name',
            'first_name',
        ]);
        $this->set(compact('invoice', 'customers'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Invoice id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $invoice = $this->Invoices->get($id);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $invoice = $this->Invoices->patchEntity($invoice, $this->getRequest()->getData());
            if ($this->Invoices->save($invoice)) {
                $this->Flash->success(__d('bookkeeping', 'The invoice has been saved.'));

                return $this->afterEditRedirect(['action' => 'view', $invoice->id]);
            }
            $this->Flash->error(__d('bookkeeping', 'The invoice could not be saved. Please, try again.'));
        }
        $customers = $this->Invoices->Customers->find('list', order: [
            'company',
            'last_name',
            'first_name',
        ]);
        $this->set(compact('invoice', 'customers'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Invoice id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $invoice = $this->Invoices->get($id);
        if ($this->Invoices->delete($invoice)) {
            $this->Flash->success(__d('bookkeeping', 'The invoice has been deleted.'));
        } else {
            $this->flashValidationErrors($invoice->getErrors());
            $this->Flash->error(__d('bookkeeping', 'The invoice could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }

    /**
     * Send by email method
     *
     * @return \Cake\Http\Response|null|void Redirects successful edit, renders view otherwise.
     */
    public function sendByEmail()
    {
        if ($this->getRequest()->is(['post']) && Validation::date($this->getRequest()->getData('creation_date'))) {
            $count = $this->Invoices->updateAll(
                [ // fields
                    'send_by_email' => true,
                ],
                [ // conditions
                    'send_by_email' => false,
                    'creation_date' => new Date($this->getRequest()->getData('creation_date')),
                ],
            );

            if ($count > 0) {
                $this->Flash->success(__d(
                    'bookkeeping',
                    'The invoices has been marked to be sent by email ({0}).',
                    $count,
                ));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->warning(__d(
                'bookkeeping',
                'No invoices could be marked to be sent by email. Please, try again.',
            ));
        }
    }

    /**
     * Validate and process CSV line
     */
    private function validateAndProcessCsvLine(array $parsedLine): ?array
    {
        $customerNumber = isset($parsedLine[0]) ? trim((string)$parsedLine[0]) : null;
        $periodTotalRaw = isset($parsedLine[1]) ? trim((string)$parsedLine[1]) : null;
        $name = isset($parsedLine[2]) ? trim((string)$parsedLine[2]) : '';

        // Normalize decimal separator
        $periodTotalNormalized = $periodTotalRaw !== null ? str_replace(',', '.', $periodTotalRaw) : null;

        if (!is_numeric($customerNumber)) {
            $this->Flash->error(
                __d('bookkeeping', 'Invalid customer number in CSV file: {0}', [$parsedLine[0] ?? '']),
            );

            return null;
        }

        if (!is_numeric($periodTotalNormalized)) {
            $this->Flash->error(
                __d('bookkeeping', 'Invalid price in CSV file: {0}', [$parsedLine[1] ?? '']),
            );

            return null;
        }

        return [
            'customerNumber' => $customerNumber,
            'item' => (object)[
                'period_total' => Decimal::create($periodTotalNormalized, 2),
                'name' => $name,
            ],
        ];
    }

    /**
     * Generate method
     *
     * @return \Cake\Http\Response|null|void Renders generateInvoices
     * @psalm-suppress ImplicitToStringCast
     */
    public function generate()
    {
        $taxRates = $this->fetchTable(TaxRatesTable::class)
            ->find('list', order: [
                'name',
            ])
            ->toArray();

        if ($this->getRequest()->is(['post'])) {
            $invoicedMonth = new Date($this->getRequest()->getData('invoiced_month', 'now'));
            $taxRate = $this->fetchTable(TaxRatesTable::class)->get($this->getRequest()->getData('tax_rate_id'));
            /** @var \Laminas\Diactoros\UploadedFile $csvForVerification */
            $csvForVerification = $this->getRequest()->getData('csv_for_verification');

            // VERIFICATION DATA CHECK
            if ($csvForVerification->getSize() > 0) {
                // load verification data from CSV
                $csvStream = $csvForVerification->getStream();
                $csvStream->rewind();
                $csvResource = $csvStream->detach();
                unset($csvStream);

                if ($csvResource === null) {
                    throw new Exception(__d('bookkeeping', 'Unable to process CSV file.'));
                }

                // create verification data array
                $verificationData = [];

                try {
                    while (($parsedLine = fgetcsv($csvResource, 1000, ',', '"', '\\')) !== false) {
                        // skip empty lines
                        if (empty(array_filter($parsedLine))) {
                            continue;
                        }

                        $result = $this->validateAndProcessCsvLine($parsedLine);
                        if ($result === null) {
                            continue;
                        }

                        $customerNumber = $result['customerNumber'];
                        $item = $result['item'];

                        if (!isset($verificationData[$customerNumber])) {
                            $verificationData[$customerNumber]['csv']['total'] = Decimal::create(0, 2);
                            $verificationData[$customerNumber]['csv']['items'] = [];
                        }

                        $verificationData[$customerNumber]['csv']['total'] =
                            $verificationData[$customerNumber]['csv']['total']->add($item->period_total);
                        $verificationData[$customerNumber]['csv']['items'][] = $item;
                    }
                } catch (Exception $e) {
                    fclose($csvResource);
                    throw new Exception(__d('bookkeeping', 'Error processing CSV file: {0}', $e->getMessage()));
                } finally {
                    fclose($csvResource);
                }

                unset($csvResource);

                // compare verification data with CRM billings
                $customers = $this->fetchTable(CustomersTable::class)
                    ->find('billingDataForMonth', [
                        'invoicedMonth' => $invoicedMonth,
                        'taxRateId' => $taxRate->id,
                    ]);

                foreach ($customers as $customer) {
                    /** @var \App\Model\Entity\Customer $customer */

                    // declare billing data
                    $billingData['total'] = Decimal::create(0, 2);
                    $billingData['items'] = [];

                    foreach ($customer->contracts as $contract) {
                        foreach ($contract->billings as $billing) {
                            $billingData['total'] = $billingData['total']->add($billing->period_total);
                            $billingData['items'][] = $billing;
                        }
                    }

                    // compare billing data with verification data
                    if (isset($verificationData[$customer->number])) {
                        if ($verificationData[$customer->number]['csv']['total'] == $billingData['total']) {
                            // remove from verification if OK
                            unset($verificationData[$customer->number]);
                        } else {
                            // add billing data to verification if not OK
                            $verificationData[$customer->number]['customer'] = $customer;
                            $verificationData[$customer->number]['crm'] = $billingData;
                        }
                    } else {
                        if (!$billingData['total']->isZero()) {
                            // create missing verification data if there are non zero billing items
                            $verificationData[$customer->number]['customer'] = $customer;
                            $verificationData[$customer->number]['crm'] = $billingData;
                        }
                    }

                    // clear billing_data for this customer
                    unset($billingData);
                }
            }

            if (isset($verificationData) && !empty($verificationData)) {
                $this->set('verificationData', $verificationData);
            } else {
                return $this->redirect([
                    'action' => 'generate',
                    '_ext' => $this->getRequest()->getData('output_format'),
                    '?' => [
                        'invoiced_month' => $invoicedMonth->i18nFormat('yyyy-MM'),
                        'tax_rate_id' => $taxRate->id,
                    ],
                ]);
            }
        }

        // DOWNLOAD INVOICES
        if ($this->getRequest()->getParam('_ext') === 'dbf' || $this->getRequest()->getParam('_ext') === 'xml') {
            $invoicedMonth = new Date($this->getRequest()->getQuery('invoiced_month', 'now'));

            /** @var \App\Model\Entity\TaxRate $taxRate */
            $taxRate = $this->fetchTable(TaxRatesTable::class)->get($this->getRequest()->getQuery('tax_rate_id'));

            if ($taxRate->reverse_charge) {
                $prefix = 10000000 * ($invoicedMonth->year - 1980)
                        + 1000000 * 8
                        + 10000 * $invoicedMonth->month;
            } else {
                $prefix = 10000000 * ($invoicedMonth->year - 1980)
                        + 1000000 * 9
                        + 10000 * $invoicedMonth->month;
            }

            // invoice number index
            $index = 1;

            $invoices = [];

            $customers = $this->fetchTable(CustomersTable::class)
                ->find('billingDataForMonth', [
                    'invoicedMonth' => $invoicedMonth,
                    'taxRateId' => $taxRate->id,
                ]);

            foreach ($customers as $customer) {
                /** @var \App\Model\Entity\Customer $customer */

                // declare customer billing data
                $billingCustomer['total'] = Decimal::create(0, 2);
                $billingCustomer['items'] = [];

                foreach ($customer->contracts as $contract) {
                    // declare contract billing data
                    $billingContract['total'] = Decimal::create(0, 2);
                    $billingContract['items'] = [];

                    foreach ($contract->billings as $billing) {
                        if ($billing->isSeparateInvoice() && !$billing->period_total->isZero()) {
                            $invoice = $this->Invoices->newEmptyEntity();
                            $invoice->number = $prefix + $index;
                            $invoice->customer = $customer;
                            $invoice->variable_symbol = (int)$customer->number;
                            $invoice->creation_date = $invoicedMonth->lastOfMonth();
                            $invoice->due_date = $invoicedMonth
                                ->lastOfMonth()
                                ->addDays($customer->individual_maturity_period ?? 10);
                            $invoice->text = strtr(Settings::getString('bookkeeping.invoices.texts.separate'), [
                                '{service_name}' => $billing->name,
                                '{invoiced_month}' => $invoicedMonth->i18nFormat('MM/yyyy'),
                            ]);
                            $invoice->internal_note = 'separate'; // TODO: constants?
                            $invoice->total = $billing->period_total;
                            //$invoice->items = [$billing];
                            $invoice->items = [];
                            $invoices[] = $invoice;
                            unset($invoice);
                            $index++;
                        } else {
                            $billingContract['total'] = $billingContract['total']->add($billing->period_total);
                            $billingContract['items'][] = $billing;
                        }
                    }

                    if ($contract->isSeparateInvoice() && !$billingContract['total']->isZero()) {
                        $invoice = $this->Invoices->newEmptyEntity();
                        $invoice->number = $prefix + $index;
                        $invoice->customer = $customer;
                        $invoice->variable_symbol = (int)$customer->number;
                        $invoice->creation_date = $invoicedMonth->lastOfMonth();
                        $invoice->due_date = $invoicedMonth
                            ->lastOfMonth()
                            ->addDays($customer->individual_maturity_period ?? 10);
                        if ($contract->getInvoiceText()) {
                            $invoice->text = strtr($contract->getInvoiceText(), [
                                '{number}' => $contract->number, // TODO: legacy
                                '{month}' => $invoicedMonth->i18nFormat('MM/yyyy'), // TODO: legacy
                                '{contract_number}' => $contract->number,
                                '{invoiced_month}' => $invoicedMonth->i18nFormat('MM/yyyy'),
                            ]);
                        } else {
                            $invoice->text = strtr(Settings::getString('bookkeeping.invoices.texts.default'), [
                                '{contract_number}' => $contract->number,
                                '{invoiced_month}' => $invoicedMonth->i18nFormat('MM/yyyy'),
                            ]);
                        }
                        $invoice->internal_note = 'separate'; // TODO: constants?
                        $invoice->total = $billingContract['total'];
                        $invoice->items = $contract->isInvoiceWithItems() ? $billingContract['items'] : [];
                        $invoices[] = $invoice;
                        unset($invoice);
                        $index++;
                    } else {
                        $billingCustomer['total'] = $billingCustomer['total']->add($billingContract['total']);
                        /** @psalm-suppress RedundantFunctionCall */
                        $billingCustomer['items'] = array_merge(
                            array_values($billingCustomer['items']),
                            array_values($billingContract['items']), // @phpstan-ignore arrayValues.list
                        );
                    }

                    unset($billingContract);
                }

                if (!$billingCustomer['total']->isZero()) {
                    $invoice = $this->Invoices->newEmptyEntity();
                    $invoice->number = $prefix + $index;
                    $invoice->customer = $customer;
                    $invoice->variable_symbol = (int)$customer->number;
                    $invoice->creation_date = $invoicedMonth->lastOfMonth();
                    $invoice->due_date = $invoicedMonth
                        ->lastOfMonth()
                        ->addDays($customer->individual_maturity_period ?? 10);
                    $invoice->text = strtr(Settings::getString('bookkeeping.invoices.texts.default'), [
                        '{contract_number}' => $customer->number,
                        '{invoiced_month}' => $invoicedMonth->i18nFormat('MM/yyyy'),
                    ]);
                    $invoice->total = $billingCustomer['total'];
                    $invoice->items = $customer->isInvoiceWithItems() ? $billingCustomer['items'] : [];
                    $invoices[] = $invoice;
                    unset($invoice);
                    $index++;
                }

                unset($billingCustomer);
            }

            $this->set(compact('invoices', 'taxRate', 'invoicedMonth'));
        }

        $this->set(compact('taxRates'));
    }

    /**
     * Import invoices from file.
     *
     * @return \Cake\Http\Response|null|void
     */
    public function importFromFile()
    {
        if (!$this->getRequest()->is(['post'])) {
            return null;
        }

        $format = InvoiceImportFormat::from(
            $this->getRequest()->getData('format'),
        );

        /** @var \Laminas\Diactoros\UploadedFile|null $file */
        $file = $this->getRequest()->getData('file');

        if ($file === null || $file->getSize() === 0) {
            $this->Flash->error(__d(
                'bookkeeping',
                'No file was uploaded.',
            ));

            return null;
        }

        $tmpPath = $file->getStream()->getMetadata('uri');

        try {
            $result = (new BookkeepingService())->importInvoices($tmpPath, $format);

            $this->Flash->success(__d(
                'bookkeeping',
                'Successfully imported {0} invoices. Created {1}, modified {2} and skipped {3} records.',
                $result['imported'],
                $result['created'],
                $result['modified'],
                $result['skipped'],
            ));
        } catch (Throwable $e) {
            $this->log($e->getMessage(), 'error');

            $this->Flash->error(__d(
                'bookkeeping',
                'An error occurred while importing the file: {0}',
                [$e->getMessage()],
            ));
        } finally {
            if (is_string($tmpPath) && file_exists($tmpPath)) {
                unlink($tmpPath);
            }
        }

        return null;
    }
}
