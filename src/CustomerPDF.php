<?php
declare(strict_types=1);

namespace App;

use App\Model\Entity\Customer;
use App\Utility\Settings;
use Override;
use TCPDF;

//set image path for TCPDF
define('K_PATH_IMAGES', dirname(__DIR__) . DS . 'webroot' . DS . 'legacy' . DS . 'images' . DS);

class CustomerPDF extends TCPDF
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function cell(
        mixed $w,
        mixed $h = 0,
        mixed $txt = '',
        mixed $border = 0,
        mixed $ln = 0,
        mixed $align = '',
        mixed $fill = false,
        mixed $link = '',
        mixed $stretch = 0,
        mixed $ignore_min_height = false,
        mixed $calign = 'T',
        mixed $valign = '',
    ): void {
        $valign = $valign == '' ? ($border == 0 ? 'T' : 'M') : $valign;
        parent::Cell($w, $h, $txt, $border, $ln, $align, $fill, $link, $stretch, $ignore_min_height, $calign, $valign);
    }

    /**
     * generate PDF document - GDPR agreement
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
        $this->Ln(4);
        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187.0, $this->GetY());
        $this->Ln(0.5);

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
        $this->Line($this->GetX() + 4.0, $this->GetY(), $this->GetX() + 187.0, $this->GetY());
        $this->Ln(3);

        // Controller section
        $this->SetFont('DejaVuSerif', 'B', 9);
        $this->Cell(187, 2, Settings::getString('core.documents.common.labels.between'), align: 'C');
        $this->Ln();

        $this->Cell(45, 4, Settings::getString('core.documents.common.labels.controller'));
        $this->Ln();

        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187.0, $this->GetY());

        // Company details (name, address, contacts, registry)
        $this->Ln(1);
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->Cell(30, 4);
        $this->Cell(40, 4, Settings::getString('core.company.name'));
        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(30, 4);
        $this->Cell(40, 4);
        $this->Cell(15, 4, Settings::getString('core.documents.common.labels.phone'));
        $this->Cell(40, 4, Settings::getString('core.company.phone'));
        $this->Ln();

        $this->Cell(30, 4);
        $this->Cell(40, 4, Settings::getString('core.company.address_line_1'));
        $this->Cell(20, 4);
        $this->Cell(10, 4, Settings::getString('core.documents.common.labels.identity_number'));
        $this->Cell(40, 4, Settings::getString('core.company.identity_number'));
        $this->Cell(15, 4, Settings::getString('core.documents.common.labels.mobile'));
        $this->Cell(40, 4, Settings::getString('core.company.mobile'));
        $this->Ln();

        $this->Cell(30, 4);
        $this->Cell(40, 4, Settings::getString('core.company.address_line_2'));
        $this->Cell(20, 4);
        $this->Cell(10, 4, Settings::getString('core.documents.common.labels.vat_number'));
        $this->Cell(40, 4, Settings::getString('core.company.vat_number'));
        $this->Cell(15, 4, 'e-mail:');
        $this->Cell(40, 4, Settings::getString('core.company.email'));
        $this->Ln();

        $this->Ln(3);
        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(30, 4);
        $this->MultiCell(157, 4, Settings::getString('core.company.executive_clause'), align: 'L');
        $this->Cell(30, 4);
        $this->MultiCell(157, 4, Settings::getString('core.company.registry_clause'), align: 'L');

        $this->Line($this->GetX() + 4.0, $this->GetY(), $this->GetX() + 187.0, $this->GetY());
        $this->Ln(3);

        // User section
        $this->SetFont('DejaVuSerif', 'B', 9);
        $this->Cell(187, 4, Settings::getString('core.documents.common.labels.and'), align: 'C');
        $this->Ln();
        $this->Cell(30, 4, Settings::getString('core.documents.common.labels.user'));

        // Determine user type (non-business, business, legal entity)
        $this->SetFont('DejaVuSerif', '', 8);
        if (is_null($customer->ic)) {
            $this->Cell(60, 4, Settings::getString('core.documents.common.user_types.non_business'));
        } elseif (is_null($customer->billing_address->company)) {
            $this->Cell(60, 4, Settings::getString('core.documents.common.user_types.business'));
        } else {
            $this->Cell(60, 4, Settings::getString('core.documents.common.user_types.legal'));
        }
        $this->Ln();

        // Separator line
        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187.0, $this->GetY());
        $this->Ln(0.5);

        // Labels for personal vs business data
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->Cell(90, 4, Settings::getString('core.documents.common.labels.personal_data'));
        $this->Cell(90, 4, Settings::getString('core.documents.common.labels.business_data'));
        $this->Ln();

        // Customer personal and business data fields
        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(30, 4, Settings::getString('core.documents.common.labels.name'), align: 'R');
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->Cell(60, 4, $customer->billing_address->full_name);

        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(30, 4, Settings::getString('core.documents.common.labels.company'), align: 'R');
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->MultiCell(60, 4, $customer->billing_address->company ?? 'X', align: 'L');

        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(30, 4, Settings::getString('core.documents.common.labels.birth_date'), align: 'R');
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->Cell(60, 4, h($customer->date_of_birth));

        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(30, 4, Settings::getString('core.documents.common.labels.identity_number'), align: 'R');
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->Cell(60, 4, $customer->ic ?? 'X');
        $this->Ln();

        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(30, 4, Settings::getString('core.documents.common.labels.identity_card_number'), align: 'R');
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->Cell(60, 4, $customer->identity_card_number);

        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(30, 4, Settings::getString('core.documents.common.labels.vat_number'), align: 'R');
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->Cell(60, 4, $customer->dic ?? 'X');
        $this->Ln();

        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(30, 4, Settings::getString('core.documents.common.labels.phone'), align: 'R');
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->MultiCell(160, 4, $customer->phone, align: 'L');

        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(30, 4, Settings::getString('core.documents.common.labels.email'), align: 'R');
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->MultiCell(160, 4, $customer->email, align: 'L');

        // Addresses loop
        foreach ($customer->addresses as $address) {
            $this->SetFont('DejaVuSerif', 'B', 8);
            $this->Cell(30, 4, $address->type->label() . ': ');
            $this->Ln();
            $this->SetFont('DejaVuSerif', 'B', 8);
            $this->Cell(30, 4);
            $this->MultiCell(160, 4, $address->full_address, align: 'L');
        }
        $this->Line($this->GetX() + 4.0, $this->GetY(), $this->GetX() + 187.0, $this->GetY());
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
        $this->SetFont('DejaVuSerif', '', 8);
        if ($this->GetY() > 240) {
            $this->AddPage();
        }
        $this->Ln(10);
        $this->Cell(90, 4);
        $this->Cell(
            90,
            4,
            Settings::getString('core.documents.common.signatures.date') . ' ' . Settings::getString('core.documents.common.signatures.date_line'),
            align: 'C',
        );
        $this->Ln(20);
        $this->Cell(90, 4);
        $this->Cell(90, 4, Settings::getString('core.documents.common.signatures.sign_line'), align: 'C');
        $this->Ln();
        $this->Cell(90, 4);
        $this->Cell(90, 4, Settings::getString('core.documents.common.signatures.user'), align: 'C');

        $this->Close();
    }
}
