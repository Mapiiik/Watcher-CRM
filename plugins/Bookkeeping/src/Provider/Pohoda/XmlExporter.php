<?php
declare(strict_types=1);

namespace Bookkeeping\Provider\Pohoda;

use App\Model\Entity\Billing;
use App\Model\Entity\TaxRate;
use Cake\I18n\Date;
use Riesenia\Pohoda;
use RuntimeException;
use Settings\Utility\Settings;

/**
 * Class XmlExporter
 *
 * Generates XML files compatible with the Pohoda accounting system.
 *
 * This exporter is responsible only for transforming InvoiceDraft objects
 * into a valid Pohoda XML import file. It does not handle persistence,
 * HTTP responses, downloads or filesystem cleanup.
 */
class XmlExporter
{
    /**
     * Export invoices into a Pohoda-compatible XML file.
     *
     * @param list<\Bookkeeping\Model\ValueObject\InvoiceDraft> $invoices Invoice drafts to export.
     * @param \Cake\I18n\Date $invoicedMonth Invoiced month (used for XML header).
     * @param \App\Model\Entity\TaxRate $taxRate Tax rate and accounting context.
     * @param string $filePath Target XML file path.
     * @return string Final XML file path.
     */
    public function export(
        array $invoices,
        Date $invoicedMonth,
        TaxRate $taxRate,
        string $filePath,
    ): string {
        Pohoda::$encoding = 'UTF-8';

        $pohoda = new Pohoda(
            Settings::getString(
                PohodaProvider::SETTINGS_ROOT . '.issuer.identity_number',
                '00000000',
            ),
        );
        $pohoda->setApplicationName('Watcher CRM');

        $pohoda->open(
            $filePath,
            (string)$invoicedMonth->i18nFormat('yyyy-MM'),
            'Import invoices',
        );

        foreach ($invoices as $invoice) {
            $invoiceRecord = $pohoda->createInvoice([
                'invoiceType' => 'issuedInvoice',
                'number' => [
                    'numberRequested' => $invoice->number,
                ],
                'symVar' => (string)$invoice->variableSymbol,
                'date' => $invoice->creationDate->i18nFormat('yyyy-MM-dd'),
                'dateTax' => $invoice->creationDate->i18nFormat('yyyy-MM-dd'),
                'dateAccounting' => $invoice->creationDate->i18nFormat('yyyy-MM-dd'),
                'dateDue' => $invoice->dueDate->i18nFormat('yyyy-MM-dd'),
                'accounting' => [
                    'ids' => $taxRate->accounting_assignment_code ?? '2Fv',
                ],
                'classificationVAT' => [
                    'ids' => $taxRate->reverse_charge ? 'UDpdp' : 'UD',
                ],
                'text' => $invoice->text ?? '',
                'partnerIdentity' => [
                    'address' => [
                        'company' => $invoice->customer->billing_address->company ?? '',
                        'name' => $invoice->customer->billing_address->full_name ?? '',
                        'city' => $invoice->customer->billing_address->city ?? '',
                        'street' => $invoice->customer->billing_address->street_and_number ?? '',
                        'zip' => $invoice->customer->billing_address->zip ?? '',
                        'ico' => $invoice->customer->identity_number ?? '',
                        'dic' => $invoice->customer->vat_number ?? '',
                    ],
                ],
                'paymentType' => [
                    'paymentType' => 'draft',
                ],
                'account' => [
                    'ids' => $taxRate->bank_account_code ?? 'KB',
                ],
                'symConst' => '0308',
                'activity' => [
                    'ids' => $taxRate->activity_code ?? 'internet',
                ],
                'note' => $invoice->note ?? '',
                'intNote' => $invoice->internalNote ?? '',
            ]);

            // add items
            foreach ($invoice->items as $item) {
                $invoiceRecord->addItem([
                    'text' => $item->name,
                    'quantity' => 1,
                    // 'unit' => 'ks',
                    'payVAT' => true, // price includes VAT
                    'rateVAT' => 'high',
                    'homeCurrency' => [
                        'unitPrice' => $item->period_total,
                        'price' => Billing::calcVatBaseFromTotal(
                            $item->period_total,
                            $taxRate->vat_rate,
                        )->toFloat(),
                        'priceVAT' => $taxRate->reverse_charge
                            ? 0
                            : Billing::calcVatFromTotal(
                                $item->period_total,
                                $taxRate->vat_rate,
                            )->toFloat(),
                    ],
                    'PDP' => $taxRate->reverse_charge,
                ]);
            }

            // add summary
            $invoiceRecord->addSummary([
                'roundingDocument' => 'none',
                'roundingVAT' => 'none',
                'homeCurrency' => [
                    'priceNone' => 0,
                    'priceLow' => 0,
                    'priceHigh' => Billing::calcVatBaseFromTotal(
                        $invoice->total,
                        $taxRate->vat_rate,
                    )->toFloat(),
                    'priceHighVAT' => $taxRate->reverse_charge
                        ? 0
                        : Billing::calcVatFromTotal(
                            $invoice->total,
                            $taxRate->vat_rate,
                        )->toFloat(),
                    'round' => [
                        'priceRound' => 0,
                    ],
                ],
            ]);

            // add invoice to import (identified by $invoice->number)
            $pohoda->addItem((string)$invoice->number, $invoiceRecord);
        }

        // finish import file
        $pohoda->close();

        // check file
        if (!is_file($filePath)) {
            throw new RuntimeException(__d('bookkeeping', 'Failed to generate Pohoda XML export.'));
        }

        return $filePath;
    }
}
