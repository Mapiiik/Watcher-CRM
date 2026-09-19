<?php
declare(strict_types=1);

namespace App\Pdf;

use App\Model\Entity\Contract;
use App\Model\Entity\Customer;
use App\Pdf\Trait\ProviderSignatureTrait;
use Override;
use Settings\Utility\Settings;

class AppPDF extends Canvas
{
    use ProviderSignatureTrait;

    /**
     * Where the page's furniture is drawn from: the rules, the headings and the blocks that
     * span the whole width. The body of a section sits inside it.
     */
    public const FRAME_LEFT = 10.0;

    /**
     * How far inside the rules the body of a section sits. Half of what the rules have over
     * the text, so the text stands the same distance from either end of the rule above it.
     */
    public const BODY_INDENT = (self::PAGE_WIDTH - self::TEXT_WIDTH) / 2;

    /**
     * A rule drawn to bracket the body rather than the section starts where the body does and
     * keeps the overhang at its far end.
     */
    public const SEPARATOR_OFFSET_X = self::BODY_INDENT;

    /**
     * The signature block: two columns of a fixed width, each row as tall as a line, with air
     * above the block and room between the dates and the lines they are signed on.
     */
    protected const SIGNATURE_COLUMN = 90.0;
    protected const SIGNATURE_ROW = 4.0;
    protected const SIGNATURE_GAP_ABOVE = 10.0;
    protected const SIGNATURE_GAP_WITHIN = 20.0;

    /**
     * How much room the whole block needs, worked out from its parts rather than stated, so that
     * moving any of them keeps the block from being started where it will not fit.
     */
    protected const SIGNATURE_BLOCK = self::SIGNATURE_GAP_ABOVE
        + self::SIGNATURE_GAP_WITHIN
        + (2 * self::SIGNATURE_ROW);

    /**
     * Where the signature itself is drawn: how far into its column, how wide, and how far above
     * the line it is signed on it starts.
     */
    protected const SIGNATURE_INDENT = 24.5;
    protected const SIGNATURE_WIDTH = 35.0;
    protected const SIGNATURE_RISE = 11.0;

    /**
     * Typeface the documents are set in. The summary overrides it, because the regulation
     * that prescribes it names a sans-serif as the readable choice.
     */
    protected const FONT_FAMILY = 'DejaVuSerif';

    /**
     * Body text, and section headings a step above it.
     */
    protected const BODY_FONT_SIZE = 8;
    protected const HEADING_FONT_SIZE = 9;

    /**
     * Footnotes and clauses that hang off a table, set below the body.
     */
    protected const NOTE_FONT_SIZE = 7;

    /**
     * Title block at the head of every document.
     */
    protected const TITLE_FONT_SIZE = 18;
    protected const SUBTITLE_FONT_SIZE = 12;

    /**
     * Line height that goes with the body size, and the air left behind a paragraph.
     */
    protected const LINE_HEIGHT = 4.0;
    protected const PARAGRAPH_GAP = 3.0;

    /**
     * Width of a flowing paragraph, and of the full text column the rules and centred
     * headings span.
     */
    protected const TEXT_WIDTH = 180.0;
    protected const PAGE_WIDTH = 187.0;

    /**
     * Air between the cells of the label and value table, in points. It is what the markup
     * that block used to be written as resolved to, and the documents are laid out to it.
     */
    protected const TABLE_CELL_SPACING = 2.0;

    /**
     * Height of a framed table row. Taller than a line of text, because a bordered cell needs
     * the air a plain one does not.
     */
    protected const TABLE_ROW_HEIGHT = 5.0;

    /**
     * Air between a table's caption and the table itself.
     */
    protected const TABLE_CAPTION_GAP = 4.0;

    /**
     * TCPDF's conditional horizontal scaling: condense the text only when it would not
     * otherwise fit its cell, and leave it alone when it does.
     */
    protected const STRETCH_TO_FIT = 1;

