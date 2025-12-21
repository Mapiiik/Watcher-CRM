<?php
declare(strict_types=1);

namespace Bookkeeping\Provider\Pohoda;

use Bookkeeping\Model\Entity\Invoice;
use Cake\I18n\DateTime;
use RuntimeException;

/**
 * Class PohodaProvider
 *
 * Main orchestration layer for the Pohoda accounting system.
 * This class delegates actual work to helper classes such as:
 * - XmlRequestBuilder
 * - XmlParser
 * - InvoiceMapper
 * - DbfExporter
 * - DbfImporter
 * - PdfLocator
 *
 * The provider exposes a stable API used by BookkeepingService.
 */
class PohodaProvider
{
    private XmlRequestBuilder $xmlRequestBuilder;
    private HttpClient $httpClient;
    private XmlParser $xmlParser;
    private InvoiceMapper $invoiceMapper;

    /**
     * Contructor
     */
    public function __construct()
    {
        $this->xmlRequestBuilder = new XmlRequestBuilder();
        $this->httpClient = new HttpClient();
        $this->xmlParser = new XmlParser();
        $this->invoiceMapper = new InvoiceMapper();
    }

    /**
     * Synchronize invoices from Pohoda (mServer).
     *
     * @param \Cake\I18n\DateTime $lastChanges Timestamp of last successful sync.
     * @return array Parsed and mapped invoice data.
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
        $parsedInvoices = $this->xmlParser->parseInvoices($xml);

        // Map and save into CRM
        $results = $this->invoiceMapper->mapAndSave($parsedInvoices);

        return $results;
    }

    /**
     * Export invoices to Pohoda (DBF/XML).
     *
     * @param array $invoices List of invoice entities.
     * @param array $options Additional export options.
     * @return array|string File path or export data.
     */
    public function exportInvoices(array $invoices, array $options = []): array|string
    {
        return '';
    }

    /**
     * Import invoices from Pohoda (DBF).
     *
     * @param string $filePath Path to DBF file.
     * @return array Import results.
     */
    public function importInvoices(string $filePath): array
    {
        return [];
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
