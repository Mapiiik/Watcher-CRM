<?php
declare(strict_types=1);

namespace App\Pdf;

use Cake\Core\Configure;
use Com\Tecnick\Pdf\Page\Unit;
use Com\Tecnick\Pdf\Tcpdf as Engine;

//set image path for the documents
define('K_PATH_IMAGES', Configure::read('Data.root') . DS . 'images' . DS);

//set the font path to the faces the documents are set in
define('K_PATH_FONTS', dirname(__DIR__, 2) . DS . 'resources' . DS . 'fonts');

/**
 * A page written from the top down.
 *
 * The engine underneath places everything by absolute coordinate. The documents above were
 * laid out against a cursor - a cell moves it right, a line break moves it down - so the
 * cursor lives here, together with the geometry the documents were measured against.
 *
 * Everything is kept in millimetres, the way the documents state their sizes, and converted
 * to points only where the page is written.
 */
class Canvas
{
    /**
     * Points per millimetre.
     */
    protected const K = 2.8346456692913;

    /**
     * Margins the documents were laid out against, in points, and the deeper one at the foot
     * of the page that a block must not run into.
     */
    protected const MARGIN = 28.35;
    protected const MARGIN_BOTTOM = 56.7;

    /**
     * Air a cell keeps to the left and right of its text. There is none above or below it -
     * a cell is as tall as it is asked to be.
     */
    protected const PADDING_X = 2.835;

    /**
     * How thick a rule is, in points.
     */
    protected const LINE_WIDTH = 0.57;

    /**
     * How much taller than its own size a line of text is. It sets the shortest a cell may
     * be, so a cell asked for less than a line of text still gets one.
     */
    protected const CELL_HEIGHT_RATIO = 1.25;

    /**
     * Horizontal scaling that leaves the text alone. It is a ratio, so one is natural width.
     */
    protected const NO_STRETCHING = 1.0;

    /**
     * Built on demand, because whether its streams are compressed is settled after the
     * document is made and before anything is written to it.
     */
    private ?Engine $engine = null;

    private bool $compress = true;

    /**
     * Where the next thing goes, in millimetres from the top left of the page.
     */
    protected float $x = 0.0;
    protected float $y = 0.0;

    /**
     * Height of the last thing written, which is how far a bare line break moves down.
     */
    protected float $lasth = 0.0;

    protected float $pageWidth = 0.0;
    protected float $pageHeight = 0.0;

    protected float $lMargin = self::MARGIN / self::K;
    protected float $rMargin = self::MARGIN / self::K;
    protected float $tMargin = self::MARGIN / self::K;
    protected float $bMargin = self::MARGIN_BOTTOM / self::K;

    protected float $paddingX = self::PADDING_X / self::K;

    /**
     * The face the next text is set in, its size in points, and the horizontal scaling
     * applied to it.
     */
    protected string $fontFamily = '';
    protected string $fontStyle = '';
    protected float $fontSize = 0.0;
    protected float $stretching = self::NO_STRETCHING;

    /**
     * Height of the current face above and below the baseline, in millimetres. Both are
     * positive - the descent is stated as a depth, not as a coordinate.
     */
    protected float $ascent = 0.0;
    protected float $descent = 0.0;

    protected bool $autoPageBreak = true;

    /**
     * When the document says it was written, where it has been told. The pages say the same,
     * so that the same data printed twice comes out as the same bytes.
     */
    private ?int $created = null;

    /**
     * Widths already measured, so a string is measured once per face and size.
     *
     * @var array<string, float>
     */
    private array $widths = [];

