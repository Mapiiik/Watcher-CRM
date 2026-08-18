<?php
declare(strict_types=1);

namespace App\Maps;

use Maps\DrawnMap;

/**
 * A customer and the place serving them, drawn, and how far apart they are.
 *
 * The distance is left out when either end is missing, because the map then has nothing to measure
 * between and a nought would read as "next door".
 */
final class DrawnConnection
{
    /**
     * @param \Maps\DrawnMap $map What to draw.
     * @param float|null $distance How far apart the ends are, in metres.
     */
    public function __construct(
        public readonly DrawnMap $map,
        public readonly ?float $distance = null,
    ) {
    }
}
