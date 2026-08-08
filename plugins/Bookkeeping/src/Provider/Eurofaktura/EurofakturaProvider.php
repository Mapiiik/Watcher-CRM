<?php
declare(strict_types=1);

namespace Bookkeeping\Provider\Eurofaktura;

use App\Model\Entity\AccountingProfile;
use App\Model\Table\CustomersTable;
use Bookkeeping\Model\Entity\Invoice;
use Bookkeeping\Model\Enum\InvoiceExportFormat;
use Bookkeeping\Model\Enum\InvoiceImportFormat;
use Bookkeeping\Model\Enum\InvoiceSyncMode;
use Bookkeeping\Provider\AccountingProviderInterface;
use Cake\Core\Configure;
use Cake\Http\Client\Response;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use RuntimeException;
use Settings\Utility\Settings;

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
    use LocatorAwareTrait;

    public const SETTINGS_ROOT = 'bookkeeping.accounting.providers.eurofaktura';

    private EurofakturaCredentialsProvider $credentialsProvider;

    private HttpClient $httpClient;

    private JsonParser $jsonParser;

    private JsonRequestBuilder $jsonRequestBuilder;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->credentialsProvider = new EurofakturaCredentialsProvider();
        $this->httpClient = new HttpClient();
        $this->jsonParser = new JsonParser();
        $this->jsonRequestBuilder = new JsonRequestBuilder();
    }

    /**
     * Assert that an API response from Eurofaktura is valid.
     *
     * This method performs a unified validation of the API response by checking:
     * - transport-level success (HTTP status, connectivity)
     * - API-level errors returned in the response payload
     *
     * It does NOT:
     * - return parsed data
     * - perform any domain-level interpretation
     *
     * On failure, a RuntimeException is thrown with a descriptive, localized message.
     * On success, the method returns silently.
     *
     * This helper centralizes error handling logic for all Eurofaktura API calls
     * and ensures consistent behavior across provider methods.
     *
     * @param \Cake\Http\Client\Response $response HTTP API response to validate.
     * @return void
     * @throws \RuntimeException When the response indicates a transport or API-level error.
     */
    private function assertValidApiResponse(Response $response): void
    {
        // Transport-level validation
        if (!$response->isOk()) {
            $description = $response->getJson()['response']['description'] ?? __d('bookkeeping', 'Unknown error');
            $description = str_replace(["\r", "\n"], ' ', $description);

            throw new RuntimeException(
                __d(
                    'bookkeeping',
                    'Eurofaktura API error ({0}, {1})',
                    [
                        $response->getStatusCode(),
                        $description,
                    ],
                ),
            );
        }

        $data = $response->getJson();

        // API-level error handling
        if (!empty($data['error'])) {
            throw new RuntimeException(
                __d(
                    'bookkeeping',
                    'Eurofaktura API error: {0}',
                    [$data['error']['message'] ?? __d('bookkeeping', 'Unknown error')],
                ),
            );
        }
    }

    /**
     * Synchronize invoices from Eurofaktura / E-racuni.
     *
     * Fetches invoices changed since the given timestamp and maps them
     * into internal InvoiceDraft value objects.
     *
     * @param \Bookkeeping\Model\Enum\InvoiceSyncMode $mode Synchronization mode
     * @param \Cake\I18n\DateTime $lastChanges Timestamp of last successful sync.
     * @return list<\Bookkeeping\Model\ValueObject\InvoiceDraft>
     */
    public function syncInvoices(InvoiceSyncMode $mode, DateTime $lastChanges): array
    {
        return match ($mode) {
            InvoiceSyncMode::DELTA => $this->syncInvoicesDelta($lastChanges),
            InvoiceSyncMode::FULL => $this->syncInvoicesFull(),
        };
    }

    /**
     * DELTA sync – invoices paid since last synchronization.
     *
     * @return list<\Bookkeeping\Model\ValueObject\InvoiceDraft>
     */
    private function syncInvoicesDelta(DateTime $lastChanges): array
    {
        $response = $this->httpClient->send(
            $this->credentialsProvider->getDefault(),
            'SalesInvoiceList',
            [
                'issuedTimestampFrom' => $lastChanges
                    ->subSeconds(1)
                    ->format('Y-m-d\TH:i:s'),
            ],
        );

        $drafts = $this->jsonParser->parseSalesInvoiceList($response->getJson());

        // Eurofaktura rate limit
        sleep(1);

        $response = $this->httpClient->send(
            $this->credentialsProvider->getDefault(),
            'SalesInvoiceList',
            [
                'dateOfFullPaymentFrom' => $lastChanges->format('Y-m-d'),
            ],
        );

        return array_merge(
            $drafts,
            $this->jsonParser->parseSalesInvoiceList($response->getJson()),
        );
    }

    /**
     * FULL sync – iterate over all CRM customers and fetch invoices per buyer.
     *
     * @return list<\Bookkeeping\Model\ValueObject\InvoiceDraft>
     */
    private function syncInvoicesFull(): array
    {
        $customersTable = $this->fetchTable(CustomersTable::class);
        /** @var \Cake\Datasource\ResultSetInterface<array-key, \App\Model\Entity\Customer> $customers */
        $customers = $customersTable
            ->find()
            ->select(['nid'])
            ->orderBy([
                'Customers.nid' => 'ASC',
            ])
            ->all();

        $buyerCodePrefix = Settings::getString(
            self::SETTINGS_ROOT . '.customers.code_prefix',
            'CRM-',
        );

        $drafts = [];

        foreach ($customers as $customer) {
            $buyerCode = $buyerCodePrefix . $customer->number;

            $response = $this->httpClient->send(
                $this->credentialsProvider->getDefault(),
                'SalesInvoiceList',
                [
                    'buyer' => $buyerCode,
                ],
            );

            $drafts = array_merge(
                $drafts,
                $this->jsonParser->parseSalesInvoiceList($response->getJson()),
            );

            // Eurofaktura rate limit
            sleep(1);
        }

        return $drafts;
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
            try {
                $useBuyerCode = (bool)Settings::get(
                    EurofakturaProvider::SETTINGS_ROOT . '.customers.use_buyer_code',
                    false,
                );
                $sendIssuedInvoiceByEmail = (bool)Settings::get(
                    EurofakturaProvider::SETTINGS_ROOT . '.invoice.send_issued_invoice_by_email',
                    false,
                );

                // 1) Send partner to API (if use_buyer_code is set)
                if ($useBuyerCode && $invoice->customer !== null) {
                    $this->sendPartners([$invoice->customer]);
                }

                // 2) Build SalesInvoice payload
                $salesInvoice = $this->jsonRequestBuilder->buildSalesInvoice(
                    $invoice,
                    $invoicedMonth,
                    $accountingProfile,
                );

                // 3) Send to API
                $response = $this->httpClient->send(
                    $this->credentialsProvider->getForInvoiceIssuing(),
                    'SalesInvoiceCreate',
                    [
                        'SalesInvoice' => $salesInvoice,
                        'sendIssuedInvoiceByEmail' => $sendIssuedInvoiceByEmail,
                    ],
                );

                // 4) Validate response
                $this->assertValidApiResponse($response);
            } catch (RuntimeException $e) {
                throw new RuntimeException(__d(
                    'bookkeeping',
                    'Failed to send invoice for customer {0}: {1}',
                    [
                        $invoice->customer->number ?? __d('bookkeeping', 'Unknown'),
                        $e->getMessage(),
                    ],
                ), $e->getCode(), previous: $e);
            }

            // 5) (Optional) store documentId / external reference
            // $data = $response->getJson();
            // $documentId = $data['result']['id'] ?? null;

            // sleep two seconds because of rate limit
            sleep(2);
        }
    }

    /**
     * Send partners (customers) to Eurofaktura / E-racuni via API.
     *
     * This method:
     * - Builds API payloads for partner creation/update
     * - Sends them using HttpClient
     * - Performs only basic transport-level validation
     *
     * It does NOT:
     * - Interpret customer data
     * - Perform domain validation
     * - Resolve duplicates or conflicts
     *
     * @param list<\App\Model\Entity\Customer> $customers Customers to send.
     * @return void
     */
    public function sendPartners(array $customers): void
    {
        foreach ($customers as $customer) {
            try {
                // 1) Build Partner payload
                $partner = $this->jsonRequestBuilder->buildPartner($customer);

                // 2) Send to API
                $response = $this->httpClient->send(
                    $this->credentialsProvider->getForInvoiceIssuing(),
                    'PartnerImport',
                    [
                        'partner' => $partner,
                    ],
                );

                // 3) Validate response
                $this->assertValidApiResponse($response);

                // 4) (Optional) store documentId / external reference
                // $data = $response->getJson();
                // $partnerId = $data['result']['id'] ?? null;
            } catch (RuntimeException $e) {
                throw new RuntimeException(__d(
                    'bookkeeping',
                    'Failed to send partner for customer {0}: {1}',
                    [
                        $customer->number ?? __d('bookkeeping', 'Unknown'),
                        $e->getMessage(),
                    ],
                ), $e->getCode(), previous: $e);
            }

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
     * Get the file path to the invoice PDF.
     *
     * Depending on state:
     * - return a locally cached file
     * - trigger a remote PDF download
     *
     * @param \Bookkeeping\Model\Entity\Invoice $invoice Invoice entity.
     * @return string Absolute path to the PDF file.
     */
    public function getInvoicePdfPath(Invoice $invoice): string
    {
        $filepath = Configure::read('Data.root')
            . DS . 'invoices'
            . DS . 'Invoice_' . strtr($invoice->number, '/', '-')
            . '_' . $invoice->creation_date->format('Y-m-d') . '.pdf';

        // 1) Check local cache
        if (file_exists($filepath)) {
            return $filepath;
        }

        // 2) Attempt to download from Eurofaktura API
        if (!empty($invoice->accounting_identifier)) {
            // Sleep one second because of rate limit
            sleep(1);

            // Attempt to download from Eurofaktura API
            $response = $this->httpClient->send(
                $this->credentialsProvider->getDefault(),
                'SalesInvoiceGetPDF',
                [
                    'documentID' => $invoice->accounting_identifier,
                ],
            );

            // Validate response
            $this->assertValidApiResponse($response);

            // Check for PDF data
            $data = $response->getJson();

            if (isset($data['response']['result']['pdfFile'])) {
                // Decode base64 PDF content
                $pdfContent = base64_decode((string)$data['response']['result']['pdfFile'], true);

                if ($pdfContent === false) {
                    throw new RuntimeException(
                        __d(
                            'bookkeeping',
                            'Invalid PDF data received from Eurofaktura API.',
                        ),
                    );
                }

                // Save to file
                file_put_contents($filepath, $pdfContent);

                return $filepath;
            }
        }

        throw new RuntimeException(
            __d(
                'bookkeeping',
                'Invoice PDF not found or could not be downloaded: {0}',
                [$filepath],
            ),
        );
    }
}