    /**
     * The engine underneath, made the first time anything is written.
     *
     * A cell keeps its own air to the left and right, and a rule is drawn as thin as the
     * documents have always drawn it - neither is what the engine does by default. The engine
     * also reads local files only from where it is told to, and the logos live outside it.
     *
     * @return \Com\Tecnick\Pdf\Tcpdf
     */
    protected function pdf(): Engine
    {
        if ($this->engine instanceof Engine) {
            return $this->engine;
        }

        $this->engine = new Engine(
            unit: Unit::Millimeter,
            isunicode: true,
            subsetfont: true,
            compress: $this->compress,
            fileOptions: ['allowedPaths' => [K_PATH_IMAGES, K_PATH_FONTS]],
        );

        $this->engine->setDefaultCellMargin(0, 0, 0, 0);
        $this->engine->setDefaultCellPadding(0, 0, 0, 0);
        $this->engine->graph->add(['lineWidth' => self::LINE_WIDTH / self::K]);

        // Markup is set to the same line as everything else.
        $this->engine->addGlobalCSS(sprintf('* { line-height: %F; }', self::CELL_HEIGHT_RATIO));

        return $this->engine;
    }

    /**
     * Starts a page and puts the cursor at the top left of it.
     *
     * The page carries a fixed timestamp: the engine stamps every page with the time it was
     * written, which would otherwise be the one thing keeping two runs of the same data from
     * coming out as the same bytes.
     *
     * @return void
     */
    public function AddPage(): void
    {
        $page = $this->pdf()->addPage(['time' => $this->pageTimestamp()]);

        $this->pageWidth = $page['width'];
        $this->pageHeight = $page['height'];
        $this->x = $this->lMargin;
        $this->y = $this->tMargin;

        if ($this->fontFamily !== '') {
            $this->applyFont();
        }
    }

    /**
     * When the pages say they were written. Overridden where a document must come out as the
     * same bytes twice.
     *
     * @return int
     */
    protected function pageTimestamp(): int
    {
        // The engine reads a zero as "no time given" and stamps the present instead, so a
        // document pinned to the epoch is pinned to the second after it.
        return $this->created === null ? time() : max(1, $this->created);
    }

    /**
     * Sets the face the following text is written in.
     *
     * @param string $family Font family
     * @param string $style Style - B, I or both
     * @param float|null $size Size in points, or null to keep the current one
     * @return void
     */
    public function SetFont(string $family, string $style = '', ?float $size = null): void
    {
        $this->fontFamily = strtolower($family);
        $this->fontStyle = strtoupper($style);
        $this->fontSize = $size ?? $this->fontSize;

        $this->applyFont();
    }

    /**
     * Puts the current face on the page and reads back what it measures.
     *
     * @return void
     */
    private function applyFont(): void
    {
        $font = $this->pdf()->font->insert(
            $this->pdf()->pon,
            $this->fontFamily,
            $this->fontStyle,
            $this->fontSize,
            null,
            $this->stretching,
        );

        $this->pdf()->page->addContent($font['out']);

        $this->ascent = $font['ascent'] / self::K;
        $this->descent = -$font['descent'] / self::K;
    }

    /**
     * Scales the following text horizontally, as a percentage of its natural width.
     *
     * @param float $stretching Percentage, where a hundred leaves the text alone
     * @return void
     */
    private function setStretching(float $stretching): void
    {
        if ($stretching === $this->stretching) {
            return;
        }

        $this->stretching = $stretching;
        $this->applyFont();
    }

    /**
     * Width of a string in the current face, in millimetres.
     *
     * @param string $text The string
     * @return float
     */
    public function GetStringWidth(string $text): float
    {
        if ($text === '') {
            return 0.0;
        }

        $key = $this->fontFamily . $this->fontStyle . $this->fontSize . "\0" . $text;

        return $this->widths[$key] ??= $this->measure($text);
    }

    /**
     * Sets one line of text at an exact spot, with no cell around it.
     *
     * @param string $text The line
     * @param float $x Where it starts
     * @param float $baseline What the letters stand on
     * @return void
     */
    protected function drawTextAt(string $text, float $x, float $baseline): void
    {
        if ($text === '') {
            return;
        }

        $this->pdf()->page->addContent($this->pdf()->getTextLine(txt: $text, posx: $x, posy: $baseline));
    }

    /**
     * Height above the baseline of the face currently set.
     *
     * @return float
     */
    protected function fontAscent(): float
    {
        return $this->ascent;
    }

    /**
     * Depth below the baseline of the face currently set, stated as a depth.
     *
     * @return float
     */
    protected function fontDescent(): float
    {
        return $this->descent;
    }

