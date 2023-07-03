<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Customer $customer
 * @var string|null $type
 * @var \stdClass|null $technical_details
 */

declare(strict_types=1);

namespace App\View;

use App\CustomerPDF;
use Cake\I18n\FrozenDate;

// define date format
\Cake\I18n\Date::setToStringFormat('dd.MM.yyyy');

if (isset($query['signed']) && $query['signed'] == 1) {
    $signed = true;
} else {
    $signed = false;
}

switch ($type) {
    case 'gdpr-new':
    case 'gdpr-change':
        //Generate PDF
        $pdf = new CustomerPDF('P', 'mm', 'A4');
        $pdf->generateGDPRAgreement($customer, $type, $signed, $address_types);
        $pdf->Output($customer->number . '_' . $type . '_' . date('Y-m-d') . '.pdf', 'I');
        break;
    default:
        exit;
}
