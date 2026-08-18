<?php
declare(strict_types=1);

namespace Maps\Geocoder;

use Maps\Position;

/**
 * Turning text into places and back.
 *
 * Which service does it is the application's business - one has a national registry of its own,
 * another asks a public one - so the maps only ever ask for this much.
 */
interface GeocoderInterface
{
    /**
     * Places matching what has been typed so far.
     *
     * @param string $query What the user has typed.
     * @param string|null $country ISO country code to search within, when the caller knows it.
     * @param int $limit How many to offer at most.
     * @return list<\Maps\Geocoder\Suggestion>
     */
    public function search(string $query, ?string $country = null, int $limit = 5): array;

    /**
     * The place a pair of coordinates falls on, if the geocoder knows one.
     *
     * @param \Maps\Position $position Where to look.
     * @param string|null $country ISO country code, when the caller knows it.
     * @return \Maps\Geocoder\Suggestion|null
     */
    public function reverse(Position $position, ?string $country = null): ?Suggestion;
}
