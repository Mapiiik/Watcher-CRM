<?php
use App\CustomerPDF;
use App\Utility\Settings;
use Cake\I18n\Date;
use Cake\I18n\I18n;

/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Customer $customer
 * @var string|null $type
 * @var \stdClass|null $technical_details
 */

// switch to documents locale
I18n::setLocale(Settings::getString('core.documents.locale', 'en_US'));

// define date format
Date::setToStringFormat('dd.MM.yyyy');

switch ($type) {
    case 'gdpr-new':
    case 'gdpr-change':
        //Generate PDF
        $pdf = new CustomerPDF('P', 'mm', 'A4');
        $pdf->generateGDPRAgreement($customer, $type);
        $pdf->Output($customer->number . '_' . $type . '_' . date('Y-m-d') . '.pdf', 'I');
        break;
    default:
        exit;
}
