<?php
declare(strict_types=1);

namespace Radius\Test\TestCase\Controller;

use App\Test\Traits\ControllerTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;
use Radius\Controller\SettingsController;

/**
 * Radius\Controller\SettingsController Test Case
 *
 * Smoke test: the action is requested once and has to answer.
 */
#[UsesClass(SettingsController::class)]
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
     * The settings page of the plugin renders.
     *
     * @return void
     * @link \Radius\Controller\SettingsController::index()
     */
    public function testIndex(): void
    {
        $this->login();
        $this->get('/radius/settings');

        $this->assertResponseOk();
    }
}
