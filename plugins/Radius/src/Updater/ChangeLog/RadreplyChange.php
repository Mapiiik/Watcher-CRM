<?php
declare(strict_types=1);

namespace Radius\Updater\ChangeLog;

/**
 * RADIUS Updater Radreply Change
 */
class RadreplyChange
{
    /**
     * @var array<\Radius\Model\Entity\Radreply> $original
     */
    private array $original;

    /**
     * @var array<\Radius\Model\Entity\Radreply> $changed
     */
    private array $changed;

    /**
     * Constructor
     *
     * @param array<\Radius\Model\Entity\Radreply> $original
     * @param array<\Radius\Model\Entity\Radreply> $changed
     */
    public function __construct(array $original, array $changed)
    {
        $this->original = $original;
        $this->changed = $changed;
    }

    /**
     * Get original data
     *
     * @return array<\Radius\Model\Entity\Radreply>
     */
    public function getOriginal(): array
    {
        return $this->original;
    }

    /**
     * Get changed data
     *
     * @return array<\Radius\Model\Entity\Radreply>
     */
    public function getChanged(): array
    {
        return $this->changed;
    }
}
