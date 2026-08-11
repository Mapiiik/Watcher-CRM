<?php
declare(strict_types=1);

namespace Settings\Test\TestCase\Service;

use Cake\Cache\Cache;
use Cake\TestSuite\TestCase;
use Override;
use Settings\Exception\SettingValueException;
use Settings\Service\SettingsService;
use Settings\ValueObject\SettingType;
use Settings\ValueObject\Type\BoolType;
use Settings\ValueObject\Type\ListType;

/**
 * Settings\Service\SettingsService Test Case
 *
 * What a setting is worth is decided in three places at once - the defaults an installation ships,
 * the overlay stored in the database and whatever the caller passed as a fallback - and the service
 * is what puts them in order.
 *
 * The defaults the tests overlay are handed to the service rather than read off the installation.
 * What `config/settings.php` happens to carry is a deployment's business and differs between the
 * applications this plugin is installed in; the ordering is the plugin's own and is the same
 * everywhere. That the shipped file is read at all is asked separately, without saying what is in
 * it.
 *
 * The namespace written to belongs to nothing, so no installation can already hold a value for it.
 */
class SettingsServiceTest extends TestCase
{
    /**
     * A namespace nothing ships defaults for.
     *
     * @var string
     */
    private const PLUGIN = 'watcher_test';

    /**
     * The block within it that the tests read and write.
     *
     * @var string
     */
    private const BLOCK = 'watcher_test.block';

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

        // the overlay is cached under the block it belongs to, and the cache outlives a test run -
        // a stale entry would answer for a row the fixtures have since truncated
        Cache::clear('default');
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

