<?php
use BookkeepingPohoda\DBFInvoices;

/**
 * @var \App\View\AppView $this
 * @psalm-scope-this \App\View\AppView
 * @var iterable<\BookkeepingPohoda\Model\Entity\Invoice> $invoices
 * @var \Cake\I18n\Date $invoicedMonth
 * @var \Cake\Collection\CollectionInterface|array<string> $taxRates
 * @var \App\Model\Entity\TaxRate $taxRate
 */

$dbf = new DBFInvoices();

// Generate DBF file name
$dbfFilename = TMP . uniqid('invoices-', true) . '.dbf';

$dbf->createDBF($dbfFilename);

foreach ($invoices as $invoice) {
    $dbf->addRecord($invoice, $taxRate);
}

$dbf->closeDBF();

// set for download with specified filename
$this->setResponse(
    $this->getResponse()->withDownload(
        'Invoices' . '-' . strtolower($taxRate->name)
            . '-' . $invoicedMonth->i18nFormat('yyyy-MM') . '.dbf'
    )
);

//read file to output
readfile($dbfFilename);

//remove file
unlink($dbfFilename);
