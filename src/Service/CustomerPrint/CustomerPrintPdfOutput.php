<?php
declare(strict_types=1);

namespace App\Service\CustomerPrint;

use App\Model\Enum\CustomerPrintType;
use App\Pdf\CustomerPDF;
use Cake\Http\Response;
use Cake\I18n\Date;
use Cake\I18n\I18n;
use Settings\Utility\Settings;

/**
 * View responsible for rendering customer-related PDF documents.
 *
 * This view:
 *  - receives fully prepared CustomerPrintData
 *  - selects the appropriate PDF generation method
 *  - outputs the final PDF to the browser
 *
 * It does NOT:
 *  - perform validation
 *  - prepare or mutate data
 *  - access request query parameters
 */
final class CustomerPrintPdfOutput
{
    /**
     * Renders a PDF document based on prepared customer print data.
     *
     * Returns a CakePHP Response containing the PDF output with appropriate headers.
     *
     * @param \App\Service\CustomerPrint\CustomerPrintData $data
     * @return \Cake\Http\Response
     */
    public function render(CustomerPrintData $data): Response
    {
        $this->initializeLocale();

        $pdf = new CustomerPDF('P', 'mm', 'A4');

        match ($data->type) {
            CustomerPrintType::GdprNew,
            CustomerPrintType::GdprChange
                => $pdf->generateGDPRAgreement($data),
        };

        $filename = $this->buildFilename($data);

        return new Response()
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
     *  - customer number
     *  - document type
     *  - generation date
     *
     * @param \App\Service\CustomerPrint\CustomerPrintData $data
     * @return string
     */
    private function buildFilename(CustomerPrintData $data): string
    {
        $date = Date::now();

        return sprintf(
            '%s_%s_%s.pdf',
            $data->customer->number,
            $data->type->value,
            $date->i18nFormat('yyyy-MM-dd'),
        );
    }
}
