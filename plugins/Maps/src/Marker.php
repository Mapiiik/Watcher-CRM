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
     * Whether the map opens focused on it. One that is not is drawn, but the map does not zoom out to
     * show it unless no marker is focused on.
     */
    public bool $focused;

    /**
     * Constructor
     */
    public function __construct(
        Position $position,
        string $title,
        string $color,
        string $content,
        bool $locked,
        bool $focused = true,
    ) {
        $this->position = $position;
        $this->title = $title;
        $this->color = $color;
        $this->content = $content;
        $this->locked = $locked;
        $this->focused = $focused;
    }
}
