<?php
use App\ContractPDF;
use App\Utility\Settings;
use Cake\I18n\Date;
use Cake\I18n\I18n;

/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Contract $contract
 * @var \App\Model\Entity\ContractVersion $contract_version
 * @var string|null $type
 * @var \stdClass|null $technical_details
 */

// switch to documents locale
I18n::setLocale(Settings::getString('core.documents.locale', 'en_US'));

// define date format
Date::setToStringFormat('dd.MM.yyyy');

$signed = !empty($query['signed']);

switch ($type) {
    case 'contract-new':
    case 'contract-new-x':
    case 'contract-amendment':
        //Generate PDF
        $pdf = new ContractPDF('P', 'mm', 'A4');
        $pdf->generateContract($contract, $contract_version, $type, $signed);
        $pdf->Output($contract->number . '_' . $type . '_'
            . $contract_version->valid_from->i18nFormat('yyyy-MM-dd')
            . ($signed ? '-signed' : '')
            . '.pdf', 'I');
        break;
    case 'contract-termination':
        //Generate PDF
        $pdf = new ContractPDF('P', 'mm', 'A4');
        $pdf->generateContract($contract, $contract_version, $type, $signed);
        $pdf->Output($contract->number . '_' . $type . '_'
            . $contract_version->valid_until->i18nFormat('yyyy-MM-dd')
            . ($signed ? '-signed' : '')
            . '.pdf', 'I');
        break;
    case 'handover-protocol-installation':
        //Generate PDF
        $pdf = new ContractPDF('P', 'mm', 'A4');
        $pdf->generateHandoverProtocol($contract, $contract_version, $type, $signed, $technical_details);
        $pdf->Output($contract->number . '_' . $type . '_'
            . $contract_version->valid_from->i18nFormat('yyyy-MM-dd')
            . ($signed ? '-signed' : '')
            . '.pdf', 'I');
        break;
    case 'handover-protocol-uninstallation':
        //Generate PDF
        $pdf = new ContractPDF('P', 'mm', 'A4');
        $pdf->generateHandoverProtocol($contract, $contract_version, $type, $signed);
        $pdf->Output($contract->number . '_' . $type . '_'
            . $contract_version->valid_until->i18nFormat('yyyy-MM-dd')
            . ($signed ? '-signed' : '')
            . '.pdf', 'I');
        break;
    default:
        exit;
}
