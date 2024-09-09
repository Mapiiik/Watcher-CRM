<?php
declare(strict_types=1);

namespace Radius\Updater\ChangeLog;

/**
 * RADIUS Updater Radcheck Change
 */
class RadcheckChange
{
    /**
     * @var array<\Radius\Model\Entity\Radcheck> $original
     */
    private array $original;

    /**
     * @var array<\Radius\Model\Entity\Radcheck> $changed
     */
    private array $changed;

    /**
     * Constructor
     *
     * @param array<\Radius\Model\Entity\Radcheck> $original
     * @param array<\Radius\Model\Entity\Radcheck> $changed
     */
    public function __construct(array $original, array $changed)
    {
        $this->original = $original;
        $this->changed = $changed;
    }

    /**
     * Get original data
     *
     * @return array<\Radius\Model\Entity\Radcheck>
     */
    public function getOriginal(): array
    {
        return $this->original;
    }

    /**
     * Get changed data
     *
     * @return array<\Radius\Model\Entity\Radcheck>
     */
    public function getChanged(): array
    {
        return $this->changed;
    }
}
