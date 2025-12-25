<?php
declare(strict_types=1);

namespace Bookkeeping\Controller;

use App\Model\Table\CustomersTable;
use App\Model\Table\TaxRatesTable;
use Bookkeeping\Model\Enum\InvoiceExportFormat;
use Bookkeeping\Model\Enum\InvoiceImportFormat;
use Bookkeeping\Service\BookkeepingService;
use Bookkeeping\Service\CsvVerificationService;
use Bookkeeping\Service\InvoiceGenerationService;
use Bookkeeping\View\DbfView;
use Bookkeeping\View\XmlView;
use Cake\I18n\Date;
use Cake\Validation\Validation;
use Override;
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

        $this->set(compact('taxRates'));

        if ($this->getRequest()->is(['post'])) {
            $invoicedMonth = new Date($this->getRequest()->getData('invoiced_month', 'now'));
            $taxRate = $this->fetchTable(TaxRatesTable::class)->get($this->getRequest()->getData('tax_rate_id'));

            // VERIFICATION DATA CHECK
            /** @var \Laminas\Diactoros\UploadedFile $csvFile */
            $csvFile = $this->getRequest()->getData('csv_for_verification');

            if ($csvFile && $csvFile->getSize() > 0) {
                $csvVerificator = new CsvVerificationService($this->fetchTable(CustomersTable::class));
                $result = $csvVerificator->verify(
                    $invoicedMonth,
                    $taxRate,
                    $csvFile,
                );

                if ($result->hasErrors()) {
                    foreach ($result->getErrors() as $error) {
                        $this->Flash->error(
                            __d(
                                'bookkeeping',
                                'CSV line {0}: {1} ({2})',
                                [$error['line'], $error['message'], $error['value']],
                            ),
                        );
                    }
                }

                if (!$result->isOk()) {
                    $this->set('verificationData', $result->getDifferences());

                    return;
                }
            }

            return $this->redirect([
                'action' => 'generate',
                '_ext' => InvoiceExportFormat::from($this->getRequest()->getData('output_format'))->value,
                '?' => [
                    'invoiced_month' => $invoicedMonth->i18nFormat('yyyy-MM'),
                    'tax_rate_id' => $taxRate->id,
                ],
            ]);
        }

        // DOWNLOAD INVOICES
        if (in_array($this->getRequest()->getParam('_ext'), ['dbf', 'xml'], true)) {
            $invoicedMonth = new Date($this->getRequest()->getQuery('invoiced_month', 'now'));

            /** @var \App\Model\Entity\TaxRate $taxRate */
            $taxRate = $this->fetchTable(TaxRatesTable::class)->get($this->getRequest()->getQuery('tax_rate_id'));

            $exportFormat = InvoiceExportFormat::from($this->getRequest()->getParam('_ext'));
            $exportType = match ($exportFormat) {
                InvoiceExportFormat::DBF =>
                    'application/dbase',
                InvoiceExportFormat::XML =>
                    'application/xml',
            };

            $invoiceGenerator = new InvoiceGenerationService($this->fetchTable(CustomersTable::class));
            $drafts = $invoiceGenerator->generate(
                $invoicedMonth,
                $taxRate,
            );

            $bookkeeping = new BookkeepingService();
            $filePath = $bookkeeping->exportInvoices(
                $drafts,
                $exportFormat,
                [
                    'invoicedMonth' => $invoicedMonth,
                    'taxRate' => $taxRate,
                ],
            );

            // set for download with specified filename
            $response = $this->getResponse()
                ->withType($exportType)
                ->withDownload(
                    sprintf(
                        'Invoices-%s-%s.' . $exportFormat->value,
                        strtolower($taxRate->name),
                        $invoicedMonth->i18nFormat('yyyy-MM'),
                    ),
                )
                ->withFile($filePath);

            register_shutdown_function(
                static function () use ($filePath): void {
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                },
            );

            return $response;
        }
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
