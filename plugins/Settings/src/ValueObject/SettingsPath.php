<?php
declare(strict_types=1);

namespace Settings\ValueObject;

use InvalidArgumentException;

/**
 * Value object representing a parsed settings path.
 *
 * A settings path is a dot-separated string identifying a configuration entry,
 * typically in the form:
 *
 *   plugin.key.subKey
 *
 * Examples:
 * - "core.company"
 * - "core.company.invoices.phone"
 *
 * The path is split into:
 * - plugin  → top-level settings namespace (e.g. "core")
 * - key     → primary settings group (e.g. "company")
 * - subKey  → optional nested path within the group (e.g. "invoices.phone")
 *
 * This object encapsulates the parsing and validation logic and provides a
 * clear, typed representation of the path for use across services,
 * controllers and commands.
 */
final readonly class SettingsPath
{
    /**
     * Create a new SettingsPath value object.
     *
     * @param string|null $plugin Top-level settings namespace (e.g. "core")
     * @param string|null $key    Primary settings group (e.g. "company")
     * @param string|null $subKey Optional nested path within the group
     *                            (e.g. "invoices.phone")
     */
    public function __construct(
        public ?string $plugin,
        public ?string $key,
        public ?string $subKey,
    ) {
        // nothing
    }

    /**
     * Create a SettingsPath instance from a dot-separated string.
     *
     * @param string $path Settings path (e.g. "core.company.invoices.phone")
     * @return self
     */
    public static function fromString(string $path): self
    {
        $parts = explode('.', $path);

        return new self(
            $parts[0] ?? null,
            $parts[1] ?? null,
            isset($parts[2]) ? implode('.', array_slice($parts, 2)) : null,
        );
    }

    /**
     * Determine whether the path contains the minimum required parts.
     *
     * A valid settings path must define both a plugin and a primary key.
     *
     * @phpstan-assert-if-true !null $this->plugin
     * @phpstan-assert-if-true !null $this->key
     */
    public function isValid(): bool
    {
        return $this->plugin !== null && $this->key !== null;
    }

    /**
     * Check whether the path contains a nested sub-key.
     */
    public function hasSubKey(): bool
    {
        return $this->subKey !== null;
    }

    /**
     * Get the full key including the sub-key, if present.
     *
     * Examples:
     * - key="company", subKey=null           → "company"
     * - key="company", subKey="invoices.id"  → "company.invoices.id"
     *
     * @return string|null Full key or null if the primary key is missing
     */
    public function fullKey(): ?string
    {
        if ($this->key === null) {
            return null;
        }

        return $this->subKey
            ? $this->key . '.' . $this->subKey
            : $this->key;
    }

    /**
     * Build cache key for the plugin/key pair.
     *
     * @return string Cache key in the form "settings.{plugin}.{key}".
     * @throws \InvalidArgumentException When plugin or key is missing.
     */
    public function cacheKey(): string
    {
        if (!$this->isValid()) {
            throw new InvalidArgumentException('Cannot build cache key from an invalid settings path.');
        }

        return 'settings.' . $this->plugin . '.' . $this->key;
    }
}
