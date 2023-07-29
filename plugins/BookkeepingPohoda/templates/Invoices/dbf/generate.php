<?php
use BookkeepingPohoda\DBFInvoices;

/**
 * @var \App\View\AppView $this
 * @psalm-scope-this \App\View\AppView
 * @var iterable<\BookkeepingPohoda\Model\Entity\Invoice> $invoices
 * @var \Cake\I18n\Date $invoiced_month
 * @var array $tax_rates
 * @var \App\Model\Entity\TaxRate $tax_rate
 */

$dbf = new DBFInvoices();

// Generate DBF file name
$dbf_filename = TMP . uniqid('invoices-', true) . '.dbf';

$dbf->createDBF($dbf_filename);

foreach ($invoices as $invoice) {
    $dbf->addRecord($invoice, $tax_rate);
}

$dbf->closeDBF();

// set for download with specified filename
$this->setResponse(
    $this->getResponse()->withDownload(
        'Invoices' . '-' . strtolower($tax_rate->name)
            . '-' . $invoiced_month->i18nFormat('yyyy-MM') . '.dbf'
    )
);

//read file to output
readfile($dbf_filename);

//remove file
unlink($dbf_filename);
