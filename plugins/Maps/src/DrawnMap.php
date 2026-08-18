<?php
declare(strict_types=1);

namespace Maps;

/**
 * A drawn map: the markers on it and the lines between them.
 *
 * Both are keyed, because a place is marked once however many links reach it and a line is one
 * line however many of its ends the drawing walks. The keys are the drawing's own business - what
 * reads a map wants the markers and the lines, not the names they were gathered under.
 */
final class DrawnMap
{
    /**
     * @param array<string, \Maps\Marker> $markers What is marked, keyed by what it marks.
     * @param array<string, \Maps\Polyline> $polylines The lines, keyed by the ends they join.
     */
    public function __construct(
        public readonly array $markers,
        public readonly array $polylines,
    ) {
    }
}
