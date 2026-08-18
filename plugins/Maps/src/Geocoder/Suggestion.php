<?php
declare(strict_types=1);

namespace Maps\Geocoder;

use Maps\Position;

/**
 * One place a geocoder offers: what to call it and where it is.
 *
 * The reference is whatever the geocoder uses to name the record it came from, for a caller that
 * wants to keep hold of it. A geocoder with nothing of the sort leaves it empty.
 */
final class Suggestion
{
    /**
     * @param string $label What the place is called, in one line.
     * @param \Maps\Position $position Where it is.
     * @param string|null $reference The geocoder's own name for the record.
     */
    public function __construct(
        public readonly string $label,
        public readonly Position $position,
        public readonly ?string $reference = null,
    ) {
    }

    /**
     * The shape the maps hand to the browser.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'lat' => $this->position->lat,
            'lng' => $this->position->lng,
            'ref' => $this->reference,
        ];
    }
}