    /**
     * Asks the engine how wide a string is, at its natural scale.
     *
     * @param string $text The string
     * @return float
     */
    private function measure(string $text): float
    {
        // A width wide enough that nothing wraps, so the answer is the width of the whole
        // string rather than of its longest line.
        $this->pdf()->getTextCell(txt: $text, width: 100000.0, height: 0.0, drawcell: false);

        $box = $this->pdf()->getLastTextBBox();

        return ($box['w'] ?? 0.0) / $this->stretching;
    }

    /**
     * The shortest a cell may be, given the face it is set in.
     *
     * @return float
     */
    protected function minimumCellHeight(): float
    {
        return round($this->fontSize / self::K * self::CELL_HEIGHT_RATIO, 6);
    }

    /**
     * A cell: a box of a given width with one line of text in it.
     *
     * @param float $w Width
     * @param float $h Height
     * @param string $txt The text
     * @param mixed $border Whether a frame is drawn round it
     * @param int $ln Where the cursor goes after - right, next line, or below
     * @param string $align How the text sits in the cell
     * @param bool $fill Whether the box is painted
     * @param string $link Unused, kept for the shape of the call
     * @param int $stretch Whether text too wide for the cell is condensed to fit
     * @param bool $ignore_min_height Whether a cell may be shorter than a line of text
     * @param string $calign Unused, the cell always hangs off its top edge
     * @param string $valign Where the text sits within the height
     * @return void
     */
    public function Cell(
        float $w,
        float $h = 0,
        string $txt = '',
        mixed $border = 0,
        int $ln = 0,
        string $align = '',
        bool $fill = false,
        string $link = '',
        int $stretch = 0,
        bool $ignore_min_height = false,
        string $calign = 'T',
        string $valign = 'M',
    ): void {
        if (!$ignore_min_height) {
            $h = max($h, $this->minimumCellHeight());
        }

        $this->checkPageBreak($h);

        $available = $w - (2 * $this->paddingX);
        $width = $this->GetStringWidth($txt);

        // Text too wide for its cell is condensed rather than allowed to run past the edge.
        $stretching = self::NO_STRETCHING;
        if ($stretch > 0 && $width > $available && $available > 0.0) {
            $stretching = $available / $width;
            $width = $available;
        }
        $this->setStretching($stretching);

        if ($border !== 0 && $border !== '') {
            $this->drawBorder($this->x, $this->y, $w, $h, $border, $fill);
        }

        if ($txt !== '') {
            $this->pdf()->page->addContent($this->pdf()->getTextLine(
                txt: $txt,
                posx: $this->textOrigin($w, $width, $align),
                posy: $this->baseline($h, $valign),
            ));
        }

        $this->setStretching(self::NO_STRETCHING);
        $this->lasth = $h;

        match ($ln) {
            1 => $this->Ln($h),
            2 => $this->y += $h,
            default => $this->x += $w,
        };
    }

    /**
     * Where the text of a cell starts, given how it is aligned in it.
     *
     * @param float $w Cell width
     * @param float $width Width of the text, as it will be drawn
     * @param string $align How the text sits in the cell
     * @return float
     */
    private function textOrigin(float $w, float $width, string $align): float
    {
        return match ($align) {
            'C' => $this->x + $this->paddingX + (($w - (2 * $this->paddingX) - $width) / 2),
            'R' => $this->x + $w - $this->paddingX - $width,
            default => $this->x + $this->paddingX,
        };
    }

    /**
     * Where the baseline of a cell's text sits, measured from the top of the page.
     *
     * A cell with a frame round it centres its text on the height, because a frame drawn
     * round text sitting on the top edge reads as a mistake.
     *
     * @param float $h Cell height
     * @param string $valign Where the text sits within the height
     * @return float
     */
    private function baseline(float $h, string $valign): float
    {
        return match ($valign) {
            'T' => $this->y + $this->ascent,
            'B' => $this->y + $h - $this->descent,
            default => $this->y + (($h - $this->ascent - $this->descent) / 2) + $this->ascent,
        };
    }

