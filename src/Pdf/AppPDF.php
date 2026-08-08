<?php
declare(strict_types=1);

namespace App\Pdf;

use App\Model\Entity\Contract;
use App\Model\Entity\Customer;
use Cake\Core\Configure;
use Cake\I18n\Date;
use Override;
use Settings\Utility\Settings;
use TCPDF;

//set image path for TCPDF
define('K_PATH_IMAGES', Configure::read('Data.root') . DS . 'images' . DS);

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
     * Draws a cross (frame with diagonals) inside the PDF.
     *
     * This helper is used to visually mark a section with a rectangular
     * border and two diagonal lines. It starts with an optional line break
     * before drawing, then renders:
     *  - top horizontal line
     *  - bottom horizontal line
     *  - left and right vertical lines
     *  - two diagonals (\ and /)
     *
     * @param float $lnBefore Line break height before drawing (default 5.0)
     * @param float $width    Total width of the frame (default 187.0)
     * @param float $bottomY  Y‑coordinate of the bottom line (default 285.0)
     * @return void
     */
    protected function drawCross(float $lnBefore = 5.0, float $width = 187.0, float $bottomY = 285.0): void
    {
        $this->Ln($lnBefore);

        $x = $this->GetX();
        $y = $this->GetY();

        // Top horizontal line
        $this->Line($x, $y, $x + $width, $y);

        // Diagonals
        $this->Line($x, $y, $x + $width, $bottomY); // \
        $this->Line($x, $bottomY, $x + $width, $y); // /

        // Vertical lines
        $this->Line($x, $y, $x, $bottomY); // |
        $this->Line($x + $width, $y, $x + $width, $bottomY); // |

        // Bottom horizontal line
        $this->Line($x, $bottomY, $x + $width, $bottomY);
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

        $this->drawSeparator(lnAfter: 1.0);

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

        $this->drawSeparator(self::SEPARATOR_OFFSET_X, lnAfter: 3.0);
    }

    /**
     * Prints the signature section with an exact layout match to existing PDFs.
     *
     * Supported layouts:
     * - "single-right": matches CustomerPDF (one signature column on the right, 90 mm offset)
     * - "double": matches ContractPDF and HandoverProtocol (two columns side by side)
     *
     * Behavior:
     * - Adds a page when near bottom (Y > 240), then inserts consistent spacing.
     * - Prints date line(s), then a fixed vertical gap, then dotted sign line(s),
     *   then party labels ("Privider"/"User").
     * - For "double" layout with $signed=true: left date shows current date; also draws a signature image
     *   at the same coordinates as before (x=38.0, width=35.0, y=currentY-19.0).
     *
     * @param string $layout Layout identifier: 'single-right' or 'double'.
     * @param bool   $signed Whether the document is signed (affects left date and signature image in 'double').
     * @return void
     */
    protected function printSignatureSection(string $layout = 'double', bool $signed = false): void
    {
        $this->SetFont('DejaVuSerif', '', 8);

        if ($this->GetY() > 240) {
            $this->AddPage();
        }

        $dateLabel = Settings::getString('core.documents.common.signatures.date');
        $dateLine = Settings::getString('core.documents.common.signatures.date_line');
        $signLine = Settings::getString('core.documents.common.signatures.sign_line');
        $provider = Settings::getString('core.documents.common.signatures.provider');
        $user = Settings::getString('core.documents.common.signatures.user');

        $double = ($layout === 'double');
        $this->Ln(10);

        // Date row
        $leftDateText = $signed && $double ? Date::now()->__toString() : $dateLine;
        $this->Cell(90, 4, $double ? $dateLabel . ' ' . $leftDateText : '', align: 'C');
        $this->Cell(90, 4, $dateLabel . ' ' . $dateLine, align: 'C');
        $this->Ln(20);

        // Sign line row
        $this->Cell(90, 4, $double ? $signLine : '', align: 'C');
        $this->Cell(90, 4, $signLine, align: 'C');
        $this->Ln();

        // Role labels row
        $this->Cell(90, 4, $double ? $provider : '', align: 'C');
        $this->Cell(90, 4, $user, align: 'C');
        $this->Ln();

        // Signature image (only for double + signed)
        if ($double && $signed) {
            $this->Image(K_PATH_IMAGES . 'signature.png', 38.0, $this->GetY() - 19.0, 35.0);
        }
    }

    /**
     * Render a flexible two-column table using TCPDF's writeHTML.
     *
     * Each cell may optionally define custom widths:
     *   ['label' => 'Name', 'value' => 'John Doe', 'label_width' => 25, 'value_width' => 80]
     *
     * @param array<int,string> $headers Array of header titles (e.g. ["Personal data", "Business data"])
     * @param array<int,array<int,array{
     *     label:string,
     *     value:string,
     *     label_width?:int,
     *     value_width?:int
     * }>> $rows Array of rows, each row is an array of associative arrays
     */
    protected function printTable(array $headers, array $rows): void
    {
        $html = '<table border="0" cellpadding="0" cellspacing="2">';

        // Header row
        if ($headers !== []) {
            $html .= '<tr>';
            foreach ($headers as $header) {
                $html .= '<td width="' . (180 / count($headers)) . 'mm" align="left">'
                    . '<b>' . htmlspecialchars($header) . '</b></td>';
            }
            $html .= '</tr>';
        }

        // Data rows
        foreach ($rows as $row) {
            $html .= '<tr>';
            if (count($row) === 1) {
                // Single cell row (e.g. phone/email)
                $labelWidth = $row[0]['label_width'] ?? 30;
                $valueWidth = $row[0]['value_width'] ?? 150;

                $html .= '<td width="' . $labelWidth . 'mm" align="right">'
                    . htmlspecialchars($row[0]['label']) . '</td>';
                $html .= '<td width="' . $valueWidth . 'mm" colspan="' . (count($headers) * 2 - 1) . '">'
                    . '<b>' . htmlspecialchars($row[0]['value']) . '</b></td>';
            } else {
                // Multi-column row
                foreach ($row as $cell) {
                    $labelWidth = (string)($cell['label_width'] ?? 30);
                    $valueWidth = (string)($cell['value_width'] ?? 60);

                    $html .= '<td width="' . $labelWidth . 'mm" align="right">'
                        . htmlspecialchars($cell['label']) . '</td>';
                    $html .= '<td width="' . $valueWidth . 'mm">'
                        . '<b>' . htmlspecialchars($cell['value']) . '</b></td>';
                }
            }
            $html .= '</tr>';
        }

        $html .= '</table>';

        $this->writeHTML($html, true, false, false, true, '');
        $this->Ln(0);
    }

    /**
     * Print the user type (non-business, business, legal) into a cell.
     *
     * Works for both \App\Model\Entity\Customer and \App\Model\Entity\Contract entities.
     *
     * Logic:
     * - If identity_number is null → non-business
     * - If company is null → business
     * - Otherwise → legal entity
     *
     * @param \App\Model\Entity\Customer|\App\Model\Entity\Contract $entity Customer or Contract entity
     * @param int                                                  $width  Cell width in mm (default 60)
     * @param int                                                  $height Cell height in mm (default 4)
     */
    protected function printUserType(Customer|Contract $entity, int $width = 60, int $height = 4): void
    {
        // Normalize: get identity_number and company regardless of entity type
        $identity_number = $entity instanceof Contract ? $entity->customer->identity_number : $entity->identity_number;
        $company = $entity->billing_address->company ?? null;

        if (is_null($identity_number)) {
            $text = Settings::getString('core.documents.common.user_types.non_business');
        } elseif (is_null($company)) {
            $text = Settings::getString('core.documents.common.user_types.business');
        } else {
            $text = Settings::getString('core.documents.common.user_types.legal');
        }

        $this->Cell($width, $height, $text);
    }

    /**
     * Render a labeled single-column address block.
     *
     * @param string $title Section title (e.g. "Installation Address")
     * @param string|null $fullAddress Address text; if null/empty, the block is skipped
     * @param float $indent Label cell width before the address value (default 30mm)
     * @param float $lineHeight Line height for the value (default 4mm)
     */
    protected function printAddressBlock(
        string $title,
        ?string $fullAddress,
        float $indent = 30.0,
        float $lineHeight = 4.0,
    ): void {
        if ($fullAddress === null || $fullAddress === '') {
            return;
        }

        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->Cell($indent, $lineHeight, $title . ': ');
        $this->Ln();
        $this->Cell($indent, $lineHeight);
        $this->MultiCell(180 - $indent, $lineHeight, $fullAddress, align: 'L');
    }

    /**
     * Normalize a nullable string into a safe string for PDF output.
     *
     * This helper ensures that values which may be null are always converted
     * into a valid string. It is primarily used when rendering customer or
     * contract data where some fields (e.g. company, VAT number) can be null.
     *
     * Example:
     *   $this->strOrX($customer->identity_number);          // returns "12345678" or "X"
     *   $this->strOrX($customer->vat_number, '-');    // returns "CZ12345678" or "-"
     *
     * @param string|null $value   The original value which may be null
     * @param string      $fallback Fallback string to use if $value is null (default "X")
     * @return string               Always returns a string suitable for PDF output
     */
    protected function strOrX(?string $value, string $fallback = 'X'): string
    {
        return $value ?? $fallback;
    }
}
