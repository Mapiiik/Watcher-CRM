<?php
declare(strict_types=1);

namespace App\Service\CustomerPrint;

use App\Documents\PrintedDocument;
use App\Model\Enum\CustomerDocumentType;
use App\Pdf\CustomerPDF;
use Cake\I18n\Date;
use Cake\I18n\I18n;
use LogicException;
use Settings\Utility\Settings;

/**
 * View responsible for rendering customer-related PDF documents.
 *
 * This view:
 *  - receives fully prepared CustomerPrintData
 *  - selects the appropriate PDF generation method
 *  - hands back the paper it drew
 *
 * It does NOT:
 *  - perform validation
 *  - prepare or mutate data
 *  - access request query parameters
 *  - decide what becomes of the paper afterwards
 */
final class CustomerPrintPdfOutput
{
    /**
     * Draws the paper the prepared data asks for.
     *
     * What becomes of it - handed over, stored, or both - is settled by whoever asked.
     *
     * @param \App\Service\CustomerPrint\CustomerPrintData $data What is wanted.
     * @return \App\Documents\PrintedDocument
     */
    public function document(CustomerPrintData $data): PrintedDocument
    {
        $this->initializeLocale();

        $pdf = new CustomerPDF();

        match ($data->type) {
            CustomerDocumentType::GdprNew,
            CustomerDocumentType::GdprChange
                => $pdf->generateGDPRAgreement($data),

            CustomerDocumentType::ServicesOverview
                => $pdf->generateServicesOverview($data),

            // Nothing to draw: this one is only ever filed. Nobody reaches here - the validator
            // turns it away first - so there is no paper to fall back on, only a way of saying
            // that the turning away did not happen.
            CustomerDocumentType::Other
                => throw new LogicException('A document that is only ever filed cannot be drawn.'),
        };

        $filename = $this->filename($data);

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
     *  - customer number
     *  - document type
     *  - generation date
     *
     * @param \App\Service\CustomerPrint\CustomerPrintData $data What the paper is.
     * @return string
     */
    public function filename(CustomerPrintData $data): string
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
