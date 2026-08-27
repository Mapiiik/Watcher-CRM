<?php
declare(strict_types=1);

namespace Settings\Utility;

use Cake\I18n\Date;
use Settings\Service\SettingsService;
use Settings\ValueObject\Type\DateType;

class Settings
{
    protected static ?SettingsService $instance = null;

    /**
     * Get a setting value by path.
     *
     * Path format: "plugin.key" or "plugin.key.subkey".
     * Example: "company.profile.phone"
     *
     * @param string $path       The path to the setting (plugin.key[.subkey...]).
     * @param mixed  $default    Default value if not found.
     * @return mixed             The setting value or default.
     */
    public static function get(string $path, mixed $default = null): mixed
    {
        if (!self::$instance instanceof SettingsService) {
            self::$instance = new SettingsService();
        }

        return self::$instance->get($path, $default);
    }

    /**
     * Get a setting value by path and return string.
     *
     * Path format: "plugin.key" or "plugin.key.subkey".
     * Example: "company.profile.phone"
     *
     * @return string
     */
    public static function getString(string $path, ?string $default = null): string
    {
        $value = self::get($path, $default);

        return (string)$value;
    }

    /**
     * Get a setting value by path and return it as a date.
     *
     * A day is stored as `Y-m-d` text, because what a setting hands back is a plain value - the
     * cache carries it and templates print it. The date is put together here, for the caller that
     * wants to compare or add to it rather than show it.
     *
     * Path format: "plugin.key" or "plugin.key.subkey".
     * Example: "contracts.checks.earliest_date"
     *
     * @param string $path The path to the setting (plugin.key[.subkey...]).
     * @param string|null $default Default value if not found, as `Y-m-d`.
     * @return \Cake\I18n\Date|null The day, or null where neither the setting nor the default names one.
     */
    public static function getDate(string $path, ?string $default = null): ?Date
    {
        $value = self::get($path, $default);

        if ($value instanceof Date) {
            return $value;
        }

        // Built rather than parsed: parsing reads the day in the machine's timezone, which for
        // a day before about 1884 can hand back the one before it.
        // {@see \Settings\ValueObject\Type\DateType::canonicalDay()}
        $day = DateType::canonicalDay($value);

        return $day === null ? null : new Date($day);
    }

    /**
     * Persist a setting value into the database.
     *
     * Path format: "plugin.key" or "plugin.key.subkey".
     * Example: "bookkeeping.invoices" or "bookkeeping.invoices.vat_rate"
     *
     * If a record with the given plugin/key combination already exists,
     * its value will be updated (merged if subKey is provided).
     * Otherwise, a new record will be created.
     *
     * The value is stored as JSONB, so arrays and scalars are both supported.
     *
     * @param string $path   The path in the form "plugin.key[.subkey...]".
     *                       The plugin part identifies the namespace,
     *                       the key identifies the logical block of settings,
     *                       and optional subKey points to a nested value.
     * @param mixed  $value  The value to store (array, string, number, etc.).
     * @return bool          True on successful save, false on failure.
     */
    public static function set(string $path, mixed $value): bool
    {
        if (!self::$instance instanceof SettingsService) {
            self::$instance = new SettingsService();
        }

        return self::$instance->set($path, $value);
    }

    /**
     * Set settings instance
     */
    public static function setInstance(SettingsService $service): void
    {
        self::$instance = $service;
    }
}