        parent::tearDown();
    }

    /**
     * A service shipping the given block as its defaults.
     *
     * @param array<string, mixed> $defaults What the installation would be shipping for the block.
     * @return \Settings\Service\SettingsService
     */
    private function serviceShipping(array $defaults): SettingsService
    {
        return new class ($defaults) extends SettingsService {
            /**
             * @param array<string, mixed> $blockDefaults Defaults for the block under test.
             */
            public function __construct(array $blockDefaults)
            {
                parent::__construct();

                // the same reading the constructor gives the shipped file, so a block handed over
                // here may declare types just as a real one does
                $this->defaults = $this->harvestTypes(['watcher_test' => ['block' => $blockDefaults]], '');
            }
        };
    }

    /**
     * The defaults an installation ships are read off its own configuration file. Everything below
     * hands the service its defaults instead, so this is what says the real ones are picked up.
     *
     * A declared type stands in the file where its value belongs, and reading the defaults is what
     * puts the value back, so the two are compared with that already done.
     *
     * @return void
     * @link \Settings\Service\SettingsService::__construct()
     */
    public function testTheShippedDefaultsAreRead(): void
    {
        /**
         * @var array<string, array<string, mixed>> $shipped
         * @phpstan-ignore-next-line include.fileNotFound
         */
        $shipped = include CONFIG . 'settings.php';

        $plugin = array_key_first($shipped);
        $this->assertNotNull($plugin, 'the installation ships no defaults at all');

        $key = array_key_first($shipped[$plugin]);
        $this->assertNotNull($key, 'the installation ships a namespace with nothing in it');

        $this->assertSame(
            $this->valuesOf($shipped[$plugin][$key]),
            (new SettingsService())->getDefault($plugin . '.' . $key),
        );
    }

    /**
     * A shipped block with every declared type replaced by the value it declares.
     *
     * @param mixed $shipped A shipped value, or a branch of them.
     * @return mixed
     */
    private function valuesOf(mixed $shipped): mixed
    {
        if ($shipped instanceof SettingType) {
            return $shipped->default();
        }

        if (!is_array($shipped)) {
            return $shipped;
        }

        return array_map($this->valuesOf(...), $shipped);
    }

    /**
     * With nothing stored, what comes back is what the installation ships.
     *
     * @return void
     * @link \Settings\Service\SettingsService::get()
     */
    public function testGetFallsBackToTheDefault(): void
    {
        $settings = $this->serviceShipping(['name' => 'shipped']);

        $this->assertSame('shipped', $settings->get(self::BLOCK . '.name'));
    }

    /**
     * A stored value is what the installation is actually configured with, so it wins over the
     * shipped one. The default itself is left as it was - it is what reverting goes back to.
     *
     * @return void
     * @link \Settings\Service\SettingsService::set()
     */
    public function testSetOverlaysTheDefault(): void
    {
        $settings = $this->serviceShipping(['name' => 'shipped']);

        $this->assertTrue($settings->set(self::BLOCK . '.name', 'overlaid'));

        $this->assertSame('overlaid', $settings->get(self::BLOCK . '.name'));
        $this->assertSame('shipped', $settings->getDefault(self::BLOCK . '.name'));
    }

    /**
     * Writing one value in a block leaves the rest of the block alone. A block is stored whole, so
     * a write that replaced it would silently drop every default beside the one being set.
     *
     * @return void
     * @link \Settings\Service\SettingsService::set()
     */
    public function testSetASubKeyKeepsTheRestOfTheBlock(): void
    {
        $settings = $this->serviceShipping(['name' => 'shipped', 'other' => 'kept']);

        $settings->set(self::BLOCK . '.name', 'overlaid');

        $this->assertSame('kept', $settings->get(self::BLOCK . '.other'));
    }

    /**
     * The overlay is what is stored and nothing else, so it answers for a value nobody has written
     * with null even where the defaults have plenty to say.
     *
     * @return void
     * @link \Settings\Service\SettingsService::getOverlay()
     */
    public function testGetOverlayIgnoresTheDefaults(): void
    {
        $settings = $this->serviceShipping(['name' => 'shipped']);

        $this->assertNull($settings->getOverlay(self::BLOCK . '.name'));

        $settings->set(self::BLOCK . '.name', 'overlaid');

        $this->assertSame('overlaid', $settings->getOverlay(self::BLOCK . '.name'));
    }

    /**
     * Blanking a value is how the settings form says "go back to what was shipped", so an emptied
     * field must not be stored as an empty one.
     *
     * @return void
     * @link \Settings\Service\SettingsService::set()
     */
    public function testSetAnEmptyValueRevertsToTheDefault(): void
    {
        $settings = $this->serviceShipping(['name' => 'shipped']);
        $settings->set(self::BLOCK . '.name', 'overlaid');

        $settings->set(self::BLOCK . '.name', '');

        $this->assertNull($settings->getOverlay(self::BLOCK . '.name'));
        $this->assertSame('shipped', $settings->get(self::BLOCK . '.name'));
    }

    /**
     * Emptying the last value in a block takes the record with it rather than leaving an empty one
     * behind - a stored block with nothing in it is a row that means the same as no row.
     *
     * @return void
     * @link \Settings\Service\SettingsService::set()
     */
    public function testEmptyingTheLastValueRemovesTheRecord(): void
    {
        $settingsTable = $this->getTableLocator()->get('Settings.Settings');
        $settings = new SettingsService();

        $settings->set(self::BLOCK . '.only', 'something');
        $this->assertSame(1, $settingsTable->find()->where(['plugin' => self::PLUGIN])->count());

        $settings->set(self::BLOCK . '.only', '');

        $this->assertSame(0, $settingsTable->find()->where(['plugin' => self::PLUGIN])->count());
    }

    /**
     * A stored list is taken whole. Laying it over the shipped one item by item would leave the
     * tail of the longer one behind, which is to say a list could be lengthened but never shortened.
     *
     * @return void
     * @link \Settings\Service\SettingsService::get()
     */
    public function testAStoredListReplacesTheShippedOneWhole(): void
    {
        $settings = $this->serviceShipping([
            'items' => ListType::ofStrings(['first', 'second', 'third']),
        ]);

        $settings->set(self::BLOCK . '.items', '["only"]');

        $this->assertSame(['only'], $settings->get(self::BLOCK . '.items'));
    }

    /**
     * A list with no items in it is stored as one, rather than read as an emptied field and sent
     * back to the default.
     *
     * @return void
     * @link \Settings\Service\SettingsService::set()
     */
    public function testAListCanBeStoredWithNoItemsInIt(): void
    {
        $settings = $this->serviceShipping([
            'items' => ListType::ofStrings(['shipped']),
        ]);

        $settings->set(self::BLOCK . '.items', '[]');

        $this->assertSame([], $settings->get(self::BLOCK . '.items'));
    }

    /**
     * Leaving the field blank still means what it means everywhere else in the form.
     *
     * @return void
     * @link \Settings\Service\SettingsService::set()
     */
    public function testAnEmptyListFieldRevertsToTheDefault(): void
    {
        $settings = $this->serviceShipping([
            'items' => ListType::ofStrings(['shipped']),
        ]);
        $settings->set(self::BLOCK . '.items', '["stored"]');

        $settings->set(self::BLOCK . '.items', '');

        $this->assertNull($settings->getOverlay(self::BLOCK . '.items'));
        $this->assertSame(['shipped'], $settings->get(self::BLOCK . '.items'));
    }

    /**
     * A value that agrees with what was shipped is stored all the same. Submitting it is how an
     * installation says it wants this value in particular, rather than whatever a later version
     * decides to ship in its place - and that is the whole use of writing it down.
     *
     * @return void
     * @link \Settings\Service\SettingsService::set()
     */
    public function testAValueAgreeingWithTheDefaultIsStoredAllTheSame(): void
    {
        $settings = $this->serviceShipping([
            'switch' => new BoolType(true),
            'items' => ListType::ofStrings(['shipped']),
        ]);

        $settings->set(self::BLOCK . '.switch', '1');
        $settings->set(self::BLOCK . '.items', '["shipped"]');

        $this->assertTrue($settings->getOverlay(self::BLOCK . '.switch'));
        $this->assertSame(['shipped'], $settings->getOverlay(self::BLOCK . '.items'));
    }

    /**
     * Turning a switch off has to survive the round trip to the database. Undeclared, it was stored
     * as text, and every value a text field holds that is not empty comes back as true.
     *
     * @return void
     * @link \Settings\Service\SettingsService::set()
     */
    public function testASwitchTurnedOffStaysOff(): void
    {
        $settings = $this->serviceShipping([
            'switch' => new BoolType(true),
        ]);

        $settings->set(self::BLOCK . '.switch', '0');

        $this->assertFalse($settings->get(self::BLOCK . '.switch'));
    }

    /**
     * A switch can be handed back to the defaults, the way an emptied text field is. Off is an
     * answer of its own, so it cannot be the one that does this - which is why the form offers the
     * shipped value as a third answer rather than as a box left unticked.
     *
     * @return void
     * @link \Settings\Service\SettingsService::set()
     */
    public function testASwitchCanBeHandedBackToTheDefault(): void
    {
        $settings = $this->serviceShipping([
            'switch' => new BoolType(true),
        ]);
        $settings->set(self::BLOCK . '.switch', '0');

        $settings->set(self::BLOCK . '.switch', '');

        $this->assertNull($settings->getOverlay(self::BLOCK . '.switch'));
        $this->assertTrue($settings->get(self::BLOCK . '.switch'));
    }

    /**
     * A value that does not fit what its setting was declared as is refused rather than stored in
     * some shape the application would later have to make sense of.
     *
     * @return void
     * @link \Settings\Service\SettingsService::set()
     */
    public function testAValueThatDoesNotFitItsTypeIsRefused(): void
    {
        $settings = $this->serviceShipping([
            'items' => ListType::ofInts([5]),
        ]);

        $this->expectException(SettingValueException::class);

        $settings->set(self::BLOCK . '.items', '["not a number"]');
    }

    /**
     * Declaring one setting in a block says nothing about the rest of it: a group beside it is
     * still laid over key by key, so an overlay only has to carry what it changes.
     *
     * @return void
     * @link \Settings\Service\SettingsService::get()
     */
    public function testAGroupBesideADeclaredSettingIsStillMergedKeyByKey(): void
    {
        $settings = $this->serviceShipping([
            'items' => ListType::ofStrings(['shipped']),
            'group' => ['name' => 'shipped', 'other' => 'kept'],
        ]);

        $settings->set(self::BLOCK . '.group.name', 'overlaid');

        $this->assertSame('overlaid', $settings->get(self::BLOCK . '.group.name'));
        $this->assertSame('kept', $settings->get(self::BLOCK . '.group.other'));
    }

    /**
     * A value read once is cached under its block, so a later write has to drop that entry. Without
     * it the application would go on answering with what it read before anybody changed anything.
     *
     * @return void
     * @link \Settings\Service\SettingsService::clearCache()
     */
    public function testAWriteIsSeenByAServiceThatAlreadyRead(): void
    {
        $writer = new SettingsService();
        $writer->set(self::BLOCK . '.value', 'first');

        // a second service stands in for the next request, which reads through the shared cache
        // rather than through the one the writer holds in memory
        $this->assertSame('first', (new SettingsService())->get(self::BLOCK . '.value'));

        $writer->set(self::BLOCK . '.value', 'second');

        $this->assertSame('second', (new SettingsService())->get(self::BLOCK . '.value'));
    }

    /**
     * A path naming only a namespace addresses nothing, and reading it is not an error - the caller
     * gets what it said to fall back on.
     *
     * @return void
     * @link \Settings\Service\SettingsService::get()
     */
    public function testGetAnIncompletePathReturnsTheFallback(): void
    {
        $settings = new SettingsService();

        $this->assertSame('fallback', $settings->get(self::PLUGIN, 'fallback'));
        $this->assertNull($settings->getOverlay(self::PLUGIN));
    }

    /**
     * Writing to one is refused rather than guessed at.
     *
     * @return void
     * @link \Settings\Service\SettingsService::set()
     */
    public function testSetAnIncompletePathIsRefused(): void
    {
        $this->assertFalse((new SettingsService())->set(self::PLUGIN, 'anything'));
    }

    /**
     * Nothing ships a default for it and nothing has stored one, so the fallback is all there is.
     *
     * @return void
     * @link \Settings\Service\SettingsService::get()
     */
    public function testGetAnUnknownSettingReturnsTheFallback(): void
    {
        $this->assertSame('fallback', (new SettingsService())->get(self::BLOCK . '.value', 'fallback'));
    }

    /**
     * An array is stored as one and read back the same way, so a block can be written whole.
     *
     * @return void
     * @link \Settings\Service\SettingsService::set()
     */
    public function testSetStoresAWholeBlock(): void
    {
        $settings = new SettingsService();

        $settings->set(self::BLOCK, ['first' => 'one', 'second' => 'two']);

        $this->assertSame(['first' => 'one', 'second' => 'two'], $settings->get(self::BLOCK));
        $this->assertSame('two', $settings->get(self::BLOCK . '.second'));
    }
}
