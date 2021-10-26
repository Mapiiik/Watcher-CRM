<?php
/**
 * @var \App\View\AppView $this
 * @var string[]|\Cake\Collection\CollectionInterface $customers
 */

$dbf = new \BookkeepingPohoda\DBFInvoices();

// Generate DBF file name
$dbf_filename = TMP . uniqid("billing", true) . ".dbf";

$dbf->CreateDBF($dbf_filename);

foreach ($invoices as $invoice) {
    $dbf->AddRecord($invoice, $reverse_charge);
}

$dbf->CloseDBF();

// set for download with specified filename
header("Content-Disposition: attachment; filename=\"billing-" . strtolower($tax_rates[$tax_rate_id]) . '-' . $invoiced_month->i18nFormat('yyyy-MM') . ".dbf\"");

//read file to output
readfile($dbf_filename);

//remove file
unlink($dbf_filename);
?>
