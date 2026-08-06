<?php
declare(strict_types=1);

namespace Settings\Test\TestCase\Model\Table;

use App\Test\Traits\TableTestTrait;
use Cake\TestSuite\TestCase;
use Override;
use Settings\Model\Table\SettingsTable;

/**
 * Settings\Model\Table\SettingsTable Test Case
 */
class SettingsTableTest extends TestCase
{
    use TableTestTrait;

    /**
     * Test subject
     *
     * @var \Settings\Model\Table\SettingsTable
     */
    protected $Settings;

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
        $config = $this->getTableLocator()->exists('Settings.Settings') ? [] : ['className' => SettingsTable::class];
        $this->Settings = $this->getTableLocator()->get('Settings.Settings', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        /** @phpstan-ignore unset.possiblyHookedProperty */
        unset($this->Settings);

        parent::tearDown();
    }

    /**
     * A new record with nothing filled in is refused - see the trait for why that is the question
     * worth asking here.
     *
     * @return void
     * @link \Settings\Model\Table\SettingsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->assertEmptyRecordIsRefused($this->Settings);
    }

    /**
     * An overlay carrying nothing is refused.
     *
     * A record with a plugin and a key but no value says the same as no record at all, and the
     * service deletes such an overlay rather than storing it. Nothing should reach the table by
     * another way and leave one behind.
     *
     * @return void
     * @link \Settings\Model\Table\SettingsTable::validationDefault()
     */
    public function testValidationDefaultRefusesAnOverlayWithoutAValue(): void
    {
        $withoutValue = $this->Settings->newEntity([
            'plugin' => 'watcher_test',
            'key' => 'block',
        ]);
        $this->assertArrayHasKey('value', $withoutValue->getErrors());

        $withAnEmptyValue = $this->Settings->newEntity([
            'plugin' => 'watcher_test',
            'key' => 'block',
            'value' => [],
        ]);
        $this->assertArrayHasKey('value', $withAnEmptyValue->getErrors());
    }

    /**
     * The rules refuse a record whose references point nowhere - see the trait for why that is
     * the question worth asking here.
     *
     * @return void
     * @link \Settings\Model\Table\SettingsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->assertDanglingReferencesAreRefused($this->Settings);
    }
}
