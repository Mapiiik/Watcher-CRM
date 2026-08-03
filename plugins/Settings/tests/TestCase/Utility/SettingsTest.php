<?php
declare(strict_types=1);

namespace Settings\Test\TestCase\Utility;

use Cake\Cache\Cache;
use Cake\TestSuite\TestCase;
use Override;
use Settings\Service\SettingsService;
use Settings\Utility\Settings;

/**
 * Settings\Utility\Settings Test Case
 *
 * The facade is how the rest of both applications reads a setting - a static call with no service
 * to hand. What it holds is a single service kept between calls, which is also what makes it worth
 * testing: the instance outlives a request, and a test that swapped it has to put it back.
 */
class SettingsTest extends TestCase
{
    /**
     * A namespace nothing ships defaults for.
     *
     * @var string
     */
    private const UNCLAIMED = 'watcher_test';

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.AppUsers',
        'plugin.Settings.Settings',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Cache::clear('default');

        // whatever a previous test left behind is a service holding its own memory of the rows this
        // one truncated
        Settings::setInstance(new SettingsService());
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        Cache::clear('default');
        Settings::setInstance(new SettingsService());

        parent::tearDown();
    }

    /**
     * What the facade answers is what the service behind it answers.
     *
     * @return void
     * @link \Settings\Utility\Settings::get()
     */
    public function testGetReadsThroughToTheService(): void
    {
        Settings::set(self::UNCLAIMED . '.block.value', 'stored');

        $this->assertSame('stored', Settings::get(self::UNCLAIMED . '.block.value'));
    }

    /**
     * A caller that names a fallback gets it where nothing is stored and nothing is shipped.
     *
     * @return void
     * @link \Settings\Utility\Settings::get()
     */
    public function testGetReturnsTheFallbackForAnUnknownSetting(): void
    {
        $this->assertSame('fallback', Settings::get(self::UNCLAIMED . '.block.value', 'fallback'));
    }

    /**
     * Most of what reads a setting puts it straight into a template or a document, so this one
     * promises a string whatever was stored - a number kept as one would render, but stops the
     * caller from being able to say it is a string.
     *
     * @return void
     * @link \Settings\Utility\Settings::getString()
     */
    public function testGetStringCastsWhateverWasStored(): void
    {
        Settings::set(self::UNCLAIMED . '.block.number', 42);

        $value = Settings::getString(self::UNCLAIMED . '.block.number');

        $this->assertSame('42', $value);
    }

    /**
     * Nothing stored, nothing shipped and no fallback named still has to be a string rather than
     * null, or the caller that promised one is passing on something else.
     *
     * @return void
     * @link \Settings\Utility\Settings::getString()
     */
    public function testGetStringOfAnUnknownSettingIsEmptyRatherThanNull(): void
    {
        $this->assertSame('', Settings::getString(self::UNCLAIMED . '.block.missing'));
    }

    /**
     * The fallback goes through the same cast.
     *
     * @return void
     * @link \Settings\Utility\Settings::getString()
     */
    public function testGetStringUsesTheFallback(): void
    {
        $this->assertSame('fallback', Settings::getString(self::UNCLAIMED . '.block.missing', 'fallback'));
    }

    /**
     * A service put in place is the one that answers. That is what lets a caller hand the facade a
     * service of its own, and what a test relies on to reset it.
     *
     * @return void
     * @link \Settings\Utility\Settings::setInstance()
     */
    public function testSetInstanceReplacesTheServiceBehindTheFacade(): void
    {
        Settings::set(self::UNCLAIMED . '.block.value', 'stored');
        $this->assertSame('stored', Settings::get(self::UNCLAIMED . '.block.value'));

        Settings::setInstance(new class extends SettingsService {
            /**
             * @param string $path Ignored.
             * @param mixed $default Ignored.
             * @return mixed
             */
            #[Override]
            public function get(string $path, mixed $default = null): mixed
            {
                return 'from the replacement';
            }
        });

        $this->assertSame('from the replacement', Settings::get(self::UNCLAIMED . '.block.value'));
    }
}
