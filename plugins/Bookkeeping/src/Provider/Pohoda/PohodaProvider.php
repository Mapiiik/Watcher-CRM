<?php
declare(strict_types=1);

namespace Bookkeeping\Provider\Pohoda;

use App\Model\Entity\AccountingProfile;
use App\Model\Entity\Customer;
use Bookkeeping\Model\Entity\Invoice;
use Bookkeeping\Model\Enum\InvoiceExportFormat;
use Bookkeeping\Model\Enum\InvoiceImportFormat;
use Bookkeeping\Model\Enum\InvoiceSyncMode;
use Bookkeeping\Provider\AccountingProviderInterface;
use Cake\Core\Configure;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use RuntimeException;
use Settings\Utility\Settings;
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

    /**
     * The name the external IDs are written under, so the address book tells ours from any other
     * system's. Changing it orphans every partner card already sent.
     */
    public const EXTERNAL_SYSTEM = 'Watcher CRM';

    /**
     * How many customers go into one data pack: a run over thousands is then a series of answers
     * of a readable size, and a timeout costs one pack rather than the whole run.
     */
    private const PARTNERS_PER_PACK = 100;

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
     * Whether invoices are linked to the customer's partner card in the address book.
     *
     * @return bool
     */
    public static function useBuyerCode(): bool
    {
        return (bool)Settings::get(self::SETTINGS_ROOT . '.customers.use_buyer_code', false);
    }

    /**
     * The external ID the customer's partner card is found by in the address book.
     *
     * @param \App\Model\Entity\Customer $customer Customer.
     * @return array{ids: string, exSystemName: string}
     */
    public static function partnerExtId(Customer $customer): array
    {
        return [
            'ids' => Settings::getString(self::SETTINGS_ROOT . '.customers.code_prefix', '') . $customer->number,
            'exSystemName' => self::EXTERNAL_SYSTEM,
        ];
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

        // 4) Validate Pohoda XML response state
        $this->validResponse($xml);

        // 5) Parse invoices
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
        // 0) Write the customers into the address book first, when invoices are linked to it -
        //    in one import for the whole run, as the invoices go in one too. A failure ends the
        //    run here: the invoices would point at partner cards that may not be there.
        if (self::useBuyerCode()) {
            $customers = [];
            foreach ($invoices as $invoice) {
                if ($invoice->customer !== null) {
                    $customers[$invoice->customer->id] = $invoice->customer;
                }
            }

            if ($customers !== []) {
                $this->sendPartners(array_values($customers));
            }
        }

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

            // 5) Validate Pohoda response state
            $this->validResponse($responseXml);

            // 6) The pack being accepted says nothing of each invoice in it: one refused on its
            //    own is reported here rather than left missing from the accounting system
            $failures = $this->xmlParser->parseImportFailures($responseXml);

            if ($failures !== []) {
                throw new RuntimeException(__d(
                    'bookkeeping',
                    'Pohoda refused these invoices: {0}',
                    $this->describeFailures($failures),
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
     * Send partners (customers) into the Pohoda address book via mServer.
     *
     * Each customer is added, or updated where their partner card is already there, found by
     * its external ID. The customers go in packs; a customer the address book refuses does not
     * stop the others, and every refusal is reported together once the run is through.
     *
     * @param list<\App\Model\Entity\Customer> $customers Customers to send.
     * @return void
     * @throws \RuntimeException When mServer cannot be reached or refuses any customer.
     */
    public function sendPartners(array $customers): void
    {
        // the customer says whether their partner card is ours to write; an invoice for them
        // still goes out, only this push in front of it is left undone
        $customers = array_values(array_filter(
            $customers,
            function (Customer $customer): bool {
                if ($customer->sync_to_accounting) {
                    return true;
                }

                Log::info(
                    'Partner sync skipped for customer ' . $customer->number
                    . ': synchronization to the accounting system is turned off.',
                );

                return false;
            },
        ));

        $failures = [];

        foreach (array_chunk($customers, self::PARTNERS_PER_PACK) as $pack) {
            // 1) Build XML request
            $xmlRequest = $this->xmlRequestBuilder->buildPartnersRequest($pack);

            // 2) Send it, and let an unreachable mServer end the run
            /** @var \SimpleXMLElement $responseXml */
            $responseXml = $this->httpClient->send($xmlRequest)->orFail(
                __d('bookkeeping', 'Pohoda mServer is not configured.'),
            );

            // 3) Validate Pohoda response state
            $this->validResponse($responseXml);

            // 4) Collect the customers refused one by one
            $failures += $this->xmlParser->parseImportFailures($responseXml);
        }

        if ($failures !== []) {
            throw new RuntimeException(__d(
                'bookkeeping',
                'Pohoda refused these customers: {0}',
                $this->describeFailures($failures),
            ));
        }
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

    /**
     * Refuse a response whose pack mServer itself marked as failed.
     *
     * @param mixed $xml Response body.
     * @return void
     * @throws \RuntimeException When the response is not XML or its state is not ok.
     */
    private function validResponse(mixed $xml): void
    {
        if (!$xml instanceof SimpleXMLElement) {
            throw new RuntimeException(
                __d('bookkeeping', 'Invalid XML response from Pohoda mServer.'),
            );
        }

        $attributes = $xml->attributes();
        $state = isset($attributes['state']) ? (string)$attributes['state'] : 'N/A';
        $note = isset($attributes['note']) ? (string)$attributes['note'] : 'N/A';

        if ($state !== 'ok') {
            throw new RuntimeException(__d(
                'bookkeeping',
                'Pohoda mServer returned an error response (STATE: {0}, NOTE: {1})',
                [$state, $note],
            ));
        }
    }

    /**
     * Put the refused items into one line for the log and the error report.
     *
     * @param array<array-key, string> $failures What Pohoda said, by item ID.
     * @return string
     */
    private function describeFailures(array $failures): string
    {
        $lines = [];
        foreach ($failures as $id => $note) {
            $lines[] = $id . ' (' . $note . ')';
        }

        return implode('; ', $lines);
    }
}
