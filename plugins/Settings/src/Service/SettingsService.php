<?php
declare(strict_types=1);

namespace Settings\Service;

use Cake\Cache\Cache;
use Cake\Core\Plugin;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Hash;
use Settings\Exception\SettingValueException;
use Settings\Model\Entity\Setting;
use Settings\Model\Table\SettingsTable;
use Settings\ValueObject\SettingsPath;
use Settings\ValueObject\SettingType;

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
     * Types declared in the defaults, by full path.
     *
     * @var array<string, \Settings\ValueObject\SettingType>
     */
    protected array $types = [];

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

        // Collect the declared types and put their plain values back in their place
        $this->defaults = $this->harvestTypes($this->defaults, '');

        // Optionally also allow Configure to hold defaults
        // $this->defaults = Configure::read('Settings') ?? [];
    }

    /**
     * Take the declared types out of the defaults and remember them by path.
     *
     * A type stands in the defaults where its value belongs, so that what a setting is is written
     * next to what it holds. Once collected, the plain value takes its place again and the rest of
     * the service - and everything it answers - deals with arrays and scalars only.
     *
     * @param array<string, mixed> $node    The defaults, or a branch of them.
     * @param string               $prefix  The path the branch hangs at.
     * @return array<string, mixed>         The branch with types replaced by their values.
     */
    protected function harvestTypes(array $node, string $prefix): array
    {
        foreach ($node as $key => $value) {
            $path = $prefix === '' ? (string)$key : $prefix . '.' . $key;

            if ($value instanceof SettingType) {
                $this->types[$path] = $value;
                $node[$key] = $value->default();

                continue;
            }

            if (is_array($value)) {
                $node[$key] = $this->harvestTypes($value, $path);
            }
        }

        return $node;
    }

    /**
     * The type a setting was declared with, if it was declared at all.
     *
     * @param string $path Settings path (plugin.key[.subKey...]).
     * @return \Settings\ValueObject\SettingType|null
     */
    public function getType(string $path): ?SettingType
    {
        return $this->types[$path] ?? null;
    }

    /**
     * The types declared anywhere below a path, by full path.
     *
     * @param string $path Settings path to look under (plugin.key[.subKey...]).
     * @return array<string, \Settings\ValueObject\SettingType>
     */
    public function getTypes(string $path): array
    {
        $prefix = $path . '.';

        return array_filter(
            $this->types,
            static fn(string $declared): bool => str_starts_with($declared, $prefix),
            ARRAY_FILTER_USE_KEY,
        );
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
                    return $this->mergeOverlay(
                        $defaults,
                        $overlay,
                        $settingsPath->plugin . '.' . $settingsPath->key,
                    );
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
     * Lay an overlay over the defaults.
     *
     * Groups are merged key by key, so an overlay only has to carry what it changes. A declared
     * setting is a leaf and is taken whole: pairing a stored list with a shipped one item by item
     * would leave the tail of the longer one behind and make a list impossible to shorten.
     *
     * A list stored before any type was declared is taken whole as well.
     *
     * @param array<string, mixed> $defaults The shipped values.
     * @param array<string, mixed> $overlay  The stored values.
     * @param string               $prefix   The path the branch hangs at.
     * @return array<string, mixed>          The shipped values with the stored ones laid over them.
     */
    protected function mergeOverlay(array $defaults, array $overlay, string $prefix): array
    {
        foreach ($overlay as $key => $value) {
            $path = $prefix . '.' . $key;

            $isGroup = !isset($this->types[$path])
                && is_array($value)
                && !array_is_list($value)
                && is_array($defaults[$key] ?? null);

            $defaults[$key] = $isGroup
                ? $this->mergeOverlay($defaults[$key], $value, $path)
                : $value;
        }

        return $defaults;
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
     * - The resulting structure is then normalized: a declared setting is checked against its type,
     *   and everything else has empty strings, nulls and empty arrays removed recursively.
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
     * @throws \Settings\Exception\SettingValueException When a value does not fit its declared type.
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

        // Check what was submitted against the declared types and drop what need not be stored
        $current = $this->normalizeOverlay($current, $settingsPath->plugin . '.' . $settingsPath->key);

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
     * Read a submitted overlay against the declared types and keep only what is worth storing.
     *
     * A declared setting is handed to its type, which either returns the value to store or refuses
     * it. What the type gives back is stored, and only dropped when the type says nothing was
     * submitted at all. A value that agrees with the default is stored like any other: submitting
     * it is how an installation says it wants this value and not whatever a later version may ship
     * instead.
     *
     * Everything else is treated as before: empty strings, nulls and empty groups are removed, so
     * an emptied field falls back to the default. A declared list is exempt from that rule, which
     * is what lets `[]` mean a list with no items in it.
     *
     * @param array<string, mixed> $node   Overlay data, or a branch of it.
     * @param string               $prefix The path the branch hangs at.
     * @return array<string, mixed>        The branch with what need not be stored removed.
     * @throws \Settings\Exception\SettingValueException When a value does not fit its declared type.
     */
    protected function normalizeOverlay(array $node, string $prefix): array
    {
        foreach ($node as $key => $value) {
            $path = $prefix . '.' . $key;
            $type = $this->types[$path] ?? null;

            if ($type !== null) {
                try {
                    $normalized = $type->normalize($value);
                } catch (SettingValueException $exception) {
                    // the type knows what it refused, only the service knows which setting it was
                    throw new SettingValueException($exception->getMessage(), $path, $exception);
                }

                if ($normalized === null) {
                    unset($node[$key]);

                    continue;
                }

                $node[$key] = $normalized;

                continue;
            }

            if (is_array($value)) {
                $value = $this->normalizeOverlay($value, $path);

                if ($value === []) {
                    unset($node[$key]);

                    continue;
                }

                $node[$key] = $value;

                continue;
            }

            if ($value === '' || $value === null) {
                unset($node[$key]);
            }
        }

        return $node;
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
