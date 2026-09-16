<?php
declare(strict_types=1);

namespace App\Service\ContractPrint;

use App\Documents\PrintedDocument;
use App\Model\Enum\ContractDocumentType;
use App\Pdf\ContractPDF;
use App\Pdf\ContractSummaryPDF;
use Cake\I18n\Date;
use Cake\I18n\I18n;
use Settings\Utility\Settings;

/**
 * Draws a contract document.
 *
 * This:
 *  - receives fully prepared ContractPrintData
 *  - selects the appropriate PDF generation method
 *  - hands back the paper as bytes
 *
 * It does NOT:
 *  - perform validation
 *  - prepare or mutate data
 *  - access request query parameters
 *  - put anybody's signature on the paper, or decide whether the paper should be drawn at all -
 *    that is {@see \App\Service\ContractPrint\ContractDocuments}, which asks for this only when
 *    there is nothing on file to hand over instead
 */
final class ContractPrintPdfOutput
{
    /**
     * Draws the paper, with nobody's signature on it.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data What to draw.
     * @return \App\Documents\PrintedDocument
     */
    public function document(ContractPrintData $data): PrintedDocument
    {
        $this->initializeLocale();

        // The summary is its own document with its own layout, so it is its own generator too.
        $pdf = $data->type === ContractDocumentType::ContractSummary
            ? new ContractSummaryPDF()
            : new ContractPDF();

        match (true) {
            $pdf instanceof ContractSummaryPDF
                => $pdf->generateContractSummary($data),

            $data->type->isHandoverProtocol()
                => $pdf->generateHandoverProtocol($data),

            default
                => $pdf->generateContract($data),
        };

        $filename = $this->filename($data, false);

        return new PrintedDocument($pdf->Output($filename, 'S'), $filename);
    }

    /**
     * Initializes locale and date formatting for PDF documents.
     *
     * This ensures consistent formatting regardless of the
     * current application or user locale.
     *
     * @return void
     */
    private function initializeLocale(): void
    {
        I18n::setLocale(
            Settings::getString('core.documents.locale', 'en_US'),
        );

        Date::setToStringFormat('dd.MM.yyyy');
    }

    /**
     * Builds the output filename for the generated PDF document.
     *
     * The filename includes:
     *  - contract number
     *  - document type
     *  - relevant contract date
     *  - optional signed suffix
     *
     * Whether it is the signed one is asked for rather than read off the request, because one
     * request draws the unsigned paper and then names the signed one it makes from it.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data What the paper is.
     * @param bool $signed Whether it is the copy carrying our signature.
     * @return string
     */
    public function filename(ContractPrintData $data, bool $signed): string
    {
        $date = match ($data->type) {
            ContractDocumentType::ContractAmendment,
                => $data->effectiveDateOfAmendment,
            ContractDocumentType::ContractTermination,
            ContractDocumentType::HandoverUninstallation
                => $data->contractVersionToBeTerminated?->valid_until,
            default
                => $data->contractVersionToBeExecuted?->valid_from,
        };

        $typeSuffix = match ($data->type) {
            ContractDocumentType::ContractAmendment
                => '-' . (($data->contractVersionToBeExecuted->number_of_amendments ?? 0) + 1),
            default => '',
        };

        return sprintf(
            '%s_%s%s_%s%s.pdf',
            $data->contract->number,
            $data->type->value,
            $typeSuffix,
            $date ? $date->i18nFormat('yyyy-MM-dd') : Date::now()->i18nFormat('yyyy-MM-dd'),
            $signed ? '-signed' : '',
        );
    }
}
