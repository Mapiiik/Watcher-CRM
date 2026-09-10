<?php
declare(strict_types=1);

namespace App\Test\TestCase\Pdf;

use App\Pdf\AppPDF;
use App\Pdf\SignatureAnchor;
use App\Pdf\SignatureAnchors;
use App\Pdf\SignatureStampPDF;
use Cake\Core\Configure;
use Cake\I18n\Date;
use Cake\TestSuite\TestCase;
use DOMDocument;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Pdf\SignatureAnchors Test Case
 *
 * The marks are what lets a paper already on file be countersigned instead of drawn a second
 * time, so what is asked of them is that a finished document carries them, that they come back
 * out of it, and that what is drawn into them lands where they say.
 */
#[CoversClass(SignatureAnchors::class)]
#[UsesClass(SignatureAnchor::class)]
#[UsesClass(SignatureStampPDF::class)]
class SignatureAnchorsTest extends TestCase
{
    /**
     * The day the signature is put on, as a paper would carry it.
     *
     * @var string
     */
    private const SIGNED_ON = '2026-09-10';

    /**
     * A signature to draw with.
     *
     * What a deployment signs with lives under the data root, which the repository does not
     * carry, so a test that leant on it would pass on a machine that happens to have one and fail
     * on a build server - the worst way round, because the failure then says nothing about the
     * change that set it off. This one brings its own and never touches the installation's.
     *
     * A single opaque pixel is enough: nothing here is asked of the image, only of where the page
     * says it was put. Opaque on purpose - tc-lib-pdf draws a PNG carrying an alpha channel as
     * nothing at all, and a transparent one would fail this test for a reason of its own making.
     *
     * @var string
     */
    private const A_PIXEL = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAACXBIWXMAAA7EAAAO'
        . 'xAGVKw4bAAAADElEQVQImWNgYGAAAAAEAAGjChXjAAAAAElFTkSuQmCC';

    /**
     * Where this test keeps it.
     *
     * Beside the installation's images rather than under the temporary directory, because the
     * engine reads local files only from where it is told to and that is one of the two places.
     * Under a name of its own, so that what a deployment signs with is never in reach.
     *
     * @var string
     */
    private string $signature = '';

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $images = Configure::read('Data.root') . DS . 'images' . DS;

        if (!is_dir($images)) {
            mkdir($images, 0777, true);
        }

        $this->signature = $images . 'signature-under-test-' . uniqid() . '.png';
        file_put_contents($this->signature, (string)base64_decode(self::A_PIXEL));
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        if (is_file($this->signature)) {
            unlink($this->signature);
        }

