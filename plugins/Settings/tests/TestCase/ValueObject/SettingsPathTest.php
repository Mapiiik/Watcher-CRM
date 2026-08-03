<?php
declare(strict_types=1);

namespace Settings\Test\TestCase\ValueObject;

use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use Settings\ValueObject\SettingsPath;

/**
 * Settings\ValueObject\SettingsPath Test Case
 *
 * Everything that reads or writes a setting names it with one of these strings, so what the parts
 * of a path are taken to mean decides which record is read and which cache entry is dropped. It
 * parses without asking anything of a database, so the test asks it directly.
 */
class SettingsPathTest extends TestCase
{
    /**
     * Two parts are a whole path: the namespace and the block within it, with nothing nested.
     *
     * @return void
     * @link \Settings\ValueObject\SettingsPath::fromString()
     */
    public function testFromStringReadsAPluginAndKey(): void
    {
        $path = SettingsPath::fromString('core.company');

        $this->assertSame('core', $path->plugin);
        $this->assertSame('company', $path->key);
        $this->assertNull($path->subKey);
        $this->assertFalse($path->hasSubKey());
    }

    /**
     * Everything past the second part belongs to the sub-key as one dotted path - the nesting
     * inside a stored block is arbitrarily deep, and splitting it further would lose the shape.
     *
     * @return void
     * @link \Settings\ValueObject\SettingsPath::fromString()
     */
    public function testFromStringKeepsTheRestAsOneSubKey(): void
    {
        $path = SettingsPath::fromString('core.company.invoices.phone');

        $this->assertSame('core', $path->plugin);
        $this->assertSame('company', $path->key);
        $this->assertSame('invoices.phone', $path->subKey);
        $this->assertTrue($path->hasSubKey());
    }

    /**
     * A path naming only a namespace is parsed rather than refused, and says of itself that it is
     * not enough to look anything up with.
     *
     * @return void
     * @link \Settings\ValueObject\SettingsPath::isValid()
     */
    public function testAPathWithoutAKeyIsNotValid(): void
    {
        $path = SettingsPath::fromString('core');

        $this->assertSame('core', $path->plugin);
        $this->assertNull($path->key);
        $this->assertFalse($path->isValid());
    }

    /**
     * Two parts are what a lookup needs.
     *
     * @return void
     * @link \Settings\ValueObject\SettingsPath::isValid()
     */
    public function testAPathWithAKeyIsValid(): void
    {
        $this->assertTrue(SettingsPath::fromString('core.company')->isValid());
        $this->assertTrue(SettingsPath::fromString('core.company.invoices.phone')->isValid());
    }

    /**
     * The full key is what addresses a value inside a stored block, so the sub-key is joined back
     * onto the key it was split from.
     *
     * @return void
     * @link \Settings\ValueObject\SettingsPath::fullKey()
     */
    public function testFullKeyJoinsTheSubKeyBackOn(): void
    {
        $this->assertSame('company', SettingsPath::fromString('core.company')->fullKey());
        $this->assertSame(
            'company.invoices.phone',
            SettingsPath::fromString('core.company.invoices.phone')->fullKey(),
        );
    }

    /**
     * Nothing to address, nothing to name it with.
     *
     * @return void
     * @link \Settings\ValueObject\SettingsPath::fullKey()
     */
    public function testFullKeyOfAPathWithoutAKeyIsNull(): void
    {
        $this->assertNull(SettingsPath::fromString('core')->fullKey());
    }

    /**
     * The cache is kept per block rather than per value, so a sub-key must not reach the cache key
     * - writing one value would otherwise leave the rest of the block cached under a stale entry.
     *
     * @return void
     * @link \Settings\ValueObject\SettingsPath::cacheKey()
     */
    public function testCacheKeyNamesTheBlockRatherThanTheValue(): void
    {
        $this->assertSame('settings.core.company', SettingsPath::fromString('core.company')->cacheKey());
        $this->assertSame(
            'settings.core.company',
            SettingsPath::fromString('core.company.invoices.phone')->cacheKey(),
        );
    }

    /**
     * A path that cannot address anything has no cache entry either, and says so rather than
     * naming one that would collide with whatever else is incomplete.
     *
     * @return void
     * @link \Settings\ValueObject\SettingsPath::cacheKey()
     */
    public function testCacheKeyOfAnInvalidPathIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        SettingsPath::fromString('core')->cacheKey();
    }
}