    /**
     * Draws the frame of a cell, and paints it where it is asked to be painted.
     *
     * @param float $x Left edge
     * @param float $y Top edge
     * @param float $w Width
     * @param float $h Height
     * @param mixed $border Which sides are drawn - all of them, or the named ones
     * @param bool $fill Whether the box is painted
     * @return void
     */
    private function drawBorder(float $x, float $y, float $w, float $h, mixed $border, bool $fill): void
    {
        $sides = $border === 1 || $border === '1' ? 'LTRB' : (string)$border;

        if ($fill) {
            $this->pdf()->page->addContent(
                $this->pdf()->graph->getBasicRect($x, $y, $w, $h, 'f'),
            );
        }

        $edges = [
            'L' => [$x, $y, $x, $y + $h],
            'T' => [$x, $y, $x + $w, $y],
            'R' => [$x + $w, $y, $x + $w, $y + $h],
            'B' => [$x, $y + $h, $x + $w, $y + $h],
        ];

        foreach ($edges as $side => [$x1, $y1, $x2, $y2]) {
            if (str_contains($sides, $side)) {
                $this->Line($x1, $y1, $x2, $y2);
            }
        }
    }

    /**
     * A block of text that wraps to the width it is given.
     *
     * The lines are set at the height of a line of the current face, not at the height the
     * block is asked for - that one is a minimum for the block as a whole.
     *
     * @param float $w Width
     * @param float $h Least the block may be
     * @param string $txt The text
     * @param mixed $border Whether a frame is drawn round it
     * @param string $align How the lines are set
     * @param bool $fill Whether the box is painted
     * @param int $ln Where the cursor goes after
     * @param float|null $x Where the block starts, or null for the cursor
     * @param float|null $y Where the block starts, or null for the cursor
     * @param bool $reseth Unused, kept for the shape of the call
     * @param int $stretch Whether a line too wide is condensed to fit
     * @param bool $ishtml Unused, kept for the shape of the call
     * @param bool $autopadding Unused, kept for the shape of the call
     * @param float $maxh Height the text is placed within, where it is given one
     * @param string $valign Where the text sits within that height
     * @return int Number of lines written
     */
    public function MultiCell(
        float $w,
        float $h,
        string $txt,
        mixed $border = 0,
        string $align = 'J',
        bool $fill = false,
        int $ln = 1,
        ?float $x = null,
        ?float $y = null,
        bool $reseth = true,
        int $stretch = 0,
        bool $ishtml = false,
        bool $autopadding = true,
        float $maxh = 0,
        string $valign = 'T',
    ): int {
        $left = $x ?? $this->x;
        $top = $y ?? $this->y;

        $pitch = $this->minimumCellHeight();

        // Each paragraph closes on its own last line, which is the one that must not be
        // spread across the width. A block of several of them justified as one would stretch
        // the end of every paragraph but the very last.
        $lines = [];
        foreach ($this->paragraphs($txt) as $paragraph) {
            $wrapped = $this->wrap($paragraph, $w - (2 * $this->paddingX));
            $closing = count($wrapped) - 1;

            foreach ($wrapped as $index => $line) {
                $lines[] = [$line, $index === $closing];
            }
        }

        $textHeight = count($lines) * $pitch;

        $height = max($h, $maxh > 0.0 ? $maxh : $textHeight);

        $this->x = $left;
        $this->y = $top;
        $this->checkPageBreak($height);
        $top = $this->y;

        if ($border !== 0 && $border !== '') {
            $this->drawBorder($left, $top, $w, $height, $border, $fill);
        }

        // Text shorter than the room it was given sits where it was asked to sit in it.
        $offset = 0.0;
        if ($maxh > 0.0 && $textHeight < $maxh) {
            $offset = match ($valign) {
                'M' => ($maxh - $textHeight) / 2,
                'B' => $maxh - $textHeight,
                default => 0.0,
            };
        }

        foreach ($lines as $index => [$line, $closes]) {
            $this->x = $left;
            $this->y = $top + $offset + ($index * $pitch);

            // A justified last line stretched across the full width reads as a mistake, so
            // it is set the way a ragged line would be.
            $this->writeLine($w, $pitch, $line, $align === 'J' && $closes ? 'L' : $align, $stretch);
        }

        $this->lasth = $height;

        match ($ln) {
            0 => [$this->x = $left + $w, $this->y = $top],
            2 => [$this->x = $left, $this->y = $top + $height],
            default => [$this->x = $this->lMargin, $this->y = $top + $height],
        };

        return count($lines);
    }

