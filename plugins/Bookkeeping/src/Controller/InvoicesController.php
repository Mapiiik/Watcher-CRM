<?php
declare(strict_types=1);

namespace Bookkeeping\Controller;

use App\Model\Table\AccountingProfilesTable;
use App\Model\Table\CustomersTable;
use Bookkeeping\Model\Enum\InvoiceExportFormat;
use Bookkeeping\Model\Enum\InvoiceImportFormat;
use Bookkeeping\Service\BookkeepingService;
use Bookkeeping\Service\CsvVerificationService;
use Bookkeeping\Service\InvoiceGenerationService;
use Bookkeeping\View\DbfView;
use Bookkeeping\View\XmlView;
use Cake\Http\Response;
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
                    'Customers.company ILIKE' => '%' . trim((string)$search) . '%',
                    'Customers.title ILIKE' => '%' . trim((string)$search) . '%',
                    'Customers.first_name ILIKE' => '%' . trim((string)$search) . '%',
                    'Customers.last_name ILIKE' => '%' . trim((string)$search) . '%',
                    'Customers.suffix ILIKE' => '%' . trim((string)$search) . '%',
                    'Invoices.number ILIKE' => '%' . trim((string)$search) . '%',
                    'Invoices.variable_symbol ILIKE' => '%' . trim((string)$search) . '%',
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
        /** @var \Cake\ORM\Query\SelectQuery<\Bookkeeping\Model\Entity\Invoice> $query */
        $query = $this->Invoices->find();

        // define sum of debts
        $query->select([
            'debt_sum' => $query->func()->sum('Invoices.debt'),
        ]);

        // set total debt
        $this->set(
            'total_debt',
            $query->first()?->get('debt_sum') ?? 0,
        );

        // set total overdue debt
        $this->set(
            'total_overdue_debt',
            $query
                ->cleanCopy() // clone the query to avoid modifying the original one
                ->where(['Invoices.due_date < NOW()'])
                ->first()?->get('debt_sum') ?? 0,
        );

        $this->set(compact('invoices'));
    }

    /**
     * View method
     *
     * @param string|null $id Invoice id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): void
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
     * @return \Cake\Http\Response Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function download(?string $id = null): Response
    {
        $invoice = $this->Invoices->get($id, contain: [
            'Customers',
        ]);

        $filePath = (new BookkeepingService())->getInvoicePdfPath($invoice);

        return $this->response->withFile($filePath, [
            'download' => true,
            'name' => basename($filePath),
        ]);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(): ?Response
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

        return null;
    }

    /**
     * Edit method
     *
     * @param string|null $id Invoice id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
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

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Invoice id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
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
     * @return \Cake\Http\Response|null Redirects successful edit, renders view otherwise.
     */
    public function sendByEmail(): ?Response
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

        return null;
    }

    /**
     * Generate method
     *
     * @return \Cake\Http\Response|null Renders generateInvoices
     * @psalm-suppress ImplicitToStringCast
     */
    public function generate(): ?Response
    {
        $accountingProfiles = $this->fetchTable(AccountingProfilesTable::class)
            ->find('list', order: [
                'name',
            ])
            ->toArray();

        $this->set(compact('accountingProfiles'));

        if ($this->getRequest()->is(['post'])) {
            $invoicedMonth = new Date($this->getRequest()->getData('invoiced_month', 'now'));
            $accountingProfile = $this->fetchTable(AccountingProfilesTable::class)
                ->get($this->getRequest()->getData('accounting_profile_id'));

            // VERIFICATION DATA CHECK
            /** @var \Laminas\Diactoros\UploadedFile $csvFile */
            $csvFile = $this->getRequest()->getData('csv_for_verification');

            if ($csvFile && $csvFile->getSize() > 0) {
                $csvVerificator = new CsvVerificationService($this->fetchTable(CustomersTable::class));
                $result = $csvVerificator->verify(
                    $invoicedMonth,
                    $accountingProfile,
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

                    return null;
                }
            }

            return $this->redirect([
                'action' => 'generate',
                '_ext' => InvoiceExportFormat::from($this->getRequest()->getData('output_format'))->value,
                '?' => [
                    'invoiced_month' => $invoicedMonth->i18nFormat('yyyy-MM'),
                    'accounting_profile_id' => $accountingProfile->id,
                ],
            ]);
        }

        // DOWNLOAD INVOICES
        if (in_array($this->getRequest()->getParam('_ext'), ['dbf', 'xml'], true)) {
            $invoicedMonth = new Date($this->getRequest()->getQuery('invoiced_month', 'now'));

            /** @var \App\Model\Entity\AccountingProfile $accountingProfile */
            $accountingProfile = $this->fetchTable(AccountingProfilesTable::class)
                ->get($this->getRequest()->getQuery('accounting_profile_id'));

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
                $accountingProfile,
            );

            $bookkeeping = new BookkeepingService();
            $filePath = $bookkeeping->exportInvoices(
                invoices: $drafts,
                invoicedMonth: $invoicedMonth,
                accountingProfile: $accountingProfile,
                format: $exportFormat,
            );

            // set for download with specified filename
            $response = $this->getResponse()
                ->withType($exportType)
                ->withDownload(
                    sprintf(
                        'Invoices-%s-%s.' . $exportFormat->value,
                        strtolower($accountingProfile->name),
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

        return null;
    }

    /**
     * Import invoices from file.
     *
     * @return null
     */
    public function importFromFile(): null
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
