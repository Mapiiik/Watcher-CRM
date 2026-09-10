<?php
declare(strict_types=1);

namespace App\Pdf;

/**
 * One place on a paper where a signature or its date goes.
 *
 * Written into the document itself rather than kept beside it, so that a paper carries its own
 * truth: a file restored from a backup without the database still knows where it is signed, and
 * a customer signing through a portal one day reads the same marks the office does.
 *
 * Millimetres from the top left of the page, which is how {@see \App\Pdf\Canvas} measures - so
 * that what draws into a mark is the same code whether the paper is being set for the first time
 * or stamped afterwards.
 */
final class SignatureAnchor
{
    /**
     * @param string $name What the mark is for.
     * @param int $page Which page it is on, counting from one.
     * @param float $x Left edge, in millimetres from the left of the page.
     * @param float $y Top edge, in millimetres from the top of the page.
     * @param float $width How wide the mark may be.
     * @param float|null $height How tall, or nothing where whatever is drawn decides for itself -
     *   a signature is an image and keeps its own proportions.
     */
    public function __construct(
        public readonly string $name,
        public readonly int $page,
        public readonly float $x,
        public readonly float $y,
        public readonly float $width,
        public readonly ?float $height = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'page' => $this->page,
            'x' => round($this->x, 3),
            'y' => round($this->y, 3),
            'width' => round($this->width, 3),
            'height' => $this->height === null ? null : round($this->height, 3),
        ];
    }

    /**
     * @param array<string, mixed> $anchor As it was written down.
     * @return self|null Nothing where the record is not one.
     */
    public static function fromArray(array $anchor): ?self
    {
        if (!is_string($anchor['name'] ?? null) || !isset($anchor['page'], $anchor['x'], $anchor['y'])) {
            return null;
        }

        return new self(
            (string)$anchor['name'],
            (int)$anchor['page'],
            (float)$anchor['x'],
            (float)$anchor['y'],
            (float)($anchor['width'] ?? 0),
            isset($anchor['height']) ? (float)$anchor['height'] : null,
        );
    }
}
