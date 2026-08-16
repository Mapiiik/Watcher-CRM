<?php
declare(strict_types=1);

namespace Dashboard\Card;

use Settings\Utility\Settings;

/**
 * Shared ground for dashboard cards - the defaults a card only overrides where it differs.
 *
 * The thresholds are read from the settings under {@see self::SETTINGS_PATH}, which an
 * application that keeps them elsewhere overrides.
 */
abstract class AbstractDashboardCard implements DashboardCardInterface
{
    /**
     * Where the settings this reads are kept.
     */
    protected const SETTINGS_PATH = 'core.dashboard';

    /**
     * Cards are offered to every role unless they say otherwise.
     *
     * @return list<string>
     */
    public function roles(): array
    {
        return [];
    }

    /**
     * Cards are rendered with the page unless they say they are slow.
     *
     * @return bool
     */
    public function deferred(): bool
    {
        return false;
    }

    /**
     * The template is named after the card unless it says otherwise.
     *
     * @return string
     */
    public function template(): string
    {
        return $this->id();
    }

    /**
     * How many rows a listing card shows before it points at the full listing.
     *
     * @return int
     */
    protected function maximumRows(): int
    {
        $rows = Settings::get(static::SETTINGS_PATH . '.max_rows_per_card', 10);

        return is_numeric($rows) ? (int)$rows : 10;
    }

    /**
     * Read a whole-number dashboard setting, falling back where it is unset or unusable.
     *
     * @param string $path Settings path below {@see self::SETTINGS_PATH}.
     * @param int $default Value to fall back to.
     * @return int
     */
    protected function days(string $path, int $default): int
    {
        $value = Settings::get(static::SETTINGS_PATH . '.' . $path, $default);

        return is_numeric($value) ? (int)$value : $default;
    }
}
