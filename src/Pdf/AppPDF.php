<?php
declare(strict_types=1);

namespace App\Pdf;

use App\Utility\Settings;
use Override;
use TCPDF;

class AppPDF extends TCPDF
{
    public const SEPARATOR_OFFSET_X = 4.0;

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
     * Draws a horizontal separator line followed by a line break.
     *
     * This helper is used to visually separate logical blocks in the PDF
     * (e.g. company details, user information, signatures). It draws a line
     * across the page starting from the current X/Y position with the given
     * offset and width, then moves the cursor down by the specified amount.
     *
     * @param float $offsetX Horizontal offset from the current X position (default 0.0)
     * @param float $width   Total line width (default 187.0)
     * @param float|null $lnBefore      Line break height before drawing (default null = disabled)
     * @param float|null $lnAfter      Line break height after drawing (default null = disabled)
     * @return void
     */
    protected function drawSeparator(
        float $offsetX = 0.0,
        float $width = 187.0,
        ?float $lnBefore = null,
        ?float $lnAfter = null,
    ): void {
        if (is_float($lnBefore)) {
            $this->Ln($lnBefore);
        }

        $this->Line($this->GetX() + $offsetX, $this->GetY(), $this->GetX() + $width, $this->GetY());

        if (is_float($lnAfter)) {
            $this->Ln($lnAfter);
        }
    }

    /**
     * Prints a standardized block with company details into the PDF.
     *
     * The block includes company name, address lines, identity number,
     * VAT number, phone, mobile, email, executive clause, and registry clause.
     * A horizontal line is drawn before and after the block to visually
     * delimit the section, with a small spacing added for readability.
     *
     * All values and labels are retrieved from the Settings configuration
     * to ensure consistency across different documents.
     *
     * @param string $roleLabel Label describing the company role
     *                          (e.g. "Provider", "Controller").
     * @return void
     */
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

        $this->Line($this->GetX() + 4.0, $this->GetY(), $this->GetX() + 187.0, $this->GetY());
        $this->Ln(3);
    }
}