    /**
     * One line of a wrapped block, spread across the width where it is set justified.
     *
     * The width is what does the spreading: give the engine one and it fills it, so every
     * line that is not justified must be drawn without it.
     *
     * @param float $w Width
     * @param float $h Height of the line
     * @param string $line The line
     * @param string $align How the line is set
     * @param int $stretch Whether a line too wide is condensed to fit
     * @return void
     */
    private function writeLine(float $w, float $h, string $line, string $align, int $stretch = 0): void
    {
        if ($align !== 'J') {
            $this->Cell($w, $h, $line, 0, 0, $align, false, '', $stretch, true, 'T', 'T');

            return;
        }

        if ($line !== '') {
            $this->pdf()->page->addContent($this->pdf()->getTextLine(
                txt: $line,
                posx: $this->x + $this->paddingX,
                posy: $this->baseline($h, 'T'),
                width: $w - (2 * $this->paddingX),
            ));
        }
    }

    /**
     * Breaks text into the lines it is set on.
     *
     * Breaks are taken at spaces, and a word too long for the width is left to overrun
     * rather than broken - moving it would only move the problem.
     *
     * @param string $txt The text
     * @param float $width Width the lines are set to
     * @return array<int, string>
     */
    protected function splitLines(string $txt, float $width): array
    {
        $lines = [];

        foreach ($this->paragraphs($txt) as $paragraph) {
            $lines = array_merge($lines, $this->wrap($paragraph, $width));
        }

        return $lines;
    }

    /**
     * The paragraphs a text is written in, which are what its own line breaks make it.
     *
     * @param string $txt The text
     * @return array<int, string>
     */
    protected function paragraphs(string $txt): array
    {
        $parts = preg_split('/\R/', $txt) ?: [];

        // A trailing break closes the last paragraph rather than opening an empty one.
        if (count($parts) > 1 && end($parts) === '') {
            array_pop($parts);
        }

        return $parts;
    }

    /**
     * The lines one paragraph is set on at a given width.
     *
     * Breaks are taken at spaces, and a word too long for the width is left to overrun
     * rather than broken - moving it would only move the problem.
     *
     * @param string $paragraph The paragraph
     * @param float $width Width the lines are set to
     * @return array<int, string>
     */
    protected function wrap(string $paragraph, float $width): array
    {
        $lines = [];
        $line = '';

        foreach ($this->breakPoints($paragraph) as [$text, $gap]) {
            $candidate = $line . $gap . $text;

            // The break falls on the space, which goes with neither line.
            if ($line !== '' && $this->GetStringWidth($candidate) > $width) {
                $lines[] = $line;
                $line = $text;

                continue;
            }

            $line = $candidate;
        }

        $lines[] = $line;

        return $lines;
    }

