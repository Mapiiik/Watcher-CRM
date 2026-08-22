<?php
declare(strict_types=1);

namespace Bookkeeping\Provider\Pohoda;

use App\Model\Entity\AccountingProfile;
use Bookkeeping\Model\Entity\Invoice;
use Bookkeeping\Model\Enum\InvoiceExportFormat;
use Bookkeeping\Model\Enum\InvoiceImportFormat;
use Bookkeeping\Model\Enum\InvoiceSyncMode;
use Bookkeeping\Provider\AccountingProviderInterface;
use Cake\Core\Configure;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use RuntimeException;
use SimpleXMLElement;

/**
 * Class PohodaProvider
 *
 * Main orchestration layer for the Pohoda accounting system.
 * This class delegates actual work to helper classes such as:
 * - XmlRequestBuilder
 * - HttpClient
 * - DbfParser
 * - XmlParser
 * - DbfExporter
 * - XmlExporter
 *
 * The provider exposes a stable API used by BookkeepingService.
 */
class PohodaProvider implements AccountingProviderInterface
{
    public const SETTINGS_ROOT = 'bookkeeping.accounting.providers.pohoda';

    private readonly XmlRequestBuilder $xmlRequestBuilder;

    private readonly HttpClient $httpClient;

    private readonly DbfParser $dbfParser;

    private readonly XmlParser $xmlParser;

    private readonly DbfExporter $dbfExporter;

