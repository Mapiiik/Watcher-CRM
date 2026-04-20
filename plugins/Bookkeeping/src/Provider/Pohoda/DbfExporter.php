<?php
declare(strict_types=1);

namespace Bookkeeping\Provider\Pohoda;

use App\Model\Entity\AccountingProfile;
use App\Model\Entity\Billing;
use Bookkeeping\Model\ValueObject\InvoiceDraft;
use Cake\I18n\Date;
use RuntimeException;

/**
 * Class DbfExporter
 *
 * Generates DBF files compatible with the Pohoda accounting system.
 *
 * This exporter is responsible only for transforming InvoiceDraft objects
 * into a DBF file. It does not handle HTTP responses, downloads, nor
 * filesystem cleanup.
 *
 * Notes:
 * - Output charset is CP852 (matches legacy behavior).
 * - DBF structure/columns match the legacy DBFInvoices implementation.
 */
class DbfExporter
{
    /**
     * DBF output charset.
     */
    private const CHARSET = 'CP852';

    /**
     * Export invoices into a Pohoda-compatible DBF file.
     *
     * @param list<\Bookkeeping\Model\ValueObject\InvoiceDraft> $invoices Invoice drafts to export.
     * @param \Cake\I18n\Date $invoicedMonth Invoiced month (kept for API symmetry; not required for DBF content).
     * @param \App\Model\Entity\AccountingProfile $accountingProfile Accounting profile context (VAT and reverse charge rules).
     * @param string $filePath Target DBF file path.
     * @return string Final DBF file path.
     */
    public function export(
        array $invoices,
        Date $invoicedMonth,
        AccountingProfile $accountingProfile,
        string $filePath,
    ): string {
        $structure = $this->buildDbfStructure();

        $dbf = dbase_create($filePath, $structure);
        if ($dbf === false) {
            throw new RuntimeException(__d('bookkeeping', 'Error while creating DBF file.'));
        }

        try {
            foreach ($invoices as $invoice) {
                $this->addRecord($dbf, $invoice, $accountingProfile);
            }
        } finally {
            /** @psalm-suppress UnusedFunctionCall */
            dbase_close($dbf);
        }

        if (!is_file($filePath)) {
            throw new RuntimeException(__d('bookkeeping', 'Failed to generate Pohoda DBF export.'));
        }

        return $filePath;
    }

    /**
     * Build the DBF file structure.
     *
     * The structure mirrors the legacy DBFInvoices field definitions.
     *
     * @return array<int, array<int, mixed>>
     */
    private function buildDbfStructure(): array
    {
        $c = self::CHARSET;

        return [
            [iconv('UTF-8', $c, 'Cislo'), 'C', 10], // invoice number
            [iconv('UTF-8', $c, 'VarSym'), 'C', 20], // variable symbol
            [iconv('UTF-8', $c, 'SText'), 'C', 240], // general text
            [iconv('UTF-8', $c, 'Datum'), 'D'], // issue date
            [iconv('UTF-8', $c, 'DatUcP'), 'D'], // last day of month (legacy: uses creationDate)
            [iconv('UTF-8', $c, 'DatSplat'), 'D'], // due date
            [iconv('UTF-8', $c, 'DatZdPln'), 'D'], // accounting date (legacy: uses creationDate)

            [iconv('UTF-8', $c, 'Kc0'), 'N', 8, 2], // always 0
            [iconv('UTF-8', $c, 'Kc1'), 'N', 8, 2], // always 0
            [iconv('UTF-8', $c, 'KcDPH1'), 'N', 8, 2], // always 0
            [iconv('UTF-8', $c, 'Kc2'), 'N', 8, 2], // VAT base
            [iconv('UTF-8', $c, 'KcDPH2'), 'N', 8, 2], // VAT amount
            [iconv('UTF-8', $c, 'KcZaloha'), 'N', 8, 2], // always 0
            [iconv('UTF-8', $c, 'KcCelkem'), 'N', 8, 2], // total
            [iconv('UTF-8', $c, 'KcLikv'), 'N', 8, 2], // total
            [iconv('UTF-8', $c, 'KcU'), 'N', 8, 2], // always 0
            [iconv('UTF-8', $c, 'KcZaokr'), 'N', 8, 2], // always 0

            [iconv('UTF-8', $c, 'Firma'), 'C', 96], // company name
            [iconv('UTF-8', $c, 'Utvar'), 'C', 32], // branch/department
            [iconv('UTF-8', $c, 'Jmeno'), 'C', 32], // full name
            [iconv('UTF-8', $c, 'Ulice'), 'C', 32], // street
            [iconv('UTF-8', $c, 'PSC'), 'C', 7], // ZIP
            [iconv('UTF-8', $c, 'Obec'), 'C', 35], // city
            [iconv('UTF-8', $c, 'ICO'), 'C', 12], // company ID
            [iconv('UTF-8', $c, 'DIC'), 'C', 15], // VAT ID

            [iconv('UTF-8', $c, 'KonstSym'), 'C', 4], // constant symbol

            [iconv('UTF-8', $c, 'Pozn'), 'C', 240], // note
            [iconv('UTF-8', $c, 'Pozn2'), 'C', 240], // internal note
        ];
    }

