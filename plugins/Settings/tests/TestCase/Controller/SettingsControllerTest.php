<?php
declare(strict_types=1);

namespace Settings\Test\TestCase\Controller;

use App\Test\Traits\ControllerTestTrait;
use Cake\Cache\Cache;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Override;
use Settings\Service\SettingsService;

/**
 * Settings\Controller\Trait\SettingsControllerTrait Test Case
 *
 * The plugin ships no controller of its own - it hands the application a trait, which
 * `App\Controller\SettingsController` mixes in and routes at `/settings`. That is what these tests
 * request, since the trait is only reachable through it.
 *
 * Which blocks exist is a deployment's business and differs between the applications this plugin is
 * installed in, so nothing here names one. The tests ask the installation what it ships and work
 * with the first block of it, whatever that turns out to be.
 *
 * @link \Settings\Controller\Trait\SettingsControllerTrait
 */
class SettingsControllerTest extends TestCase
{
    use ControllerTestTrait;
    use IntegrationTestTrait;

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

        // an overlay is cached under the block it belongs to, and the cache outlives a test run
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
     * A block this installation ships defaults for.
     *
     * @return string
     */
    private function aShippedBlock(): string
    {
        $defaults = (new class extends SettingsService {
            /**
             * @return array<string, array<string, mixed>>
             */
            public function shipped(): array
            {
                return $this->defaults;
            }
        })->shipped();

        $plugin = (string)array_key_first($defaults);
        $key = (string)array_key_first($defaults[$plugin]);

        return $plugin . '.' . $key;
    }

    /**
     * The overview of the settings blocks renders.
     *
     * @return void
     * @link \App\Controller\SettingsController::index()
     */
    public function testIndex(): void
    {
        $this->login();
        $this->get('/settings');

        $this->assertResponseOk();
    }

    /**
     * The form of a settings block renders, with the defaults to overlay.
     *
     * @return void
     * @link \Settings\Controller\Trait\SettingsControllerTrait::edit()
     */
    public function testEdit(): void
    {
        $this->login();
        $this->get('/settings/edit/' . $this->aShippedBlock());

        $this->assertResponseOk();
    }

    /**
     * What the form submits reaches the setting, and the operator is sent back to it.
     *
     * The block is handed back what it already holds, the way the form does - it renders the values
     * in force and submits them whole - with one entry added that no installation ships, so the test
     * can recognise its own writing without naming a setting any particular application has.
     *
     * @return void
     * @link \Settings\Controller\Trait\SettingsControllerTrait::edit()
     */
    public function testEditStoresWhatTheFormSubmits(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $block = $this->aShippedBlock();
        $inForce = (array)(new SettingsService())->get($block);

        $this->post('/settings/edit/' . $block, [
            'overlay' => ['watcher_test' => 'stored'] + $inForce,
        ]);

        $this->assertRedirect();

        // the service reads through a cache, and the one it wrote to is not the one this holds
        Cache::clear('default');
        $stored = (array)(new SettingsService())->get($block);

        $this->assertSame('stored', $stored['watcher_test'] ?? null);
        foreach ($inForce as $setting => $value) {
            $this->assertEquals($value, $stored[$setting] ?? null);
        }
    }

    /**
     * A path no defaults declare is not a settings block, whatever it looks like.
     *
     * @return void
     * @link \Settings\Controller\Trait\SettingsControllerTrait::edit()
     */
    public function testEditRejectsAnUnknownBlock(): void
    {
        $this->login();
        $this->get('/settings/edit/core.no-such-block');

        $this->assertResponseCode(404);
    }

    // There are no view, add or delete actions to test: a settings block is not a record that gets
    // created or removed, only a set of defaults the database overlays.
}
