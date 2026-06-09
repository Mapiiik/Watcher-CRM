<?php
declare(strict_types=1);

namespace Settings\Service;

use Cake\Cache\Cache;
use Cake\Core\Plugin;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Hash;
use Settings\Model\Entity\Setting;
use Settings\Model\Table\SettingsTable;
use Settings\ValueObject\SettingsPath;

/**
 * SettingsService
 *
 * Provides access to application settings stored in the database.
 * Supports lazy-loading, Redis caching, and optional bulk-loading of entire plugins.
 */
class SettingsService
{
    use LocatorAwareTrait;

    /**
     * In‑memory cache for merged settings.
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $localCache = [];

    /**
     * Default settings loaded from config/settings.php
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $defaults = [];

    /**
     * Constructor.
     *
     * Initializes the SettingsService by loading default configuration values.
     *
     * The service will:
     * - Load application defaults from `config/settings.php` if present.
     * - Iterate over all loaded plugins and, if a plugin contains its own
     *   `config/settings.php`, merge those defaults into the global defaults.
     * - Store the merged result in `$this->defaults` for later overlay with DB values.
     *
     * The expected structure of each `settings.php` file is an associative array:
     *
     * [
     *     'plugin' => [
     *         'key' => [
     *             'subKey' => 'value',
     *             ...
     *         ],
     *     ],
     * ]
     *
     * Example:
     * [
     *     'core' => [
     *         'company' => [
     *             'name' => 'My Company s.r.o.',
     *             'timezone' => 'Europe/Prague',
     *         ],
     *     ],
     * ]
     *
     * These defaults act as a baseline configuration and are later
     * overlaid by values stored in the database.
     */
    public function __construct()
    {
        // Load app defaults
        $appDefaultsFile = CONFIG . 'settings.php';
        if (file_exists($appDefaultsFile)) {
            /** @phpstan-ignore-next-line include.fileNotFound */
            $defaults = include $appDefaultsFile;
            if (is_array($defaults)) {
                $this->defaults = $defaults;
            }
        }

        // Load defaults from all loaded plugins
        foreach (Plugin::loaded() as $plugin) {
            $pluginDefaultsFile = Plugin::path($plugin) . 'config' . DS . 'settings.php';
            if (file_exists($pluginDefaultsFile)) {
                $pluginDefaults = include $pluginDefaultsFile;
                if (is_array($pluginDefaults)) {
                    // Merge plugin defaults into global defaults
                    $this->defaults = array_replace_recursive($this->defaults, $pluginDefaults);
                }
            }
        }

        // Optionally also allow Configure to hold defaults
        // $this->defaults = Configure::read('Settings') ?? [];
    }

    /**
     * Get a default setting value by path.
     *
     * Path format: "plugin.key" or "plugin.key.subkey".
     * Example: "core.company.name"
     *
     * @param string $path    The path to the setting (plugin.key[.subkey...]).
     * @param mixed  $default Default value if not found.
     * @return mixed          The setting value or default.
     */
    public function getDefault(string $path, mixed $default = null): mixed
    {
        $settingsPath = SettingsPath::fromString($path);

        if (!$settingsPath->isValid()) {
            return $default;
        }

        return $this->resolveSubKey(
            $this->defaults[$settingsPath->plugin][$settingsPath->key] ?? null,
            $settingsPath->subKey,
            $default,
        );
    }

    /**
     * Get settings table for overlay operations.
     */
    protected function getOverlayTable(): SettingsTable
    {
        return $this->fetchTable(SettingsTable::class);
    }

    /**
     * Load settings overlay entity for given plugin and key.
     *
     * @return \Settings\Model\Entity\Setting
     */
    protected function loadOverlayEntity(string $plugin, string $key): Setting
    {
        $settingsTable = $this->getOverlayTable();

        return $settingsTable->findOrNewEntity([
            'plugin' => $plugin,
            'key' => $key,
        ]);
    }

    /**
     * Get overlay value from database by path.
     *
     * Returns only the persisted overlay (without defaults).
     *
     * @param string $path Settings path (plugin.key[.subKey...]).
     * @return mixed|null  Overlay value or null if not present.
     */
    public function getOverlay(string $path): mixed
    {
        $settingsPath = SettingsPath::fromString($path);

        if (!$settingsPath->isValid()) {
            return null;
        }

        $entity = $this->loadOverlayEntity($settingsPath->plugin, $settingsPath->key);

        return $this->resolveSubKey(
            (array)$entity->value,
            $settingsPath->subKey,
            null,
        );
    }