    /**
     * Whether blocks are kept whole across a page break. Off here, because a document laid
     * out around the breaks it has must keep them; a document turns it on for itself.
     */
    protected const KEEPS_BLOCKS_WHOLE = false;

    /**
     * Room a heading needs below it - itself and a couple of lines - before it is worth
     * starting a section on this page at all. Only consulted where blocks are kept whole.
     */
    protected const HEADING_ORPHAN_GUARD = 16.0;

    /**
     * A cell, with two habits of its own.
     *
     * Bordered cells centre their text vertically, because a frame drawn round text sitting
     * on the top edge reads as a mistake.
     *
     * And text too long for its cell is condensed to fit rather than allowed to run past its
     * edge. A cell cannot wrap - that is what MultiCell is for - so without this the overflow
     * lands on whatever comes next, which on these documents is usually a price. The scaling
     * engages only when the text would not otherwise fit, so nothing that fits is touched.
     *
     * This is only honest if every cell is declared as wide as the room it actually has: one
     * followed by a blank spacer owns that spacer's width too, and saying otherwise condenses
     * text that had somewhere to go.
     *
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
        mixed $stretch = self::STRETCH_TO_FIT,
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
     * A rule is furniture, so it is drawn from the page's left edge rather than from wherever
     * the text has got to. That is what lets the body sit inside it.
     *
     * @param float $offsetX Horizontal offset from the page's left edge (default 0.0)
     * @param float $width Total line width, measured from that same edge
     * @param float|null $lnBefore      Line break height before drawing (default null = disabled)
     * @param float|null $lnAfter      Line break height after drawing (default null = disabled)
     * @return void
     */
    protected function drawSeparator(
        float $offsetX = 0.0,
        float $width = self::PAGE_WIDTH,
        ?float $lnBefore = null,
        ?float $lnAfter = null,
    ): void {
        if (is_float($lnBefore)) {
            $this->Ln($lnBefore);
        }

        $left = static::FRAME_LEFT;
        $this->Line($left + $offsetX, $this->GetY(), $left + $width, $this->GetY());

        if (is_float($lnAfter)) {
            $this->Ln($lnAfter);
        }
    }

    /**
     * Reads one of the labels every document shares.
     *
     * @param string $key Key under the common labels block
     * @return string
     */
    protected function label(string $key): string
    {
        return Settings::getString('core.documents.common.labels.' . $key);
    }

    /**
     * Reads one of the texts every document says about paying.
     *
     * @param string $key Key under the common payment block
     * @return string
     */
    protected function paymentText(string $key): string
    {
        return Settings::getString('core.documents.common.payment.' . $key);
    }

    /**
     * Opens a document: logo, title, subtitle and the rule that closes the block.
     *
     * Every document this application prints starts the same way, and it is what makes a
     * contract, its handover protocol and its summary read as one set of papers.
     *
     * @param string $title Title, set large and centred
     * @param string $subtitle Subtitle directly beneath it
     * @param string|null $overline A line above the title, where a document needs one
     * @return void
     */
    protected function printDocumentHeader(string $title, string $subtitle, ?string $overline = null): void
    {
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);

        $this->AddPage();
        $this->frameBody();

        $this->Image(K_PATH_IMAGES . 'logo-contract.png', static::FRAME_LEFT, 5, 28);

        if ($overline !== null && $overline !== '') {
            $this->SetFont(static::FONT_FAMILY, 'B', static::BODY_FONT_SIZE);
            $this->printFullWidth($overline, 4);
            $this->Ln(5);
        }

        $this->SetFont(static::FONT_FAMILY, 'B', static::TITLE_FONT_SIZE);
        $this->printFullWidth($title, 6);
        $this->Ln();

        $this->SetFont(static::FONT_FAMILY, 'B', static::SUBTITLE_FONT_SIZE);
        $this->printFullWidth($subtitle, 2);
        $this->Ln(3);

