<?php
/**
 * @var \App\View\AppView $this
 * @var string[]|\Cake\Collection\CollectionInterface $customers
 */

use Riesenia\Pohoda;

Pohoda::$encoding = 'UTF-8';

$pohoda = new Pohoda('27496139');
$pohoda->setApplicationName(env('APP_NAME', 'Watcher CRM'));

// Generate XML file name
$xml_filename = TMP . uniqid("billing", true) . ".xml";

// create file
$pohoda->open($xml_filename, $invoiced_month->i18nFormat('yyyy-MM'), 'Import invoices');

foreach ($invoices as $invoice) {
    //$dbf->addRecord($invoice, $reverse_charge);

    $invoiceRecord = $pohoda->createInvoice([
        'invoiceType' => 'issuedInvoice',
        'number' => [
            'numberRequested' => $invoice->number,
        ],
        'symVar' => $invoice->variable_symbol,
        'date' => $invoice->creation_date->i18nFormat('yyyy-MM-dd'),
        'dateTax' => $invoice->creation_date->i18nFormat('yyyy-MM-dd'),
        'dateAccounting' => $invoice->creation_date->i18nFormat('yyyy-MM-dd'),
        'dateDue' => $invoice->due_date->i18nFormat('yyyy-MM-dd'),
        'accounting' => [
            'ids' => '2Fv',
        ],
        'classificationVAT' => [
            'ids' => $reverse_charge ? 'UDpdp' : 'UD',
        ],
        'text' => $invoice->text,
        'partnerIdentity' => [
            'address' => [
                'company' => $invoice->customer->billing_address->company,
                'name' => $invoice->customer->billing_address->full_name,
                'city' => $invoice->customer->billing_address->city,
                'street' => $invoice->customer->billing_address->street_and_number,
                'zip' => $invoice->customer->billing_address->zip,
                'ico' => $invoice->customer->ic,
                'dic' => $invoice->customer->dic,
            ],
        ],
        'paymentType' => [
            'paymentType' => 'draft',
        ],
        'account' => [
            'ids' => 'KB',
        ],
        'symConst' => '0308',
        'activity' => [
            'ids' => 'internet',
        ],
        'note' => $invoice->note,
        'intNote' => $invoice->internal_note,
    ]);


    // add items
    foreach ($invoice->items as $item) {
        $invoiceRecord->addItem([
            'text' => $item->name,
            'quantity' => 1,
//            'unit' => 'ks',
            'payVAT' => true, // částka s/bez DPH
            'rateVAT' => 'high',
            'homeCurrency' => [
                'unitPrice' => $item->period_total,
                'price' => $item->period_total - round($item->period_total - ($item->period_total / (1 + env('VAT_RATE'))), 2),
                'priceVAT' => $reverse_charge ? 0 : round($item->period_total - ($item->period_total / (1 + env('VAT_RATE'))), 2),
            ],
            'PDP' => $reverse_charge, // not implemented in pohoda class yet
        ]);
    }

    // add summary
    $invoiceRecord->addSummary([
        'roundingDocument' => 'none',
        'roundingVAT' => 'none',
        'homeCurrency' => [
            'priceNone' => 0,
            'priceLow' => 0,
            'priceHigh' => $invoice->total - round($invoice->total - ($invoice->total / (1 + env('VAT_RATE'))), 2),
            'priceHighVAT' => $reverse_charge ? 0 : round($invoice->total - ($invoice->total / (1 + env('VAT_RATE'))), 2),
            'round' => [
                'priceRound' => 0
            ]
        ]
    ]);

    // add invoice to import (identified by $invoice->number)
    $pohoda->addItem($invoice->number, $invoiceRecord);
}

// finish import file
$pohoda->close();

// set for download with specified filename
header("Content-Disposition: attachment; filename=\"billing-" . strtolower($tax_rates[$tax_rate_id]) . '-' . $invoiced_month->i18nFormat('yyyy-MM') . ".xml\"");

//read file to output
readfile($xml_filename);

//remove file
unlink($xml_filename);
?>
