<?php
declare(strict_types=1);

namespace App\Pdf\Trait;

use App\Pdf\SignatureAnchor;
use App\Pdf\SignatureAnchors;
use Cake\I18n\Date;

/**
 * Drawing our signature into the marks a paper carries.
 *
 * The one place the geometry of it lives. It is called twice - once while a document is being set,
 * and once when one that is already finished is being stamped - so that the two cannot drift
 * apart: they are not two renderings that ought to agree, they are the same one.
 */
trait ProviderSignatureTrait
{
    /**
     * How far above the line the date sits.
     *
     * A little, and no more: the point is that it reads as something written onto the paper rather
     * than as something set on it, and a date floating clear of its line reads as neither.
     */
    protected const DATE_LIFT = 1.0;

    /**
     * What the signature is drawn from.
     */
    protected const SIGNATURE_IMAGE = 'signature.png';

    /**
     * What the date is written in. Named here rather than left to whatever the caller had set,
     * because one of the two callers is stamping a finished paper and has set nothing.
     */
    protected const SIGNATURE_FONT = 'DejaVuSerif';
    protected const SIGNATURE_FONT_SIZE = 8.0;

    /**
     * Puts our date and our signature onto whichever of the marks are on the page being drawn.
     *
     * @param \App\Pdf\SignatureAnchors $anchors The marks the paper carries.
     * @param \Cake\I18n\Date $on The day we signed it.
     * @return void
     */
    protected function drawProviderSignature(SignatureAnchors $anchors, Date $on): void
    {
        $this->SetFont(static::SIGNATURE_FONT, '', static::SIGNATURE_FONT_SIZE);

        $date = $anchors->find(SignatureAnchors::PROVIDER_DATE);
        if ($date instanceof SignatureAnchor && $date->page === $this->PageNo()) {
            $this->writeOverTheLine($date, $on->__toString());
        }

        $signature = $anchors->find(SignatureAnchors::PROVIDER_SIGNATURE);
        if ($signature instanceof SignatureAnchor && $signature->page === $this->PageNo()) {
            $this->Image(K_PATH_IMAGES . static::SIGNATURE_IMAGE, $signature->x, $signature->y, $signature->width);
        }
    }

    /**
     * Writes into a mark, a shade above where the line it sits on runs.
     *
     * @param \App\Pdf\SignatureAnchor $anchor Where it goes.
     * @param string $text What is written.
     * @return void
     */
    protected function writeOverTheLine(SignatureAnchor $anchor, string $text): void
    {
        $x = $this->GetX();
        $y = $this->GetY();

        $this->SetXY($anchor->x, $anchor->y - static::DATE_LIFT);
        $this->Cell($anchor->width, $anchor->height ?? 0.0, $text, align: 'C');

        $this->SetXY($x, $y);
    }
}
