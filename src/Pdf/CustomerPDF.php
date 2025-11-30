<?php
declare(strict_types=1);

namespace App\Pdf;

use App\Model\Entity\Customer;
use App\Utility\Settings;

class CustomerPDF extends AppPDF
{
    /**
     * Generate PDF document - GDPR agreement
     *
     * @param \App\Model\Entity\Customer $customer Customer with all related data
     * @param string $type Type of requested document
     * @return void
     */
    public function generateGDPRAgreement(Customer $customer, string $type = 'gdpr-new'): void
    {
        // Disable default header and footer
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);

        // Add first page
        $this->AddPage();

        // Company logo
        $this->Image(K_PATH_IMAGES . 'logo-contract.png', 10, 5, 28);

        // Document title
        $this->SetFont('DejaVuSerif', 'B', 18);
        $this->Cell(187, 6, Settings::getString('core.documents.gdpr.title'), align: 'C');
        $this->Ln();

        // Document subtitle
        $this->SetFont('DejaVuSerif', 'B', 12);
        $this->Cell(187, 2, Settings::getString('core.documents.gdpr.subtitle'), align: 'C');
        $this->Ln(3);

        // Separator line
        $this->drawSeparator(lnBefore: 4, lnAfter: 0.5);

        // Agreement header labels
        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(62, 4, Settings::getString('core.documents.common.labels.new_or_change'), align: 'C');
        $this->Cell(62, 4, Settings::getString('core.documents.common.labels.agreement_number'), align: 'C');
        $this->Cell(62, 4, Settings::getString('core.documents.common.labels.agreement_duration'), align: 'C');
        $this->Ln();

        // Agreement header values
        $this->SetFont('DejaVuSerif', 'B', 8);
        switch ($type) {
            case 'gdpr-new':
                $this->Cell(62, 4, Settings::getString('core.documents.common.labels.new'), align: 'C');
                break;
            case 'gdpr-change':
                $this->Cell(62, 4, Settings::getString('core.documents.common.labels.change'), align: 'C');
                break;
        }
        $this->Cell(62, 4, $customer->number, align: 'C');
        $this->Cell(62, 4, Settings::getString('core.documents.common.labels.duration_indefinite'), align: 'C');
        $this->Ln();

        // Separator line
        $this->drawSeparator(AppPDF::SEPARATOR_OFFSET_X, lnAfter: 3.0);

        // Controller section
        $this->SetFont('DejaVuSerif', 'B', 9);
        $this->Cell(187, 2, Settings::getString('core.documents.common.labels.between'), align: 'C');
        $this->Ln();

        // Controller section - company details block
        $this->printCompanyDetails(Settings::getString('core.documents.common.labels.controller'));

        // User section
        $this->SetFont('DejaVuSerif', 'B', 9);
        $this->Cell(187, 4, Settings::getString('core.documents.common.labels.and'), align: 'C');
        $this->Ln();
        $this->Cell(30, 4, Settings::getString('core.documents.common.labels.user'));

        // Determine user type (non-business, business, legal entity)
        $this->SetFont('DejaVuSerif', '', 8);
        $this->printUserType($customer);

        $this->Ln();

        // Separator line
        $this->drawSeparator(lnAfter: 0.5);

        // Customer personal and business data
        $this->SetFont('DejaVuSerif', '', 8);
        $this->printTable(
            [
                Settings::getString('core.documents.common.labels.personal_data'),
                Settings::getString('core.documents.common.labels.business_data')
            ],
            [
                [
                    ['label' => Settings::getString('core.documents.common.labels.name'), 'value' => $customer->billing_address->full_name],
                    ['label' => Settings::getString('core.documents.common.labels.company'), 'value' => $customer->billing_address->company ?? 'X'],
                ],
                [
                    ['label' => Settings::getString('core.documents.common.labels.birth_date'), 'value' => (string)$customer->date_of_birth],
                    ['label' => Settings::getString('core.documents.common.labels.identity_number'), 'value' => $customer->ic ?? 'X'],
                ],
                [
                    ['label' => Settings::getString('core.documents.common.labels.identity_card_number'), 'value' => $customer->identity_card_number],
                    ['label' => Settings::getString('core.documents.common.labels.vat_number'), 'value' => $customer->dic ?? 'X'],
                ],
                [
                    ['label' => Settings::getString('core.documents.common.labels.phone'), 'value' => $customer->phone],
                ],
                [
                    ['label' => Settings::getString('core.documents.common.labels.email'), 'value' => $customer->email],
                ],
            ],
        );

        // Addresses loop
        foreach ($customer->addresses as $address) {
            $this->SetFont('DejaVuSerif', 'B', 8);
            $this->Cell(30, 4, $address->type->label() . ': ');
            $this->Ln();
            $this->SetFont('DejaVuSerif', 'B', 8);
            $this->Cell(30, 4);
            $this->MultiCell(160, 4, $address->full_address, align: 'L');
        }
        $this->drawSeparator(AppPDF::SEPARATOR_OFFSET_X);
        $this->Ln();

        // Declaration text
        $this->SetFont('DejaVuSerif', '', 7);
        $this->Write(3, Settings::getString('core.documents.gdpr.declaration'), ln: true);

        // Checkboxes
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->Write(3, Settings::getString('core.documents.gdpr.checkboxes.billing'), ln: true);
        $this->Write(3, Settings::getString('core.documents.gdpr.checkboxes.outages'), ln: true);
        $this->Write(3, Settings::getString('core.documents.gdpr.checkboxes.marketing'), ln: true);
        $this->Ln();
        $this->Write(3, Settings::getString('core.documents.gdpr.checkboxes.note'), ln: true);

        // Signature section
        $this->printSignatureSection('single-right', false);
    }
}