    private readonly XmlExporter $xmlExporter;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->xmlRequestBuilder = new XmlRequestBuilder();
        $this->httpClient = new HttpClient();
        $this->dbfParser = new DbfParser();
        $this->xmlParser = new XmlParser();
        $this->dbfExporter = new DbfExporter();
        $this->xmlExporter = new XmlExporter();
    }

    /**
     * Synchronize invoices from Pohoda (mServer).
     *
     * @param \Bookkeeping\Model\Enum\InvoiceSyncMode $mode Synchronization mode
     * @param \Cake\I18n\DateTime $lastChanges Timestamp of last successful sync.
     * @return list<\Bookkeeping\Model\ValueObject\InvoiceDraft>
     */
    public function syncInvoices(InvoiceSyncMode $mode, DateTime $lastChanges): array
    {
        // 1) Build XML request
        $xmlRequest = $this->xmlRequestBuilder->buildSyncRequest($lastChanges);

        // 2) Send request to Pohoda mServer
        // 3) Ask, and let a failure end the run - a sync that read half the invoices would
        //    look like the accounting system having lost the rest
        /** @var \SimpleXMLElement $xml */
        $xml = $this->httpClient->send($xmlRequest)->orFail(
            __d('bookkeeping', 'Pohoda mServer is not configured.'),
        );

        // 4) Parse XML
        if (!$xml instanceof SimpleXMLElement) {
            throw new RuntimeException(
                __d('bookkeeping', 'Invalid XML response from Pohoda mServer.'),
            );
        }

        // 5) Validate Pohoda XML response state
        $attributes = $xml->attributes();

        $state = property_exists($attributes, 'state') && $attributes->state !== null ?
            (string)$attributes->state : 'N/A';
        $note = property_exists($attributes, 'note') && $attributes->note !== null ?
            (string)$attributes->note : 'N/A';

        if ($state !== 'ok') {
            throw new RuntimeException(__d(
                'bookkeeping',
                'Pohoda mServer returned an error response (STATE: {0}, NOTE: {1})',
                [$state, $note],
            ));
        }

        // 6) Parse invoices
        $drafts = $this->xmlParser->parseSimpleXML($xml);

        return $drafts;
    }

    /**
     * Send invoices directly to Pohoda via mServer using XML import.
     *
     * This method generates a Pohoda-compatible XML import file
     * using XmlExporter and immediately sends it to the Pohoda mServer
     * via HttpClient.
     *
     * The XML file is treated as a temporary transport artifact and
     * is removed after successful transmission.
     *
     * Responsibilities:
     * - Orchestrates XML generation and delivery
     * - Does NOT perform XML generation logic
     * - Does NOT handle HTTP response parsing beyond basic validation
     *
     * @param list<\Bookkeeping\Model\ValueObject\InvoiceDraft> $invoices Invoice drafts to send.
     * @param \Cake\I18n\Date $invoicedMonth Invoiced month used in XML header.
     * @param \App\Model\Entity\AccountingProfile $accountingProfile Accounting profile and accounting context.
     * @return void
     * @throws \RuntimeException When XML generation or mServer communication fails.
     */
    public function sendInvoices(
        array $invoices,
        Date $invoicedMonth,
        AccountingProfile $accountingProfile,
    ): void {
        // Generate temporary XML file path
        $filePath = TMP . uniqid('pohoda-import-', true) . '.xml';

        try {
            // 1) Generate Pohoda-compatible XML import file
            $this->xmlExporter->export(
                $invoices,
                $invoicedMonth,
                $accountingProfile,
                $filePath,
            );

            // 2) Load XML content from generated file
            $xml = file_get_contents($filePath);
            if ($xml === false) {
                throw new RuntimeException(
                    __d('bookkeeping', 'Failed to read generated XML file.'),
                );
            }

            // 3) Send XML to Pohoda mServer
            // 4) Ask, and let a failure end the run
            /** @var \SimpleXMLElement $responseXml */
            $responseXml = $this->httpClient->send($xml)->orFail(
                __d('bookkeeping', 'Pohoda mServer is not configured.'),
            );

            // 5) Validate XML response body
            if (!$responseXml instanceof SimpleXMLElement) {
                throw new RuntimeException(
                    __d('bookkeeping', 'Invalid XML response from Pohoda mServer.'),
                );
            }

            // 6) Validate Pohoda response state
            $attributes = $responseXml->attributes();

            $state = property_exists($attributes, 'state') && $attributes->state !== null ?
                (string)$attributes->state : 'N/A';
            $note = property_exists($attributes, 'note') && $attributes->note !== null ?
                (string)$attributes->note : '';

            if ($state !== 'ok') {
                throw new RuntimeException(__d(
                    'bookkeeping',
                    'Pohoda mServer returned an error response (STATE: {0}, NOTE: {1})',
                    [$state, $note],
                ));
            }
        } finally {
            // Always remove temporary XML file
            if (is_file($filePath)) {
                unlink($filePath);
            }
        }
    }

    /**
     * Send partners (customers) to Pohoda.
     *
     * NOTE:
     * Pohoda provider currently does not support partner synchronization.
     *
     * @param list<\App\Model\Entity\Customer> $customers Customers to send.
     * @return void
     */
    public function sendPartners(array $customers): void
    {
        throw new RuntimeException(
            __d(
                'bookkeeping',
                'Partner synchronization is not implemented in Pohoda Provider.',
            ),
        );
    }

    /**
     * Export invoices to Pohoda (DBF/XML).
     *
     * @param list<\Bookkeeping\Model\ValueObject\InvoiceDraft> $invoices List of invoice drafts.
     * @param \Bookkeeping\Model\Enum\InvoiceExportFormat $format Export format.
     * @param \Cake\I18n\Date $invoicedMonth Invoiced month used in XML header.
     * @param \App\Model\Entity\AccountingProfile $accountingProfile Accounting profile and accounting context.
     * @return string File path.
     */
    public function exportInvoices(
        array $invoices,
        Date $invoicedMonth,
        AccountingProfile $accountingProfile,
        InvoiceExportFormat $format,
    ): string {
        // Export invoices
        $filePath = match ($format) {
            InvoiceExportFormat::DBF =>
                $this->dbfExporter->export(
                    $invoices,
                    $invoicedMonth,
                    $accountingProfile,
                    TMP . uniqid('invoices-', true) . '.dbf',
                ),

            InvoiceExportFormat::XML =>
                $this->xmlExporter->export(
                    $invoices,
                    $invoicedMonth,
                    $accountingProfile,
                    TMP . uniqid('invoices-', true) . '.xml',
                ),
        };

        return $filePath;
    }

    /**
     * Import invoices from Pohoda (DBF/XML).
     *
     * @param string $filePath Path to DBF file.
     * @param \Bookkeeping\Model\Enum\InvoiceImportFormat $format Import format.
     * @return list<\Bookkeeping\Model\ValueObject\InvoiceDraft>
     */
    public function importInvoices(string $filePath, InvoiceImportFormat $format): array
    {
        // Parse invoices
        $drafts = match ($format) {
            InvoiceImportFormat::DBF =>
                $this->dbfParser->parseFile($filePath),

            InvoiceImportFormat::XML =>
                $this->xmlParser->parseFile($filePath),
        };

        return $drafts;
    }

    /**
     * Generate invoice number according to Pohoda rules.
     *
     * @param \Cake\I18n\DateTime $date Invoice date.
     * @param bool $reverseCharge Whether reverse charge applies.
     * @return string Generated invoice number.
     */
    public function generateInvoiceNumber(DateTime $date, bool $reverseCharge): string
    {
        return '';
    }

    /**
     * Get the file path to the invoice PDF.
     *
     * @return string Absolute path to the PDF file.
     */
    public function getInvoicePdfPath(Invoice $invoice): string
    {
        return Configure::read('Data.root')
            . DS . 'invoices'
            . DS . 'Faktura_' . $invoice->number . '.pdf';
    }
}
