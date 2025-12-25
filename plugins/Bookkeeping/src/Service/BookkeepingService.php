<?php
declare(strict_types=1);

namespace Bookkeeping\Service;

use App\Model\Entity\TaxRate;
use Bookkeeping\Model\Entity\Invoice;
use Bookkeeping\Model\Enum\InvoiceExportFormat;
use Bookkeeping\Model\Enum\InvoiceImportFormat;
use Bookkeeping\Provider\Pohoda\PohodaProvider;
use Cake\I18n\Date;
use Cake\I18n\DateTime;

/**
 * BookkeepingService
 *
 * This service acts as a stable API layer between the CRM
 * and the underlying accounting system provider.
 *
 * Controllers and CLI commands should call this service,
 * not the provider directly.
 *
 * The provider can be replaced (Pohoda, Eracuni, Flexibee, ...)
 * without changing the rest of the application.
 */
class BookkeepingService
{
    private PohodaProvider $provider;
    private InvoiceMapperService $invoiceMapper;

    /**
     * Constructor
     *
     * @param \Bookkeeping\Provider\Pohoda\PohodaProvider|null $provider
     *   Allows injecting a different provider in the future.
     */
    public function __construct(?PohodaProvider $provider = null)
    {
        $this->provider = $provider ?? new PohodaProvider();
        $this->invoiceMapper = new InvoiceMapperService();
    }

    /**
     * Synchronize invoices from the accounting system.
     *
     * @param \Cake\I18n\DateTime $lastChanges
     * @return array{imported:int, created:int, modified:int, skipped:int} Import results.
     */
    public function syncInvoices(DateTime $lastChanges): array
    {
        // Load and parse invoices
        $drafts = $this->provider->syncInvoices($lastChanges);

        // Map and save into CRM
        return $this->invoiceMapper->mapAndSave($drafts);
    }

    /**
     * Send invoices to the accounting system.
     *
     * @param list<\Bookkeeping\Model\ValueObject\InvoiceDraft> $invoices List of invoice drafts.
     * @param \Cake\I18n\Date $invoicedMonth Invoiced month used in XML header.
     * @param \App\Model\Entity\TaxRate $taxRate Tax rate and accounting context.
     * @return void
     */
    public function sendInvoices(
        array $invoices,
        Date $invoicedMonth,
        TaxRate $taxRate,
    ): void {
        return $this->provider->sendInvoices($invoices, $invoicedMonth, $taxRate);
    }

    /**
     * Export invoices (DBF/XML) for the accounting system.
     *
     * @param list<\Bookkeeping\Model\ValueObject\InvoiceDraft> $invoices List of invoice drafts.
     * @param \Bookkeeping\Model\Enum\InvoiceExportFormat $format Export format.
     * @param \Cake\I18n\Date $invoicedMonth Invoiced month used in XML header.
     * @param \App\Model\Entity\TaxRate $taxRate Tax rate and accounting context.
     * @return string File path.
     */
    public function exportInvoices(
        array $invoices,
        Date $invoicedMonth,
        TaxRate $taxRate,
        InvoiceExportFormat $format,
    ): string {
        return $this->provider->exportInvoices($invoices, $invoicedMonth, $taxRate, $format);
    }

    /**
     * Import invoices from the accounting system (DBF).
     *
     * @param string $filePath
     * @param \Bookkeeping\Model\Enum\InvoiceImportFormat $format Import format.
     * @return array{imported:int, created:int, modified:int, skipped:int} Import results.
     */
    public function importInvoices(string $filePath, InvoiceImportFormat $format): array
    {
        // Parse invoices
        $drafts = $this->provider->importInvoices($filePath, $format);

        // Map and save into CRM
        return $this->invoiceMapper->mapAndSave($drafts);
    }

    /**
     * Generate invoice number according to provider rules.
     *
     * @param \Cake\I18n\DateTime $date
     * @param bool $reverseCharge
     * @return string
     */
    public function generateInvoiceNumber(DateTime $date, bool $reverseCharge): string
    {
        return $this->provider->generateInvoiceNumber($date, $reverseCharge);
    }

    /**
     * Get the file path to the invoice PDF.
     *
     * @return string Absolute path to the PDF file.
     */
    public function getInvoicePdfPath(Invoice $invoice): string
    {
        return $this->provider->getInvoicePdfPath($invoice);
    }
}
