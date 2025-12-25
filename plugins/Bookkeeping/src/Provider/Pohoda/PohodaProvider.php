<?php
declare(strict_types=1);

namespace Bookkeeping\Provider\Pohoda;

use Bookkeeping\Model\Entity\Invoice;
use Bookkeeping\Model\Enum\InvoiceExportFormat;
use Bookkeeping\Model\Enum\InvoiceImportFormat;
use Cake\I18n\DateTime;
use RuntimeException;

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
class PohodaProvider
{
    private XmlRequestBuilder $xmlRequestBuilder;
    private HttpClient $httpClient;
    private DbfParser $dbfParser;
    private XmlParser $xmlParser;
    private DbfExporter $dbfExporter;
    private XmlExporter $xmlExporter;

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
     * @param \Cake\I18n\DateTime $lastChanges Timestamp of last successful sync.
     * @return list<\Bookkeeping\Model\ValueObject\InvoiceDraft>
     */
    public function syncInvoices(DateTime $lastChanges): array
    {
        // 1) Build XML request
        $xmlRequest = $this->xmlRequestBuilder->buildSyncRequest($lastChanges);

        // 2) Send request to Pohoda mServer
        $response = $this->httpClient->send($xmlRequest);

        // 3) Validate response
        if (!$response->isOk()) {
            throw new RuntimeException(__d(
                'bookkeeping',
                'Invalid response from the server ({0})',
                [$response->getReasonPhrase()],
            ));
        }

        // 4) Parse XML
        $xml = $response->getXml();
        if ($xml === null) {
            throw new RuntimeException(__d(
                'bookkeeping',
                'Invalid XML response',
            ));
        }

        // 5) Validate Pohoda XML response state
        $attributes = $xml->attributes();

        $id = isset($attributes->id) ? (string)$attributes->id : 'N/A';
        $state = isset($attributes->state) ? (string)$attributes->state : 'N/A';
        $note = isset($attributes->note) ? (string)$attributes->note : 'N/A';

        if ($state !== 'ok') {
            throw new RuntimeException(__d(
                'bookkeeping',
                'The server returned an XML error response (ID: {0}, STATE: {1}, NOTE: {2})',
                [$id, $state, $note],
            ));
        }

        // 6) Parse invoices
        $drafts = $this->xmlParser->parseSimpleXML($xml);

        return $drafts;
    }

    /**
     * Export invoices to Pohoda (DBF/XML).
     *
     * @param list<\Bookkeeping\Model\ValueObject\InvoiceDraft> $invoices List of invoice drafts.
     * @param \Bookkeeping\Model\Enum\InvoiceExportFormat $format Export format.
     * @param array{
     *   invoicedMonth:\Cake\I18n\Date,
     *   taxRate:\App\Model\Entity\TaxRate
     * } $options
     * @return string File path.
     */
    public function exportInvoices(array $invoices, InvoiceExportFormat $format, array $options): string
    {
        // Export invoices
        $filePath = match ($format) {
            InvoiceExportFormat::DBF =>
                $this->dbfExporter->export(
                    $invoices,
                    $options['invoicedMonth'],
                    $options['taxRate'],
                    TMP . uniqid('invoices-', true) . '.dbf',
                ),

            InvoiceExportFormat::XML =>
                $this->xmlExporter->export(
                    $invoices,
                    $options['invoicedMonth'],
                    $options['taxRate'],
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
        return (string)env('DATA_ROOT', ROOT . DS . 'data')
            . DS . 'invoices'
            . DS . 'Faktura_' . $invoice->number . '.pdf';
    }
}