        $this->drawSeparator(lnBefore: 4, lnAfter: 0.5);
    }

    /**
     * Sets the document's body inside the rules, once, for the whole document.
     *
     * Both margins are pulled in by the same indent, so a line that wraps ends as far inside
     * the rules as it began inside them. What spans the whole width steps back out to them.
     *
     * @return void
     */
    protected function frameBody(): void
    {
        $this->SetLeftMargin(static::FRAME_LEFT + static::BODY_INDENT);
        $this->SetRightMargin(
            $this->getPageWidth() - static::FRAME_LEFT - static::PAGE_WIDTH + static::BODY_INDENT,
        );
        $this->SetXY(static::FRAME_LEFT + static::BODY_INDENT, $this->GetY());
    }

    /**
     * Puts the cursor at the page's left edge, where the furniture is drawn from.
     *
     * Everything that spans the whole width - the headings, the party blocks, the rows of
     * columns - starts here rather than where the body flows, so it lines up with the rules.
     *
     * @return void
     */
    protected function frameLeft(): void
    {
        $this->SetXY(static::FRAME_LEFT, $this->GetY());
    }

    /**
     * A heading set from the page's left edge, wrapping back to it.
     *
     * @param string $text What to set
     * @param float $height Height of a line
     * @return void
     */
    protected function printFrameHeading(string $text, float $height): void
    {
        $this->writeFrom(static::FRAME_LEFT, $text, $height);
    }

    /**
     * The line that introduces a table.
     *
     * It belongs to the table under it rather than to the prose around it, so it stands
     * between the two: half the body's indent, close enough to the section heading to read as
     * its own thing without lining up with it.
     *
     * The air below it is the caption's own, and it is less than a paragraph leaves behind. A
     * caption that stands as far from its table as from the text above it belongs to neither.
     *
     * The face is the caller's, because one of these is underlined and another carries the
     * weight of a statement.
     *
     * @param string $text What to set
     * @return void
     */
    protected function printTableCaption(string $text): void
    {
        $this->writeFrom(static::FRAME_LEFT + static::BODY_INDENT / 2, $text, static::LINE_HEIGHT);
        $this->Ln(static::TABLE_CAPTION_GAP);
    }

    /**
     * Writes flowing text from somewhere other than where the body begins.
     *
     * `Write()` takes the left margin for the lines it wraps onto, so anything set outside the
     * body would drop back into it on a second line. The margin is moved for the duration and
     * put back, which is the only way the continuation lands under the line it belongs to.
     *
     * @param float $left Where the text begins and wraps back to
     * @param string $text What to set
     * @param float $height Height of a line
     * @return void
     */
    private function writeFrom(float $left, string $text, float $height): void
    {
        $this->SetLeftMargin($left);
        $this->SetXY($left, $this->GetY());
        $this->Write($height, $text);
        $this->SetLeftMargin(static::FRAME_LEFT + static::BODY_INDENT);
    }

    /**
     * One line centred across the whole width, from the page's left edge.
     *
     * @param string $text What to set
     * @param float $height Height of the line
     * @return void
     */
    protected function printFullWidth(string $text, float $height): void
    {
        $this->frameLeft();
        $this->Cell(static::PAGE_WIDTH, $height, $text, align: 'C');
    }

    /**
     * Prints a row of centred labels over their values, closed by a rule.
     *
     * This is how every document states what it is about - contract number, dates, the
     * number of the amendment - and the columns are shared out evenly across the width.
     *
     * The columns divide the width the rule beneath them spans, not the narrower one the
     * paragraphs are set to - the row sits between two rules and belongs to them.
     *
     * @param array<int, array{0:string, 1:string}> $columns Label and value pairs
     * @param float|null $width Column width, or null to divide the row evenly
     * @param float $separatorOffset Indent of the closing rule
     * @return void
     */
    protected function printLabelledRow(
        array $columns,
        ?float $width = null,
        float $separatorOffset = self::SEPARATOR_OFFSET_X,
    ): void {
        $widths = $width === null
            ? $this->shareOutColumns($columns)
            : array_fill(0, count($columns), $width);

        $this->SetFont(static::FONT_FAMILY, '', static::BODY_FONT_SIZE);
        $this->frameLeft();
        foreach ($columns as $index => [$label, $value]) {
            $this->Cell($widths[$index], static::LINE_HEIGHT, $label, align: 'C');
        }
        $this->Ln();

        $this->SetFont(static::FONT_FAMILY, 'B', static::BODY_FONT_SIZE);
        $this->frameLeft();
        foreach ($columns as $index => [$label, $value]) {
            $this->Cell($widths[$index], static::LINE_HEIGHT, $value, align: 'C');
        }
        $this->Ln();

        $this->drawSeparator($separatorOffset, lnAfter: 3.0);
    }

    /**
     * Divides the row between its columns.
     *
     * Every column starts with an equal share, and one whose heading will not fit that share
     * takes what it needs from the columns that have room to spare. Dates are not all the same
     * length in Czech, and a heading squeezed to fit while the column beside it sits half
     * empty is the layout admitting it never looked.
     *
     * @param array<int, array{0:string, 1:string}> $columns Label and value pairs
     * @return array<int, float> Width of each column, together the width of the row
     */
    private function shareOutColumns(array $columns): array
    {
        $count = max(1, count($columns));
        $share = static::PAGE_WIDTH / $count;
        $padding = $this->getCellPaddings();

        $needed = [];
        foreach ($columns as [$label, $value]) {
            $this->SetFont(static::FONT_FAMILY, '', static::BODY_FONT_SIZE);
            $labelWidth = $this->GetStringWidth($label);
            $this->SetFont(static::FONT_FAMILY, 'B', static::BODY_FONT_SIZE);
            $needed[] = max($labelWidth, $this->GetStringWidth($value)) + $padding['L'] + $padding['R'];
        }

        $widths = array_map(fn(float $want): float => max($share, $want), $needed);
        $spare = array_map(fn(float $has, float $want): float => $has - $want, $widths, $needed);

        $excess = array_sum($widths) - static::PAGE_WIDTH;
        $roomToGive = array_sum($spare);

        if ($excess > 0.0 && $roomToGive > 0.0) {
            $taken = min($excess, $roomToGive);
            $widths = array_map(
                fn(float $has, float $room): float => $has - $taken * $room / $roomToGive,
                $widths,
                $spare,
            );
        }

        // If every column wanted more than its share there was nothing to give, and the row
        // would run off the page. It is brought back to width and the cells condense what is
        // left over, which is the same answer a single column too narrow already gets.
        $total = array_sum($widths);
        if ($total > static::PAGE_WIDTH) {
            $widths = array_map(fn(float $has): float => $has * static::PAGE_WIDTH / $total, $widths);
        }

        return $widths;
    }

    /**
     * Names the two parties: the provider's own block, then who the other one is.
     *
     * @param string $roleLabel What the company is in this document - provider, controller
     * @param \App\Model\Entity\Customer|\App\Model\Entity\Contract $entity Whose type is stated
     * @return void
     */
    protected function printParties(string $roleLabel, Customer|Contract $entity): void
    {
        $this->printCompanyDetails($roleLabel);

        $this->SetFont(static::FONT_FAMILY, 'B', static::HEADING_FONT_SIZE);
        $this->printFullWidth($this->label('and'), 4);
        $this->Ln();
        $this->frameLeft();
        $this->Cell(30, 4, $this->label('user'));

        $this->SetFont(static::FONT_FAMILY, '', static::BODY_FONT_SIZE);
        $this->printUserType($entity);
    }

    /**
     * Prints a section heading over a rule.
     *
     * A heading left stranded at the foot of a page with its text overleaf reads as a
     * mistake, so where a document asks for the guard the heading takes the break with it.
     *
     * @param string $text The heading
     * @return void
     */
    protected function printSectionHeading(string $text): void
    {
        $this->keepTogether(static::HEADING_ORPHAN_GUARD);

        $this->SetFont(static::FONT_FAMILY, 'B', static::HEADING_FONT_SIZE);
        $this->printFrameHeading($text, 4);
        $this->Ln();

        $this->drawSeparator(lnBefore: 0.4, lnAfter: 1.0);
    }

    /**
     * Prints one paragraph of body text and the air that separates it from the next.
     *
     * The trailing newline is what keeps a justified last line from being stretched across
     * the full width, and every flowing paragraph in these documents needs it.
     *
     * @param string $text The paragraph
     * @param string $align Alignment, justified unless the text reads better ragged
     * @param bool $bold Whether the paragraph carries the weight of a statement
     * @param string $format Extra font style, used where a document sets a whole block apart
     * @param float|null $gap Air left behind it, or null for the document's own
     * @return void
     */
    protected function printParagraph(
        string $text,
        string $align = 'J',
        bool $bold = false,
        string $format = '',
        ?float $gap = null,
    ): void {
        $this->SetFont(static::FONT_FAMILY, ($bold ? 'B' : '') . $format, static::BODY_FONT_SIZE);

        $body = $text . PHP_EOL;
        $this->keepTogether($this->getNumLines($body, static::TEXT_WIDTH) * static::LINE_HEIGHT);
        $this->MultiCell(static::TEXT_WIDTH, static::LINE_HEIGHT, $body, align: $align);
        $this->Ln($gap ?? static::PARAGRAPH_GAP);
    }

    /**
     * One row of a framed table.
     *
     * Weight is given per row, or per cell where a row states a figure against its label and
     * only the figure carries the weight.
     *
     * A name too long for its cell is condensed to fit rather than allowed to run across the
     * column beside it, the same way the billing table has always handled its service names.
     * The scaling only engages when the text would not otherwise fit, so nothing that fits is
     * touched, and the row keeps the height the rest of the table has.
     *
     * @param array<int, string> $cells Cell contents
     * @param array<int, float|int> $widths Cell widths in mm
     * @param array<int, string> $aligns Cell alignments
     * @param array<int, bool>|bool $bold Whether the row, or each of its cells, is set bold
     * @return void
     */
    protected function printFramedRow(array $cells, array $widths, array $aligns, array|bool $bold = false): void
    {
        foreach ($cells as $index => $cell) {
            $weight = is_array($bold) ? ($bold[$index] ?? false) : $bold;
            $this->SetFont(static::FONT_FAMILY, $weight ? 'B' : '', static::BODY_FONT_SIZE);
            $this->Cell(
                $widths[$index],
                static::TABLE_ROW_HEIGHT,
                $cell,
                border: 1,
                align: $aligns[$index],
                stretch: self::STRETCH_TO_FIT,
            );
        }

        $this->Ln();
    }

    /**
     * Empty framed rows, so a document filled in by hand has room to write in.
     *
     * @param int $count How many rows
     * @param array<int, float|int> $widths Cell widths in mm
     * @return void
     */
    protected function printBlankRows(int $count, array $widths): void
    {
        for ($i = 1; $i <= $count; $i++) {
            foreach ($widths as $width) {
                $this->Cell($width, static::TABLE_ROW_HEIGHT, '', border: 1, align: 'C');
            }
            $this->Ln();
        }
    }

    /**
     * Starts a new page if what comes next would not fit whole on this one.
     *
     * A paragraph broken over the fold is read twice - once to lose the thread and once to
     * find it again - and a table split from its heading says nothing at all. Anything taller
     * than a page is let through, because moving it would only move the problem.
     *
     * Documents opt into this: one that was laid out around where its pages happen to break
     * leaves it off and keeps the breaks it has.
     *
     * @param float $height How much room the block needs, in mm
     * @return void
     */
    protected function keepTogether(float $height): void
    {
        if (!static::KEEPS_BLOCKS_WHOLE || $height <= 0.0) {
            return;
        }

        $bottom = $this->getPageHeight() - $this->getBreakMargin();

        if ($height < $bottom - $this->getMargins()['top'] && $this->GetY() + $height > $bottom) {
            $this->AddPage();
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
     * Furniture, like the rules, so it is drawn from the page's left edge and stands around
     * the body rather than starting where the body does.
     *
     * @param float $lnBefore Line break height before drawing (default 5.0)
     * @param float $width    Total width of the frame
     * @param float $bottomY  Y‑coordinate of the bottom line (default 285.0)
     * @return void
     */
    protected function drawCross(
        float $lnBefore = 5.0,
        float $width = self::PAGE_WIDTH,
        float $bottomY = 285.0,
    ): void {
        $this->Ln($lnBefore);

        $x = static::FRAME_LEFT;
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
        $this->frameLeft();
        $this->Cell(45, 4, $roleLabel);
        $this->Ln();

        $this->drawSeparator(lnAfter: 1.0);

        // Each value is declared as wide as the room it actually has before the next thing
        // with text in it, blank columns included, so it is only condensed when it truly
        // overruns rather than whenever it crosses a spacer.
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->frameLeft();
        $this->Cell(30, 4);
        $this->Cell(110, 4, Settings::getString('core.company.name'));
        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(15, 4, Settings::getString('core.documents.common.labels.phone'));
        $this->Cell(40, 4, Settings::getString('core.company.phone'));
        $this->Ln();

        $this->frameLeft();
        $this->Cell(30, 4);
        $this->Cell(60, 4, Settings::getString('core.company.address_line_1'));
        $this->Cell(10, 4, Settings::getString('core.documents.common.labels.identity_number'));
        $this->Cell(40, 4, Settings::getString('core.company.identity_number'));
        $this->Cell(15, 4, Settings::getString('core.documents.common.labels.mobile'));
        $this->Cell(40, 4, Settings::getString('core.company.mobile'));
        $this->Ln();

        $this->frameLeft();
        $this->Cell(30, 4);
        $this->Cell(60, 4, Settings::getString('core.company.address_line_2'));
        $this->Cell(10, 4, Settings::getString('core.documents.common.labels.vat_number'));
        $this->Cell(40, 4, Settings::getString('core.company.vat_number'));
        $this->Cell(15, 4, Settings::getString('core.documents.common.labels.email'));
        $this->Cell(40, 4, Settings::getString('core.company.email'));
        $this->Ln();

        $this->Ln(3);
        $this->SetFont('DejaVuSerif', '', 8);
        $this->frameLeft();
        $this->Cell(30, 4);
        $this->MultiCell(157, 4, Settings::getString('core.company.executive_clause'), align: 'L');
        $this->frameLeft();
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
     *
     * Nobody's signature is set into it. The block comes out the same however far along the
     * paperwork is, and a signature is drawn on top of it afterwards - which is what lets a paper
     * already on file be countersigned rather than drawn again. Where the drawing goes is written
     * down here, as marks the finished document carries.
     *
     * @param string $layout Layout identifier: 'single-right' or 'double'.
     * @return void
     */
    protected function printSignatureSection(string $layout = 'double'): void
    {
        $this->SetFont('DejaVuSerif', '', 8);

        // The whole block or none of it. Asked as the room it actually needs rather than as a
        // height the page happens to have reached: a block that starts low enough to be split
        // leaves the signature on one page and the line it belongs to on the next.
        $this->checkPageBreak(static::SIGNATURE_BLOCK);

        $dateLabel = Settings::getString('core.documents.common.signatures.date');
        $dateLine = Settings::getString('core.documents.common.signatures.date_line');
        $signLine = Settings::getString('core.documents.common.signatures.sign_line');
        $provider = Settings::getString('core.documents.common.signatures.provider');
        $user = Settings::getString('core.documents.common.signatures.user');

        $double = ($layout === 'double');
        $this->Ln(static::SIGNATURE_GAP_ABOVE);

        // Date row. The same line on both sides whoever has signed: a date is written on top of
        // it afterwards rather than set in place of it, which is what lets a paper already on
        // file be countersigned without being drawn a second time.
        $dateText = $dateLabel . ' ' . $dateLine;
        $left = $this->GetX();

        $this->Cell(static::SIGNATURE_COLUMN, static::SIGNATURE_ROW, $double ? $dateText : '', align: 'C');
        $this->Cell(static::SIGNATURE_COLUMN, static::SIGNATURE_ROW, $dateText, align: 'C');

        // Read afterwards rather than before: a row that has broken onto the next page has taken
        // both of its cells with it, and the mark has to say where they ended up.
        if ($double) {
            $this->markDate(SignatureAnchors::PROVIDER_DATE, $left, $dateText, $dateLabel . ' ');
        }
        $this->markDate(
            SignatureAnchors::CUSTOMER_DATE,
            $left + static::SIGNATURE_COLUMN,
            $dateText,
            $dateLabel . ' ',
        );

        $this->Ln(static::SIGNATURE_GAP_WITHIN);

        // Sign line row
        $left = $this->GetX();

        $this->Cell(static::SIGNATURE_COLUMN, static::SIGNATURE_ROW, $double ? $signLine : '', align: 'C');
        $this->Cell(static::SIGNATURE_COLUMN, static::SIGNATURE_ROW, $signLine, align: 'C');

        if ($double) {
            $this->markSignature(SignatureAnchors::PROVIDER_SIGNATURE, $left);
        }
        $this->markSignature(SignatureAnchors::CUSTOMER_SIGNATURE, $left + static::SIGNATURE_COLUMN);

        $this->Ln();

        // Role labels row
        $this->Cell(static::SIGNATURE_COLUMN, static::SIGNATURE_ROW, $double ? $provider : '', align: 'C');
        $this->Cell(static::SIGNATURE_COLUMN, static::SIGNATURE_ROW, $user, align: 'C');
        $this->Ln();
    }

    /**
     * Marks where a date is written on one side of the block.
     *
     * The mark covers the line and not the words before it, so that what is written lands where
     * somebody signing by hand would write it.
     *
     * @param string $name What the mark is for.
     * @param float $left Left edge of the column it is in.
     * @param string $text The whole of what the cell says.
     * @param string $label The part of it that is not the line.
     * @return void
     */
    private function markDate(string $name, float $left, string $text, string $label): void
    {
        $whole = $this->GetStringWidth($text);
        $words = $this->GetStringWidth($label);
        $inset = $this->paddingX + ((static::SIGNATURE_COLUMN - (2 * $this->paddingX) - $whole) / 2);

        $this->anchors()->add(new SignatureAnchor(
            $name,
            $this->PageNo(),
            $left + $inset + $words,
            $this->GetY(),
            $whole - $words,
            static::SIGNATURE_ROW,
        ));
    }

    /**
     * Marks where a signature goes on one side of the block.
     *
     * @param string $name What the mark is for.
     * @param float $left Left edge of the column it is in.
     * @return void
     */
    private function markSignature(string $name, float $left): void
    {
        $this->anchors()->add(new SignatureAnchor(
            $name,
            $this->PageNo(),
            $left + static::SIGNATURE_INDENT,
            $this->GetY() - static::SIGNATURE_RISE,
            static::SIGNATURE_WIDTH,
        ));
    }

    /**
     * A table of labels and the values against them.
     *
     * Each cell may say how wide its label and its value are:
     *   ['label' => 'Name', 'value' => 'John Doe', 'label_width' => 25, 'value_width' => 80]
     *
     * The block used to be written as markup and set by the old engine's HTML renderer. It
     * is drawn directly now, to the geometry that markup resolved to: cells a fixed gap
     * apart, and a line box a quarter taller than the text standing in it.
     *
     * @param array<int,string> $headers Header titles, or nothing for a table without them
     * @param array<int,array<int,array{
     *     label:string,
     *     value:string,
     *     label_width?:int,
     *     value_width?:int
     * }>> $rows Rows, each a list of label and value pairs
     */
    protected function printTable(array $headers, array $rows): void
    {
        $left = $this->GetX();
        $top = $this->GetY();

        if ($headers !== []) {
            $width = 180 / count($headers);
            $top = $this->printTableRow(
                array_map(fn(string $header): array => [$header, $width, true, 'L'], $headers),
                $left,
                $top,
            );
        }

        foreach ($rows as $row) {
            $cells = [];
            foreach ($row as $cell) {
                // A label is set against its value rather than away from it, and a row with
                // one thing in it gives that value the rest of the width.
                $cells[] = [$cell['label'], (float)($cell['label_width'] ?? 30), false, 'R'];
                $cells[] = [
                    $cell['value'],
                    (float)($cell['value_width'] ?? (count($row) === 1 ? 150 : 60)),
                    true,
                    'L',
                ];
            }

            $top = $this->printTableRow($cells, $left, $top);
        }

        // The gap runs all the way round, so the table closes with one under its last row
        // the same way it opened with one above its first.
        $this->SetXY($left, $top + (static::TABLE_CELL_SPACING / static::K));
    }

    /**
     * One row of that table, and where the next one starts.
     *
     * The values are set bold and the labels are not, and the two do not sit on the same
     * line: each cell hangs from the top of the row by its own height above the baseline,
     * so a bold one sits a hair lower. That is what the old renderer did, and a row set any
     * other way no longer lines up with the one above it.
     *
     * The last cell takes whatever width the row has left, the way a table cell does, and
     * a cell wraps against its full width - the air it keeps to either side of its text is
     * where the text starts, not what it is allowed to fill.
     *
     * @param array<int, array{0:string, 1:float, 2:bool, 3:string}> $cells Text, width,
     *   weight and which edge of the cell the text is set against
     *
     * @param float $left Left edge of the table
     * @param float $top Top edge of this row
     * @return float Top edge of the row after it
     */
    private function printTableRow(array $cells, float $left, float $top): float
    {
        $spacing = static::TABLE_CELL_SPACING / static::K;
        $lineHeight = static::BODY_FONT_SIZE * static::CELL_HEIGHT_RATIO / static::K;

        $last = count($cells) - 1;
        $spare = static::TEXT_WIDTH - array_sum(array_column($cells, 1));

        $placed = [];
        $depth = 1;
        $x = $left + $spacing;

        foreach ($cells as $index => [$text, $width, $bold, $align]) {
            $width += $index === $last ? max(0.0, $spare) : 0.0;

            $this->SetFont(static::FONT_FAMILY, $bold ? 'B' : '', static::BODY_FONT_SIZE);

            $lines = $text === '' ? [] : $this->splitLines($text, $width);
            $placed[] = [$x, $width, $lines, $bold, $align, $this->fontAscent()];
            $depth = max($depth, count($lines));

            $x += $width + $spacing;
        }

        foreach ($placed as [$x, $width, $lines, $bold, $align, $ascent]) {
            $this->SetFont(static::FONT_FAMILY, $bold ? 'B' : '', static::BODY_FONT_SIZE);

            foreach ($lines as $index => $line) {
                $this->drawTextAt(
                    $line,
                    $align === 'R'
                        ? $x + $width + $this->paddingX - $this->GetStringWidth($line)
                        : $x + $this->paddingX,
                    $top + $spacing + ($index * $lineHeight) + $ascent,
                );
            }
        }

        return $top + $spacing + ($depth * $lineHeight);
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

        // The title has the line to itself, so it is given the whole column; the indent is
        // only there to place the address underneath it.
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->Cell(static::TEXT_WIDTH, $lineHeight, $title . ': ');
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
