<?php
declare(strict_types=1);

namespace Bookkeeping\Provider;

use App\Model\Entity\AccountingProfile;
use Bookkeeping\Model\Entity\Invoice;
use Bookkeeping\Model\Enum\InvoiceExportFormat;
use Bookkeeping\Model\Enum\InvoiceImportFormat;
use Bookkeeping\Model\Enum\InvoiceSyncMode;
use Cake\I18n\Date;
use Cake\I18n\DateTime;

/**
 * Interface AccountingProviderInterface
 *
 * Defines a common contract for accounting system providers
 * (e.g. Eurofaktura / E-racuni, Pohoda, etc.).
 *
 * Implementations of this interface act as orchestration layers
 * between the domain (BookkeepingService) and external accounting systems.
 *
 * Providers:
 * - orchestrate data exchange
 * - delegate transport to HTTP clients
 * - delegate payload construction to builders
 *
 * Providers MUST NOT:
 * - contain business logic
 * - calculate prices or VAT
 * - interpret accounting rules
 */
interface AccountingProviderInterface
{
    /**
     * Synchronize invoices from the accounting system.
     *
     * Fetches invoices changed since the given timestamp and maps them
     * into internal InvoiceDraft value objects.
     *
     * @param \Bookkeeping\Model\Enum\InvoiceSyncMode $mode Synchronization mode
     * @param \Cake\I18n\DateTime $lastChanges Timestamp of last successful sync.
     * @return list<\Bookkeeping\Model\ValueObject\InvoiceDraft>
     */
    public function syncInvoices(InvoiceSyncMode $mode, DateTime $lastChanges): array;

    /**
     * Send invoices to the accounting system.
     *
     * This method:
     * - builds provider-specific payloads
     * - sends them via the provider transport layer
     * - performs only basic transport-level validation
     *
     * It does NOT:
     * - generate invoice numbers
     * - perform domain validation
     * - interpret accounting rules
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
    ): void;

    /**
     * Send partners (customers) to the accounting system.
     *
     * This method:
     * - builds provider-specific partner payloads
     * - sends them via the provider transport layer
     * - performs only basic transport-level validation
     *
     * It does NOT:
     * - interpret customer data
     * - perform domain validation
     * - resolve duplicates or conflicts
     *
     * @param list<\App\Model\Entity\Customer> $customers Customers to send.
     * @return void
     */
    public function sendPartners(array $customers): void;

    /**
     * Export invoices into a file-based format.
     *
     * Intended mainly for:
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
    ): string;

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
    public function importInvoices(
        string $filePath,
        InvoiceImportFormat $format,
    ): array;

    /**
     * Generate an invoice number according to provider rules.
     *
     * NOTE:
     * In API-driven systems, invoice numbers are often generated
     * by the remote system itself. Implementations may therefore
     * return an empty string or delegate to configuration.
     *
     * @param \Cake\I18n\DateTime $date Invoice date.
     * @param bool $reverseCharge Whether reverse charge applies.
     * @return string Generated invoice number.
     */
    public function generateInvoiceNumber(
        DateTime $date,
        bool $reverseCharge,
    ): string;

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
    public function getInvoicePdfPath(Invoice $invoice): string;
}
