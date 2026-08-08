<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\SettingsController;
use App\Test\Traits\ControllerTestTrait;
use Cake\Datasource\FactoryLocator;
use Cake\ORM\Locator\TableLocator;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\SettingsController Test Case
 *
 * The actions come from the plugin's trait and are tested there. What is asked here is what only
 * this application decides: it refuses the fallback table class in the web, so a controller that
 * has no table of its own has to be all right with that.
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
     * setUp method
     *
     * Console runs let a table be built for an alias no class answers to, the web does not - see
     * `App\Application::bootstrap()`. The tests are a console run, so the refusal has to be asked
     * for here or the question this case exists for is never put.
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        FactoryLocator::add('Table', (new TableLocator())->allowFallbackClass(false));
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        FactoryLocator::add('Table', new TableLocator());

        parent::tearDown();
    }

    /**
     * Editing a settings block renders, though `Settings` names no table of the application's.
     *
     * @return void
     * @link \Settings\Controller\Trait\SettingsControllerTrait::edit()
     */
    public function testEditRendersWithoutATableOfItsOwn(): void
    {
        $this->login();
        $this->get('/settings/edit/core.company');

        $this->assertResponseOk();
    }
}
