<?php
/**
 * @var \App\View\AppView $this
 * @var \BookkeepingPohoda\Model\Entity\Invoice[]|\Cake\Collection\CollectionInterface $invoices
 * @var \Cake\I18n\FrozenDate $invoiced_month
 * @var array $tax_rates
 * @var int $tax_rate_id
 * @var bool $reverse_charge
 */

use BookkeepingPohoda\DBFInvoices;

$dbf = new DBFInvoices();

// Generate DBF file name
$dbf_filename = TMP . uniqid('invoices-', true) . '.dbf';

$dbf->createDBF($dbf_filename);

foreach ($invoices as $invoice) {
    $dbf->addRecord($invoice, $reverse_charge);
}

$dbf->closeDBF();

// set for download with specified filename
$this->setResponse(
    $this->getResponse()->withDownload(
        'Invoices' . '-' . strtolower($tax_rates[$tax_rate_id])
            . '-' . $invoiced_month->i18nFormat('yyyy-MM') . '.dbf'
    )
);

//read file to output
readfile($dbf_filename);

//remove file
unlink($dbf_filename);
