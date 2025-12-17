<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Cache\Cache;
use Cake\Core\Plugin;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Hash;

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
     *
     * @return void
     */
    public function __construct()
    {
        $this->defaults = [];

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
        // Split path into plugin, key, subKey
        $parts = explode('.', $path);
        $plugin = $parts[0] ?? null;
        $key = $parts[1] ?? null;
        $subKey = isset($parts[2]) ? implode('.', array_slice($parts, 2)) : null;

        if ($plugin === null || $key === null) {
            return $default;
        }

        return $this->resolveSubKey($this->defaults[$plugin][$key] ?? null, $subKey, $default);
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
        // Split path into plugin, key, subKey
        $parts = explode('.', $path);
        $plugin = $parts[0] ?? null;
        $key = $parts[1] ?? null;
        $subKey = isset($parts[2]) ? implode('.', array_slice($parts, 2)) : null;

        if ($plugin === null || $key === null) {
            return $default;
        }

        // 1) Check local in-memory cache first
        if (isset($this->localCache[$plugin][$key])) {
            return $this->resolveSubKey($this->localCache[$plugin][$key], $subKey, $default);
        }

        // 2) Load from cache/DB and merge with defaults
        $cacheKey = "settings.$plugin.$key";
        $merged = Cache::remember(
            $cacheKey,
            function () use ($plugin, $key) {
                // Load defaults for this plugin/key
                $defaults = $this->defaults[$plugin][$key] ?? null;

                // Load DB value for this plugin/key
                /** @var \App\Model\Table\SettingsTable $settingsTable */
                $settingsTable = $this->fetchTable('Settings');
                $row = $settingsTable->find()
                    ->where(['plugin' => $plugin, 'key' => $key])
                    ->first();

                $dbValue = $row?->value;

                // Merge: defaults < DB
                if (is_array($defaults) && is_array($dbValue)) {
                    return array_replace_recursive($defaults, $dbValue);
                } elseif ($dbValue !== null) {
                    return $dbValue;
                } else {
                    return $defaults;
                }
            },
            'default',
        );

        // 3) Store merged value in local cache
        $this->localCache[$plugin][$key] = $merged;

        // 4) Return subKey or the whole value
        if ($merged !== null) {
            return $this->resolveSubKey($merged, $subKey, $default);
        }

        return $default;
    }

    /**
     * Resolve a value from cached plugin data.
     *
     * @param array<string, mixed> $data    The cached plugin data.
     * @param string               $key     The key inside the plugin.
     * @param string|null          $subKey  The subKey path (may contain dots).
     * @param mixed                $default Default value if not found.
     * @return mixed
     */
    protected function resolveValue(array $data, string $key, ?string $subKey, mixed $default): mixed
    {
        if (!array_key_exists($key, $data)) {
            return $default;
        }

        return $this->resolveSubKey($data[$key], $subKey, $default);
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
     * Persist a setting value into the database.
     *
     * Path format: "plugin.key" or "plugin.key.subkey".
     * Example: "bookkeeping_pohoda.invoices" or "bookkeeping_pohoda.invoices.vat_rate"
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
    public function set(string $path, mixed $value): bool
    {
        // Split path into plugin, key, subKey
        $parts = explode('.', $path);
        $plugin = $parts[0] ?? null;
        $key = $parts[1] ?? null;
        $subKey = isset($parts[2]) ? implode('.', array_slice($parts, 2)) : null;

        if ($plugin === null || $key === null) {
            return false;
        }

        /** @var \App\Model\Table\SettingsTable $settingsTable */
        $settingsTable = $this->fetchTable('Settings');
        $entity = $settingsTable->findOrNewEntity([
            'plugin' => $plugin,
            'key' => $key,
        ]);

        // If subKey is provided, merge into existing array
        if ($subKey !== null) {
            $current = (array)$entity->value;
            // Use Cake\Utility\Hash to insert nested value
            $updated = Hash::insert($current, $subKey, $value);
            $entity->value = $updated;
        } else {
            // Replace whole value
            $entity->value = $value;
        }

        $result = (bool)$settingsTable->save($entity);

        if ($result) {
            Cache::delete("settings.$plugin.$key");
            unset($this->localCache[$plugin][$key]);
        }

        return $result;
    }

    /**
     * Clear cached settings.
     *
     * @param string|null $plugin If provided, clear only this plugin.
     *                            If null, clear all cached plugin.
     * @return void
     */
    public function clearCache(?string $plugin = null): void
    {
        if ($plugin) {
            unset($this->localCache[$plugin]);
            Cache::delete("settings.$plugin", 'default');
        } else {
            $this->localCache = [];
            Cache::delete('settings', 'default');
        }
    }
}