    /**
     * Get a setting value by path.
     *
     * Priority: DB > defaults > $default param.
     *
     * Path format: "plugin.key" or "plugin.key.subkey".
     * Example: "core.company.name"
     *
     * @param string $path    The path to the setting (plugin.key[.subkey...]).
     * @param mixed  $default Default value if not found.
     * @return mixed          The setting value or default.
     */
    public function get(string $path, mixed $default = null): mixed
    {
        $settingsPath = SettingsPath::fromString($path);

        if (!$settingsPath->isValid()) {
            return $default;
        }

        // 1) Check local in-memory cache first
        if (isset($this->localCache[$settingsPath->plugin][$settingsPath->key])) {
            return $this->resolveSubKey(
                $this->localCache[$settingsPath->plugin][$settingsPath->key],
                $settingsPath->subKey,
                $default,
            );
        }

        // 2) Load from cache/DB and merge with defaults
        $merged = Cache::remember(
            $settingsPath->cacheKey(),
            function () use ($settingsPath) {
                // Load defaults for this plugin/key
                $defaults = $this->defaults[$settingsPath->plugin][$settingsPath->key] ?? null;

                // Load DB value for this plugin/key
                $entity = $this->loadOverlayEntity($settingsPath->plugin, $settingsPath->key);

                $overlay = $entity->value;

                // Merge: defaults < DB
                if (is_array($defaults) && is_array($overlay)) {
                    return array_replace_recursive($defaults, $overlay);
                }

                return $overlay ?? $defaults;
            },
            'default',
        );

        // 3) Store merged value in local cache
        $this->localCache[$settingsPath->plugin][$settingsPath->key] = $merged;

        // 4) Return subKey or the whole value
        return $merged !== null
            ? $this->resolveSubKey($merged, $settingsPath->subKey, $default)
            : $default;
    }

    /**
     * Resolve a nested subKey inside a JSONB value using Hash::get.
     *
     * @param mixed       $value    The JSONB value (array or scalar).
     * @param string|null $subKey   The subKey path (dot notation supported).
     * @param mixed       $default  Default value if not found.
     * @return mixed
     */
    protected function resolveSubKey(mixed $value, ?string $subKey, mixed $default): mixed
    {
        if ($subKey === null) {
            return $value;
        }

        if (is_array($value)) {
            return Hash::get($value, $subKey, $default);
        }

        return $default;
    }

    /**
     * Persist a settings overlay value into the database.
     *
     * The method supports both whole-block updates and nested updates using
     * dot-notation paths (plugin.key.subKey...).
     *
     * Workflow:
     * - The provided value is always inserted into the current overlay structure.
     * - The resulting structure is then normalized by removing:
     *   - empty strings
     *   - null values
     *   - empty arrays (recursively)
     * - If the cleaned overlay is empty, the database record is removed,
     *   effectively reverting the setting to its default value.
     *
     * This allows empty form fields to naturally fall back to defaults while
     * keeping the database free of redundant or empty overlay data.
     *
     * Cache for the affected setting block is invalidated on any change.
     *
     * @param string $path  Setting path in the form "plugin.key[.subKey...]".
     * @param mixed  $value Value to store (scalar or array).
     * @return bool         True on success, false on failure.
     */
    public function set(string $path, mixed $value): bool
    {
        $settingsPath = SettingsPath::fromString($path);

        if (!$settingsPath->isValid()) {
            return false;
        }

        $entity = $this->loadOverlayEntity($settingsPath->plugin, $settingsPath->key);

        $current = (array)$entity->value;

        // Always insert first
        if ($settingsPath->subKey !== null) {
            $current = Hash::insert($current, $settingsPath->subKey, $value);
        } else {
            $current = (array)$value;
        }

        // Cleanup empty values
        $current = $this->cleanupOverlay($current);

        // Nothing left → delete overlay
        if ($current === []) {
            if (!$entity->isNew()) {
                $this->getOverlayTable()->delete($entity);
            }

            $this->clearCache($settingsPath->plugin, $settingsPath->key);

            return true;
        }

        $entity->value = $current;

        if ($this->getOverlayTable()->save($entity)) {
            $this->clearCache($settingsPath->plugin, $settingsPath->key);

            return true;
        }

        return false;
    }

    /**
     * Recursively remove empty values from a settings overlay structure.
     *
     * The following values are considered empty and will be removed:
     * - empty strings
     * - null values
     * - empty arrays
     *
     * This method is used to normalize overlay data before persistence,
     * ensuring that only meaningful overrides are stored in the database.
     *
     * @param array $data Overlay data to clean.
     * @return array      Cleaned overlay data.
     */
    protected function cleanupOverlay(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = $this->cleanupOverlay($value);
                if ($value === []) {
                    unset($data[$key]);
                    continue;
                }
                $data[$key] = $value;
            } elseif ($value === '' || $value === null) {
                unset($data[$key]);
            }
        }

        return $data;
    }

    /**
     * Clear cached settings.
     *
     * @param string|null $plugin If provided, clear only this plugin.
     *                            If null, clear all cached plugins.
     * @param string|null $key    If provided together with plugin, clear only this key.
     * @return void
     */
    public function clearCache(?string $plugin = null, ?string $key = null): void
    {
        // Clear specific key
        if ($plugin !== null && $key !== null) {
            unset($this->localCache[$plugin][$key]);

            if (empty($this->localCache[$plugin])) {
                unset($this->localCache[$plugin]);
            }

            Cache::delete(sprintf('settings.%s.%s', $plugin, $key), 'default');

            return;
        }

        // Clear whole plugin
        if ($plugin !== null) {
            unset($this->localCache[$plugin]);
            Cache::delete('settings.' . $plugin, 'default');

            return;
        }

        // Clear everything
        $this->localCache = [];
        Cache::delete('settings', 'default');
    }
}
