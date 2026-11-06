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
    private readonly array $original;

    /**
     * @var array<\Radius\Model\Entity\Radcheck> $changed
     */
    private readonly array $changed;

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

    /**
     * The original data as a person may read it, on the page and in the e-mail from cron
     *
     * @return list<array<string, mixed>>
     */
    public function originalToRead(): array
    {
        return self::withoutPasswords($this->original);
    }

    /**
     * The changed data as a person may read it, on the page and in the e-mail from cron
     *
     * @return list<array<string, mixed>>
     */
    public function changedToRead(): array
    {
        return self::withoutPasswords($this->changed);
    }

    /**
     * @param array<\Radius\Model\Entity\Radcheck> $checks The checks.
     * @return list<array<string, mixed>>
     */
    private static function withoutPasswords(array $checks): array
    {
        $rows = [];
        foreach ($checks as $check) {
            $row = $check->toArray();
            if (($row['attribute'] ?? null) === 'Cleartext-Password') {
                $row['value'] = '****';
            }
            $rows[] = $row;
        }

        return $rows;
    }
}
