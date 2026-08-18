<?php
declare(strict_types=1);

namespace Maps;

/**
 * A marker on the map, and what its bubble says.
 */
class Marker
{
    /**
     * Position
     */
    public Position $position;

    /**
     * Title
     */
    public string $title;

    /**
     * Color
     */
    public string $color;

    /**
     * Content
     */
    public string $content;

    /**
     * Locked
     */
    public bool $locked;

    /**
     * Constructor
     */
    public function __construct(Position $position, string $title, string $color, string $content, bool $locked)
    {
        $this->position = $position;
        $this->title = $title;
        $this->color = $color;
        $this->content = $content;
        $this->locked = $locked;
    }
}
