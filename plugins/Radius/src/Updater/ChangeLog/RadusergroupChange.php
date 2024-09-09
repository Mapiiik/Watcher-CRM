<?php
declare(strict_types=1);

namespace Radius\Updater\ChangeLog;

/**
 * RADIUS Updater Radusergroup Change
 */
class RadusergroupChange
{
    /**
     * @var array<\Radius\Model\Entity\Radusergroup> $original
     */
    private array $original;

    /**
     * @var array<\Radius\Model\Entity\Radusergroup> $changed
     */
    private array $changed;

    /**
     * Constructor
     *
     * @param array<\Radius\Model\Entity\Radusergroup> $original
     * @param array<\Radius\Model\Entity\Radusergroup> $changed
     */
    public function __construct(array $original, array $changed)
    {
        $this->original = $original;
        $this->changed = $changed;
    }

    /**
     * Get original data
     *
     * @return array<\Radius\Model\Entity\Radusergroup>
     */
    public function getOriginal(): array
    {
        return $this->original;
    }

    /**
     * Get changed data
     *
     * @return array<\Radius\Model\Entity\Radusergroup>
     */
    public function getChanged(): array
    {
        return $this->changed;
    }
}
