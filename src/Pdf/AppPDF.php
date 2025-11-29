<?php
declare(strict_types=1);

namespace App\Pdf;

use App\Utility\Settings;
use Override;
use TCPDF;

class AppPDF extends TCPDF
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

    protected function drawSeparator(float $offsetX = 4.0, float $width = 187.0, float $ln = 3.0): void
    {
        $this->Line($this->GetX() + $offsetX, $this->GetY(), $this->GetX() + $width, $this->GetY());
        $this->Ln($ln);
    }

    protected function printCompanyDetails(string $roleLabel): void
    {
        $this->SetFont('DejaVuSerif', 'B', 9);
        $this->Cell(45, 4, $roleLabel);
        $this->Ln();
        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187.0, $this->GetY());

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
        $this->Cell(15, 4, Settings::getString('core.documents.common.labels.email'));
        $this->Cell(40, 4, Settings::getString('core.company.email'));
        $this->Ln();

        $this->Ln(3);
        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(30, 4);
        $this->MultiCell(157, 4, Settings::getString('core.company.executive_clause'), align: 'L');
        $this->Cell(30, 4);
        $this->MultiCell(157, 4, Settings::getString('core.company.registry_clause'), align: 'L');
    }
}