        parent::tearDown();
    }

    /**
     * @link \App\Pdf\SignatureAnchors::toXmp()
     * @link \App\Pdf\SignatureAnchors::fromPdf()
     * @return void
     */
    public function testAPaperCarriesItsMarksAndGivesThemBack(): void
    {
        $read = SignatureAnchors::fromPdf($this->paper('double'));

        $names = array_map(fn(SignatureAnchor $a): string => $a->name, $read->all());

        $this->assertSame([
            SignatureAnchors::PROVIDER_DATE,
            SignatureAnchors::CUSTOMER_DATE,
            SignatureAnchors::PROVIDER_SIGNATURE,
            SignatureAnchors::CUSTOMER_SIGNATURE,
        ], $names);
    }

    /**
     * A consent has one column and one signatory, and says so without being told to.
     *
     * @link \App\Pdf\SignatureAnchors::fromPdf()
     * @return void
     */
    public function testAPaperWithOneSignatoryMarksOnlyThatOne(): void
    {
        $read = SignatureAnchors::fromPdf($this->paper('single-right'));

        $names = array_map(fn(SignatureAnchor $a): string => $a->name, $read->all());

        $this->assertSame([
            SignatureAnchors::CUSTOMER_DATE,
            SignatureAnchors::CUSTOMER_SIGNATURE,
        ], $names);
    }

    /**
     * A block that will not fit goes over whole rather than leaving its feet behind.
     *
     * The room it needs is a millimetre more than the page has left at this point, so under a
     * guard that measures the page instead of the block the lines would stay here and the names
     * under them would go over on their own.
     *
     * @link \App\Pdf\AppPDF::printSignatureSection()
     * @return void
     */
    public function testABlockThatWillNotFitGoesOverWhole(): void
    {
        $read = SignatureAnchors::fromPdf($this->paper('double', false, 239.5));

        foreach ($read->all() as $anchor) {
            $this->assertSame(2, $anchor->page, $anchor->name . ' was left on the page before.');
        }
    }

    /**
     * And one that fits stays where it is.
     *
     * @link \App\Pdf\AppPDF::printSignatureSection()
     * @return void
     */
    public function testABlockThatFitsStaysOnItsPage(): void
    {
        $read = SignatureAnchors::fromPdf($this->paper('double', false, 100.0));

        foreach ($read->all() as $anchor) {
            $this->assertSame(1, $anchor->page);
        }
    }

    /**
     * The marks are still readable when the rest of the document is not, which is the whole
     * reason they go into the metadata rather than anywhere else.
     *
     * @link \App\Pdf\SignatureAnchors::fromPdf()
     * @return void
     */
    public function testTheMarksSurviveCompression(): void
    {
        $this->assertCount(4, SignatureAnchors::fromPdf($this->paper('double', true))->all());
    }

    /**
     * @link \App\Pdf\SignatureAnchors::fromPdf()
     * @return void
     */
    public function testAPaperThatCarriesNoMarksIsNotAFailure(): void
    {
        $this->assertTrue(SignatureAnchors::fromPdf('this is not a document at all')->isEmpty());
    }

    /**
     * What the numbers mean is written down beside them, so a paper found on its own can be read.
     *
     * @link \App\Pdf\SignatureAnchors::toXmp()
     * @return void
     */
    public function testTheMarksSayWhatTheirNumbersMean(): void
    {
        $anchors = new SignatureAnchors();
        $anchors->add(new SignatureAnchor(SignatureAnchors::PROVIDER_DATE, 1, 10.0, 20.0, 30.0, 4.0));

        // Read back the way the document itself will be read, which also settles that the
        // fragment is well formed - the engine refuses one that is not.
        $document = new DOMDocument();
        $this->assertTrue(
            $document->loadXML(
                '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">'
                . $anchors->toXmp()
                . '</rdf:RDF>',
            ),
            'The marks are not well-formed XMP.',
        );

        $written = json_decode(trim($document->textContent), true);

        $this->assertSame('mm', $written['units'] ?? null);
        $this->assertSame('top-left', $written['origin'] ?? null);
        $this->assertSame(SignatureAnchors::PROVIDER_DATE, $written['anchors'][0]['name'] ?? null);
    }

    /**
     * The one that matters: what is stamped onto a finished paper lands on the mark the paper
     * carries, rather than wherever the code that drew it once happened to put it.
     *
     * @link \App\Pdf\SignatureStampPDF::stamp()
     * @return void
     */
    public function testWhatIsStampedLandsOnTheMark(): void
    {
        $paper = $this->paper('double');
        $anchors = SignatureAnchors::fromPdf($paper);

        $stamp = new SignatureStampPDF($this->signature);
        $stamp->SetCompression(false);
        $stamped = $stamp->stamp($paper, $anchors, new Date(self::SIGNED_ON));

        $date = $anchors->find(SignatureAnchors::PROVIDER_DATE);
        $signature = $anchors->find(SignatureAnchors::PROVIDER_SIGNATURE);
        $this->assertInstanceOf(SignatureAnchor::class, $date);
        $this->assertInstanceOf(SignatureAnchor::class, $signature);

        // The image is placed by a matrix that states its size and its corner outright, so the
        // mark can be read straight back off the page.
        $placed = [];
        $this->assertSame(
            1,
            preg_match('#q ([0-9.]+) 0 0 ([0-9.]+) ([0-9.]+) ([0-9.]+) cm /IMG\d+ Do Q#', $stamped, $placed),
            'The signature was not drawn onto the paper.',
        );

        $this->assertEqualsWithDelta($signature->width, $this->toMillimetres((float)($placed[1] ?? 0)), 0.01);
        $this->assertEqualsWithDelta($signature->x, $this->toMillimetres((float)($placed[3] ?? 0)), 0.01);

        $height = $this->toMillimetres((float)($placed[2] ?? 0));
        $bottom = $this->toMillimetres((float)($placed[4] ?? 0));
        $this->assertEqualsWithDelta($signature->y, $this->pageHeight($stamped) - $bottom - $height, 0.01);

        // And the date is written a shade above the line it is written on, not across it.
        $written = [];
        $this->assertSame(
            1,
            preg_match('#([0-9.]+) ([0-9.]+) Td \((?:(?!\) Tj).)*\) Tj#s', $stamped, $written),
            'The date was not written onto the paper.',
        );

        $baseline = $this->pageHeight($stamped) - $this->toMillimetres((float)($written[2] ?? 0));
        $this->assertLessThan($date->y + ($date->height ?? 0.0), $baseline);
        $this->assertGreaterThan($date->y - 2.0, $baseline);
    }

    /**
     * A paper as the documents set one, cut down to the block that is signed.
     *
     * @param string $layout Which block - two columns or one.
     * @param bool $compress Whether the streams are squeezed, as a stored document's are.
     * @param float|null $from How far down the page the block is started, where that matters.
     * @return string
     */
    private function paper(string $layout, bool $compress = false, ?float $from = null): string
    {
        $pdf = new class extends AppPDF {
            /**
             * @param string $layout Which block.
             * @param float|null $from How far down the page to start it.
             * @return void
             */
            public function drawTheBlockThatIsSigned(string $layout, ?float $from = null): void
            {
                $this->AddPage();
                $this->SetFont(static::FONT_FAMILY, '', static::BODY_FONT_SIZE);

                if ($from !== null) {
                    $this->SetY($from);
                }

                $this->printSignatureSection($layout);
            }
        };

        $pdf->SetCompression($compress);
        $pdf->drawTheBlockThatIsSigned($layout, $from);

        return $pdf->Output('paper.pdf', 'S');
    }

    /**
     * @param float $points A length as the page states it.
     * @return float The same length in millimetres.
     */
    private function toMillimetres(float $points): float
    {
        return $points / 2.8346456692913;
    }

    /**
     * @param string $pdf The document.
     * @return float How tall its pages are, in millimetres.
     */
    private function pageHeight(string $pdf): float
    {
        $box = [];
        preg_match('#/MediaBox\s*\[\s*[0-9.]+\s+[0-9.]+\s+[0-9.]+\s+([0-9.]+)\s*\]#', $pdf, $box);

        return $this->toMillimetres((float)($box[1] ?? 0));
    }
}
