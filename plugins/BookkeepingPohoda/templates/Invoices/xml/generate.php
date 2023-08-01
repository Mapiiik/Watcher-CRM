<?php
use Riesenia\Pohoda;

/**
 * @var \App\View\AppView $this
 * @psalm-scope-this \App\View\AppView
 * @var iterable<\BookkeepingPohoda\Model\Entity\Invoice> $invoices
 * @var \Cake\I18n\Date $invoiced_month
 * @var array $tax_rates
 * @var \App\Model\Entity\TaxRate $tax_rate
 */

Pohoda::$encoding = 'UTF-8';

$pohoda = new Pohoda('27496139');
$pohoda->setApplicationName(env('APP_NAME', 'Watcher CRM'));

// Generate XML file name
$xml_filename = TMP . uniqid('invoices-', true) . '.xml';

// create file
$pohoda->open($xml_filename, $invoiced_month->i18nFormat('yyyy-MM'), 'Import invoices');

foreach ($invoices as $invoice) {
    /** @var \BookkeepingPohoda\Model\Entity\Invoice $invoice */
    $invoiceRecord = $pohoda->createInvoice([
        'invoiceType' => 'issuedInvoice',
        'number' => [
            'numberRequested' => $invoice->number,
        ],
        'symVar' => (string)$invoice->variable_symbol,
        'date' => $invoice->creation_date->i18nFormat('yyyy-MM-dd'),
        'dateTax' => $invoice->creation_date->i18nFormat('yyyy-MM-dd'),
        'dateAccounting' => $invoice->creation_date->i18nFormat('yyyy-MM-dd'),
        'dateDue' => $invoice->due_date->i18nFormat('yyyy-MM-dd'),
        'accounting' => [
            'ids' => $tax_rate->accounting_assignment_code ?? '2Fv',
        ],
        'classificationVAT' => [
            'ids' => $tax_rate->reverse_charge ? 'UDpdp' : 'UD',
        ],
        'text' => $invoice->text ?? '',
        'partnerIdentity' => [
            'address' => [
                'company' => $invoice->customer->billing_address->company ?? '',
                'name' => $invoice->customer->billing_address->full_name ?? '',
                'city' => $invoice->customer->billing_address->city ?? '',
                'street' => $invoice->customer->billing_address->street_and_number ?? '',
                'zip' => $invoice->customer->billing_address->zip ?? '',
                'ico' => $invoice->customer->ic ?? '',
                'dic' => $invoice->customer->dic ?? '',
            ],
        ],
        'paymentType' => [
            'paymentType' => 'draft',
        ],
        'account' => [
            'ids' => $tax_rate->bank_account_code ?? 'KB',
        ],
        'symConst' => '0308',
        'activity' => [
            'ids' => $tax_rate->activity_code ?? 'internet',
        ],
        'note' => $invoice->note ?? '',
        'intNote' => $invoice->internal_note ?? '',
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
                'price' => $item->period_total - round(
                    $item->period_total - ($item->period_total / (1 + $tax_rate->vat_rate)),
                    2
                ),
                'priceVAT' => $tax_rate->reverse_charge ? 0 : round(
                    $item->period_total - ($item->period_total / (1 + $tax_rate->vat_rate)),
                    2
                ),
            ],
            'PDP' => $tax_rate->reverse_charge,
        ]);
    }

    // add summary
    $invoiceRecord->addSummary([
        'roundingDocument' => 'none',
        'roundingVAT' => 'none',
        'homeCurrency' => [
            'priceNone' => 0,
            'priceLow' => 0,
            'priceHigh' => $invoice->total - round(
                $invoice->total - ($invoice->total / (1 + $tax_rate->vat_rate)),
                2
            ),
            'priceHighVAT' => $tax_rate->reverse_charge ? 0 : round(
                $invoice->total - ($invoice->total / (1 + $tax_rate->vat_rate)),
                2
            ),
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

// set for download with specified filename
$this->setResponse(
    $this->getResponse()->withDownload(
        'Invoices' . '-' . strtolower($tax_rate->name)
            . '-' . $invoiced_month->i18nFormat('yyyy-MM') . '.xml'
    )
);

//read file to output
readfile($xml_filename);

//remove file
unlink($xml_filename);
