<?php
declare(strict_types=1);

namespace Bookkeeping\Provider\Eurofaktura;

use App\Model\Entity\AccountingProfile;
use Bookkeeping\Model\Entity\Invoice;
use Bookkeeping\Model\Enum\InvoiceExportFormat;
use Bookkeeping\Model\Enum\InvoiceImportFormat;
use Bookkeeping\Provider\AccountingProviderInterface;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use RuntimeException;

/**
 * Class EurofakturaProvider
 *
 * Main orchestration layer for the Eurofaktura / E-racuni accounting system.
 *
 * This provider acts as a thin coordination layer between the domain
 * (BookkeepingService) and the external accounting API.
 *
 * Responsibilities:
 * - Orchestrates invoice synchronization and submission
 * - Delegates transport to HttpClient
 * - Delegates payload construction to request builders
 * - Does NOT contain business logic
 * - Does NOT perform low-level API parsing
 *
 * The provider exposes a stable interface used by BookkeepingService,
 * regardless of the underlying accounting system.
 */
class EurofakturaProvider implements AccountingProviderInterface
{
    public const SETTINGS_ROOT = 'bookkeeping.accounting.providers.eurofaktura';

    private HttpClient $httpClient;
    private JsonRequestBuilder $jsonRequestBuilder;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->httpClient = new HttpClient();
        $this->jsonRequestBuilder = new JsonRequestBuilder();
    }

    /**
     * Synchronize invoices from Eurofaktura / E-racuni.
     *
     * Fetches invoices changed since the given timestamp and maps them
     * into internal InvoiceDraft value objects.
     *
     * @param \Cake\I18n\DateTime $lastChanges Timestamp of last successful sync.
     * @return list<\Bookkeeping\Model\ValueObject\InvoiceDraft>
     */
    public function syncInvoices(DateTime $lastChanges): array
    {
        throw new RuntimeException(
            __d(
                'bookkeeping',
                'Not implemented in Eurofaktura Provider.',
            ),
        );
    }

    /**
     * Send invoices to Eurofaktura / E-racuni via API.
     *
     * This method:
     * - Builds API payloads for invoice creation
     * - Sends them using HttpClient
     * - Performs only basic transport-level validation
     *
     * It does NOT:
     * - Generate invoice numbers
     * - Perform domain validation
     * - Interpret accounting rules
     *
     * @param list<\Bookkeeping\Model\ValueObject\InvoiceDraft> $invoices Invoice drafts to send.
     * @param \Cake\I18n\Date $invoicedMonth Invoiced month context.
     * @param \App\Model\Entity\AccountingProfile $accountingProfile Accounting profile and accounting context.
     * @return void
     */
    public function sendInvoices(
        array $invoices,
        Date $invoicedMonth,
        AccountingProfile $accountingProfile,
    ): void {
        foreach ($invoices as $invoice) {
            // 1) Build SalesInvoice payload
            $salesInvoice = $this->jsonRequestBuilder->buildSalesInvoice(
                $invoice,
                $invoicedMonth,
                $accountingProfile,
            );

            // 2) Send to API
            $response = $this->httpClient->send(
                'SalesInvoiceCreate',
                [
                    'SalesInvoice' => $salesInvoice,
                ],
            );

            // 3) Basic transport-level validation
            if (!$response->isOk()) {
                throw new RuntimeException(
                    __d(
                        'bookkeeping',
                        'Eurofaktura API error ({0}, {1})',
                        [
                            $response->getStatusCode(),
                            $response->getJson()['response']['description'] ?? 'Unknown error',
                        ],
                    ),
                );
            }

            $data = $response->getJson();

            // 4) API-level error handling
            if (!empty($data['error'])) {
                throw new RuntimeException(
                    __d(
                        'bookkeeping',
                        'Eurofaktura API error: {0}',
                        [$data['error']['message'] ?? 'Unknown error'],
                    ),
                );
            }

            // 5) (Optional) store documentId / external reference
            // $documentId = $data['result']['id'] ?? null;

            // sleep one second because of rate limit
            sleep(1);
        }
    }

    /**
     * Export invoices into a file-based format.
     *
     * This method exists primarily for:
     * - manual accounting workflows
     * - legacy integrations
     * - audit or archival purposes
     *
     * @param list<\Bookkeeping\Model\ValueObject\InvoiceDraft> $invoices Invoice drafts.
     * @param \Cake\I18n\Date $invoicedMonth Invoiced month context.
     * @param \App\Model\Entity\AccountingProfile $accountingProfile Accounting profile and accounting context.
     * @param \Bookkeeping\Model\Enum\InvoiceExportFormat $format Export format.
     * @return string Absolute file path.
     */
    public function exportInvoices(
        array $invoices,
        Date $invoicedMonth,
        AccountingProfile $accountingProfile,
        InvoiceExportFormat $format,
    ): string {
        throw new RuntimeException(
            __d(
                'bookkeeping',
                'Not implemented in Eurofaktura Provider.',
            ),
        );
    }

    /**
     * Import invoices from an external file.
     *
     * Used mainly for:
     * - historical data migration
     * - one-off imports
     * - reconciliation workflows
     *
     * @param string $filePath Path to the import file.
     * @param \Bookkeeping\Model\Enum\InvoiceImportFormat $format Import format.
     * @return list<\Bookkeeping\Model\ValueObject\InvoiceDraft>
     */
    public function importInvoices(string $filePath, InvoiceImportFormat $format): array
    {
        throw new RuntimeException(
            __d(
                'bookkeeping',
                'Not implemented in Eurofaktura Provider.',
            ),
        );
    }

    /**
     * Generate an invoice number according to provider rules.
     *
     * NOTE:
     * In API-driven systems like Eurofaktura, invoice numbers are often
     * generated by the remote system itself. This method may therefore
     * be a no-op or delegate to configuration.
     *
     * @param \Cake\I18n\DateTime $date Invoice date.
     * @param bool $reverseCharge Whether reverse charge applies.
     * @return string Generated invoice number.
     */
    public function generateInvoiceNumber(DateTime $date, bool $reverseCharge): string
    {
        throw new RuntimeException(
            __d(
                'bookkeeping',
                'Not implemented in Eurofaktura Provider.',
            ),
        );
    }

    /**
     * Get absolute path to the invoice PDF.
     *
     * Depending on provider capabilities, this may:
     * - return a locally cached file
     * - trigger a remote PDF download
     * - or throw if unsupported
     *
     * @param \Bookkeeping\Model\Entity\Invoice $invoice Invoice entity.
     * @return string Absolute path to the PDF file.
     */
    public function getInvoicePdfPath(Invoice $invoice): string
    {
        throw new RuntimeException(
            __d(
                'bookkeeping',
                'Not implemented in Eurofaktura Provider.',
            ),
        );
    }
}