    /**
     * A paragraph cut at every place a line may break, each piece with what precedes it.
     *
     * A line breaks at a space, and also after a hyphen - an address written with one in it
     * is otherwise carried whole to the next line and overruns the column it sits in. The
     * runs of spaces are kept rather than collapsed: some of these documents indent a line
     * by starting it with them, and a text taken apart on single spaces and put back
     * together loses that.
     *
     * @param string $paragraph The paragraph
     * @return array<int, array{0:string, 1:string}> The piece, and the space before it
     */
    private function breakPoints(string $paragraph): array
    {
        $pieces = [];
        $gap = '';

        foreach (preg_split('/( +)/u', $paragraph, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [] as $index => $token) {
            if ($index % 2 === 1) {
                $gap = $token;

                continue;
            }

            foreach (preg_split('/(?<=-)(?=.)/u', $token) ?: [$token] as $part) {
                $pieces[] = [$part, $gap];
                $gap = '';
            }
        }

        return $pieces;
    }

    /**
     * How many lines a text takes at a given width.
     *
     * @param string $txt The text
     * @param float $w The width
     * @return int
     */
    public function getNumLines(string $txt, float $w): int
    {
        return count($this->splitLines($txt, $w - (2 * $this->paddingX)));
    }

    /**
     * Writes text from the cursor, wrapping at the right margin.
     *
     * @param float $h Height of one line
     * @param string $txt The text
     * @param string $link Unused, kept for the shape of the call
     * @param bool $fill Whether the box is painted
     * @param string $align How the text is set
     * @param bool $ln Whether the cursor drops to the next line after
     * @return void
     */
    public function Write(
        float $h,
        string $txt,
        string $link = '',
        bool $fill = false,
        string $align = '',
        bool $ln = false,
    ): void {
        $width = $this->pageWidth - $this->rMargin - $this->x;
        $lines = $this->splitLines($txt, $width - (2 * $this->paddingX));
        $last = count($lines) - 1;

        foreach ($lines as $index => $line) {
            $this->Cell(
                $index === $last ? $this->GetStringWidth($line) + (2 * $this->paddingX) : $width,
                $h,
                $line,
                0,
                $index === $last ? 0 : 1,
                $align,
                $fill,
                '',
                0,
                false,
                'T',
                'T',
            );
        }

        // Without a height of its own, so that it drops by the height the cells actually
        // took: a line shorter than the text standing in it is grown to fit.
        if ($ln) {
            $this->Ln();
        }
    }

    /**
     * Moves the cursor down and back to the left margin.
     *
     * @param float|null $h How far down, or null for the height of the last thing written
     * @return void
     */
    public function Ln(?float $h = null): void
    {
        $this->x = $this->lMargin;
        $this->y += $h ?? $this->lasth;
    }

    /**
     * Draws a straight line.
     *
     * @param float $x1 Start
     * @param float $y1 Start
     * @param float $x2 End
     * @param float $y2 End
     * @return void
     */
    public function Line(float $x1, float $y1, float $x2, float $y2): void
    {
        $this->pdf()->page->addContent($this->pdf()->graph->getLine($x1, $y1, $x2, $y2));
    }

    /**
     * Places an image at a fixed spot on the page.
     *
     * @param string $file The file
     * @param float $x Left edge
     * @param float $y Top edge
     * @param float $w Width, the height following from it
     * @return void
     */
    public function Image(string $file, float $x, float $y, float $w): void
    {
        $id = $this->pdf()->image->add($file);
        $data = $this->pdf()->image->getImageDataByKey($this->pdf()->image->getKey($file));

        $this->pdf()->page->addContent($this->pdf()->image->getSetImage(
            $id,
            $x,
            $y,
            $w,
            $w * $data['height'] / $data['width'],
            $this->pageHeight,
        ));
    }

    /**
     * Writes a block of HTML.
     *
     * @param string $html The markup
     * @param bool $ln Whether the cursor drops below it after
     * @param bool $fill Whether the box is painted
     * @param bool $reseth Unused, kept for the shape of the call
     * @param bool $cell Unused, kept for the shape of the call
     * @param string $align How the block is set
     * @return void
     */
    public function writeHTML(
        string $html,
        bool $ln = true,
        bool $fill = false,
        bool $reseth = false,
        bool $cell = false,
        string $align = '',
    ): void {
        $width = $this->pageWidth - $this->rMargin - $this->x;

        // Markup starts where a cell's text would start rather than hard against the margin.
        $this->pdf()->addHTMLCell(
            html: $html,
            posx: $this->x + $this->paddingX,
            posy: $this->y,
            width: $width - $this->paddingX,
        );

        // The engine reports the last line it set, not the block as a whole, so the foot of
        // the block is that line's top and one line more.
        $box = $this->pdf()->getLastCellBBox();
        $this->y = ($box['y'] ?? $this->y) + $this->minimumCellHeight();
        $this->x = $this->lMargin;
    }

    /**
     * Starts a new page when what comes next would run past the foot of this one.
     *
     * @param float $h How much room it needs
     * @return void
     */
    protected function checkPageBreak(float $h): void
    {
        if (!$this->autoPageBreak) {
            return;
        }

        if ($this->y + $h > $this->pageHeight - $this->bMargin) {
            $x = $this->x;
            $this->AddPage();
            $this->x = $x;
        }
    }

    /**
     * @param bool $auto Whether pages break on their own
     * @param float $margin How much room is kept at the foot of the page
     * @return void
     */
    public function SetAutoPageBreak(bool $auto, float $margin = 0): void
    {
        $this->autoPageBreak = $auto;
        $this->bMargin = $margin > 0.0 ? $margin : self::MARGIN_BOTTOM / self::K;
    }

    /**
     * Where a new line begins. Everything that flows rather than being placed by hand starts
     * here, so moving it moves the whole body of the document.
     *
     * @param float $margin Distance from the left edge of the page
     * @return void
     */
    public function SetLeftMargin(float $margin): void
    {
        $this->lMargin = $margin;
    }

    /**
     * @return float
     */
    public function GetX(): float
    {
        return $this->x;
    }

    /**
     * @return float
     */
    public function GetY(): float
    {
        return $this->y;
    }

    /**
     * Moves the cursor down the page. A negative value is measured from the foot of it.
     *
     * @param float $y Where to
     * @param bool $resetx Whether the cursor also goes back to the left margin
     * @return void
     */
    public function SetY(float $y, bool $resetx = true): void
    {
        $this->y = $y >= 0 ? $y : $this->pageHeight + $y;

        if ($resetx) {
            $this->x = $this->lMargin;
        }
    }

    /**
     * @param float $x Where to
     * @param float $y Where to
     * @return void
     */
    public function SetXY(float $x, float $y): void
    {
        $this->SetY($y, false);
        $this->x = $x;
    }

    /**
     * @return float
     */
    public function getPageHeight(): float
    {
        return $this->pageHeight;
    }

    /**
     * @return float
     */
    public function getBreakMargin(): float
    {
        return $this->bMargin;
    }

    /**
     * @return array<string, float>
     */
    public function getMargins(): array
    {
        return [
            'left' => $this->lMargin,
            'right' => $this->rMargin,
            'top' => $this->tMargin,
            'bottom' => $this->bMargin,
        ];
    }

    /**
     * @return array<string, float>
     */
    public function getCellPaddings(): array
    {
        return ['T' => 0.0, 'R' => $this->paddingX, 'B' => 0.0, 'L' => $this->paddingX];
    }

    /**
     * Kept for the shape of the call: these documents print no running head or foot.
     *
     * @param bool $print Whether one is printed
     * @return void
     */
    public function setPrintHeader(bool $print): void
    {
    }

    /**
     * Kept for the shape of the call: these documents print no running head or foot.
     *
     * @param bool $print Whether one is printed
     * @return void
     */
    public function setPrintFooter(bool $print): void
    {
    }

    /**
     * How far a list is indented from the text round it.
     *
     * @param float $width The indent
     * @return void
     */
    public function setListIndentWidth(float $width): void
    {
        $this->pdf()->addGlobalCSS(sprintf('ul, ol { padding-left: %Fmm; }', $width));
    }

    /**
     * @param bool $compress Whether the streams are compressed
     * @return void
     */
    public function SetCompression(bool $compress): void
    {
        $this->compress = $compress;
    }

    /**
     * Kept for the shape of the call: the document is finished when it is asked for.
     *
     * @return void
     */
    public function Close(): void
    {
    }

    /**
     * @param int $timestamp When the document says it was written
     * @return void
     */
    public function setDocCreationTimestamp(int $timestamp): void
    {
        $this->created = $timestamp;
        $this->pdf()->setDocCreationDate($timestamp);
    }

    /**
     * @param int $timestamp When the document says it was last changed
     * @return void
     */
    public function setDocModificationTimestamp(int $timestamp): void
    {
        $this->pdf()->setDocModificationDate($timestamp);
    }

    /**
     * The finished document.
     *
     * @param string $name What the file is called
     * @param string $dest Where it goes - only handing it back is supported
     * @return string
     */
    public function Output(string $name = 'doc.pdf', string $dest = 'I'): string
    {
        $this->pdf()->setPDFFilename($name);

        return $this->pdf()->getOutPDFString();
    }
}
