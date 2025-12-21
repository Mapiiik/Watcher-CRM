<?php
declare(strict_types=1);

namespace Settings\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use Override;
use Settings\Model\Table\SettingsTable;

/**
 * Settings\Model\Table\SettingsTable Test Case
 */
class SettingsTableTest extends TestCase
{
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
     * Test validationDefault method
     *
     * @return void
     * @link \Settings\Model\Table\SettingsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \Settings\Model\Table\SettingsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
