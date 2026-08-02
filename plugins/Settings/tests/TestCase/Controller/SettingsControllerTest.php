<?php
declare(strict_types=1);

namespace Settings\Test\TestCase\Controller;

use App\Test\Traits\ControllerTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Settings\Controller\Trait\SettingsControllerTrait Test Case
 *
 * The plugin ships no controller of its own - it hands the application a trait, which
 * `App\Controller\SettingsController` mixes in and routes at `/settings`. That is what these tests
 * request, since the trait is only reachable through it.
 *
 * @link \Settings\Controller\Trait\SettingsControllerTrait
 */
class SettingsControllerTest extends TestCase
{
    use ControllerTestTrait;
    use IntegrationTestTrait;

    /**
     * A settings block the shipped defaults declare, so the edit form has something to render.
     *
     * @var string
     */
    private const PATH = 'core.company';

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
        $this->get('/settings/edit/' . self::PATH);

        $this->assertResponseOk();
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