    /**
     * Add a single invoice record to an open DBF file.
     *
     * Mirrors the legacy DBFInvoices::addRecord() implementation.
     *
     * @param resource $dbf Open DBF resource.
     * @param \Bookkeeping\Model\ValueObject\InvoiceDraft $invoice Invoice draft.
     * @param \App\Model\Entity\AccountingProfile $accountingProfile Accounting profile context.
     * @return void
     */
    private function addRecord($dbf, InvoiceDraft $invoice, AccountingProfile $accountingProfile): void
    {
        if (!$invoice->isValid()) {
            throw new RuntimeException(__d(
                'bookkeeping',
                'Invalid invoice data for invoice number {0}.',
                [$invoice->number],
            ));
        }

        // Ensure customer data is valid before adding the record
        if ($invoice->customer === null || $invoice->customer->billing_address === null) {
            throw new RuntimeException(__d(
                'bookkeeping',
                'Missing customer information or billing address for invoice number {0}.',
                [$invoice->number],
            ));
        }

        $totalCost = $invoice->total->toFloat();

        $vat = Billing::calcVatFromTotal(
            $invoice->total,
            $accountingProfile->vat_rate,
        )->toFloat();

        $data = [];

        // Basic invoice data
        $data[] = $invoice->number; // Cislo
        $data[] = $invoice->variableSymbol; // VarSym
        $data[] = $invoice->text; // SText
        $data[] = $invoice->creationDate->i18nFormat('yyyyMMdd'); // Datum
        $data[] = $invoice->creationDate->i18nFormat('yyyyMMdd'); // DatUcP
        $data[] = $invoice->dueDate->i18nFormat('yyyyMMdd'); // DatSplat
        $data[] = $invoice->creationDate->i18nFormat('yyyyMMdd'); // DatZdPln

        // VAT / pricing section
        if ($accountingProfile->reverse_charge) {
            $data[] = 0; // Kc0
            $data[] = 0; // Kc1
            $data[] = 0; // KcDPH1
            $data[] = $totalCost - $vat; // Kc2
            $data[] = 0; // KcDPH2
            $data[] = 0; // KcZaloha
            $data[] = $totalCost - $vat; // KcCelkem
            $data[] = $totalCost - $vat; // KcLikv
            $data[] = 0; // KcU
            $data[] = 0; // KcZaokr
        } else {
            $data[] = 0; // Kc0
            $data[] = 0; // Kc1
            $data[] = 0; // KcDPH1
            $data[] = $totalCost - $vat; // Kc2
            $data[] = $vat; // KcDPH2
            $data[] = 0; // KcZaloha
            $data[] = $totalCost; // KcCelkem
            $data[] = $totalCost; // KcLikv
            $data[] = 0; // KcU
            $data[] = 0; // KcZaokr
        }

        // Customer identification
        $data[] = $invoice->customer->billing_address->company; // Firma
        $data[] = null; // Utvar
        $data[] = $invoice->customer->billing_address->full_name; // Jmeno
        $data[] = $invoice->customer->billing_address->street_and_number; // Ulice
        $data[] = $invoice->customer->billing_address->zip; // PSC
        $data[] = $invoice->customer->billing_address->city; // Obec
        $data[] = $invoice->customer->identity_number; // ICO
        $data[] = $invoice->customer->vat_number; // DIC

        // Constant symbol
        $data[] = '0308'; // KonstSym

        // Notes
        $data[] = $invoice->note; // Pozn
        $data[] = $invoice->internalNote; // Pozn2

        // Charset conversion (legacy behavior)
        $encoded = [];
        foreach ($data as $value) {
            $encoded[] = is_string($value)
                ? iconv('UTF-8', self::CHARSET, $value)
                : $value;
        }

        $ok = dbase_add_record($dbf, $encoded);

        if ($ok === false) {
            throw new RuntimeException(
                __d('bookkeeping', 'Failed to write DBF record for invoice {0}.', (string)$invoice->number),
            );
        }
    }
}
