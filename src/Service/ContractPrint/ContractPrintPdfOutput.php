<?php
declare(strict_types=1);

namespace App\Service\ContractPrint;

use App\Model\Enum\ContractPrintType;
use App\Pdf\ContractPDF;
use Cake\Http\Response;
use Cake\I18n\Date;
use Cake\I18n\I18n;
use Settings\Utility\Settings;

/**
 * View responsible for rendering contract-related PDF documents.
 *
 * This view:
 *  - receives fully prepared ContractPrintData
 *  - selects the appropriate PDF generation method
 *  - outputs the final PDF to the browser
 *
 * It does NOT:
 *  - perform validation
 *  - prepare or mutate data
 *  - access request query parameters
 */
final class ContractPrintPdfOutput
{
    /**
     * Renders a PDF document based on prepared contract print data.
     *
     * Returns a CakePHP Response containing the PDF output with appropriate headers.
     *
     * @return \Cake\Http\Response
     */
    public function render(ContractPrintData $data): Response
    {
        $this->initializeLocale();

        $pdf = new ContractPDF('P', 'mm', 'A4');

        match ($data->type) {
            ContractPrintType::ContractNew,
            ContractPrintType::ContractNewX,
            ContractPrintType::ContractAmendment,
            ContractPrintType::ContractTermination
                => $pdf->generateContract($data),

            ContractPrintType::HandoverInstallation,
            ContractPrintType::HandoverUninstallation
                => $pdf->generateHandoverProtocol($data),
        };

        $filename = $this->buildFilename($data);

        return (new Response())
            ->withType('application/pdf')
            //->withDownload($this->buildFilename($data))
            ->withHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->withStringBody(
                $pdf->Output(
                    $filename,
                    'S', // return as string
                ),
            );
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
     * @return string
     */
    private function buildFilename(ContractPrintData $data): string
    {
        $date = match ($data->type) {
            ContractPrintType::ContractAmendment,
                => $data->effectiveDateOfAmendment,
            ContractPrintType::ContractTermination,
            ContractPrintType::HandoverUninstallation
                => $data->contractVersionToBeTerminated?->valid_until,
            default
                => $data->contractVersionToBeExecuted?->valid_from,
        };

        $typeSuffix = match ($data->type) {
            ContractPrintType::ContractAmendment
                => '-' . (($data->contractVersionToBeExecuted->number_of_amendments ?? 0) + 1),
            default => '',
        };

        return sprintf(
            '%s_%s%s_%s%s.pdf',
            $data->contract->number,
            $data->type->value,
            $typeSuffix,
            $date ? $date->i18nFormat('yyyy-MM-dd') : Date::now()->i18nFormat('yyyy-MM-dd'),
            $data->signed ? '-signed' : '',
        );
    }
}
