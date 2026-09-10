<?php
declare(strict_types=1);

namespace App\Pdf;

use App\Pdf\Trait\ProviderSignatureTrait;
use Cake\I18n\Date;

/**
 * Our signature drawn onto a paper that is already finished.
 *
 * Each page of the paper comes across as a form the new document places whole, and our date and
 * our signature go on top of it, into the marks the paper carries. The marks are written into the
 * result again, so the paper does not forget where it is signed on the way through.
 *
 * The drawing is the same code that sets a document with our signature already on it - not
 * something that ought to match it, the same thing - which is what makes the two agree.
 *
 * A digital signature does not survive this: a page comes across as its content and its
 * resources, and what covers the bytes is left behind. Signing by certificate has to come last.
 */
final class SignatureStampPDF extends Canvas
{
    use ProviderSignatureTrait;

    /**
     * @param string|null $signature A signature to draw with, where it is not the installation's.
     */
    public function __construct(?string $signature = null)
    {
        $this->signature = $signature;
    }

    /**
     * Draws our signature onto a finished paper and hands back the result.
     *
     * @param string $pdf The paper as it stands.
     * @param \App\Pdf\SignatureAnchors $anchors Where it is signed - its own marks, or those of
     *   the paper it is a scan of.
     * @param \Cake\I18n\Date $on The day we signed it.
     * @return string
     * @throws \Com\Tecnick\Pdf\Import\ImportException When the paper cannot be taken across.
     */
    public function stamp(string $pdf, SignatureAnchors $anchors, Date $on): string
    {
        $source = $this->pdf()->setImportSourceData($pdf);

        $pages = $this->pdf()->getSourcePageCount($source);
        for ($number = 1; $number <= $pages; $number++) {
            $this->takeAcross($source, $number);
            $this->drawProviderSignature($anchors, $on);
        }

        // Carried forward whole, spent ones and all: which of them have been used is a question
        // about what the paper is filed as, and that is not this layer's to answer.
        foreach ($anchors->all() as $anchor) {
            $this->anchors()->add($anchor);
        }

        return $this->Output('signed.pdf', 'S');
    }

    /**
     * Puts one page of the paper onto a page of its own, and tells the cursor where it is.
     *
     * @param string $source The paper, as the engine knows it.
     * @param int $number Which page of it.
     * @return void
     */
    private function takeAcross(string $source, int $number): void
    {
        $page = $this->pdf()->addPageFromImport($source, $number);

        $this->pages++;
        $this->pageWidth = $page->getWidth() / self::K;
        $this->pageHeight = $page->getHeight() / self::K;
        $this->x = $this->lMargin;
        $this->y = $this->tMargin;
    }
}
