<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\ServiceOverridesController;
use App\Test\Traits\ControllerTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\ServiceOverridesController Test Case
 *
 * Smoke tests: every action is requested once and has to answer. They are deliberately shallow -
 * their job is to notice an action that stopped answering at all, which is how the query building
 * bugs in this application have shown up.
 */
#[UsesClass(ServiceOverridesController::class)]
class ServiceOverridesControllerTest extends TestCase
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
        'app.AccountingProfiles',
        'app.Customers',
        'app.Countries',
        'app.Addresses',
        'app.Commissions',
        'app.ContractStates',
        'app.ServiceTypes',
        'app.Queues',
        'app.Services',
        'app.Contracts',
        'app.ServiceOverrides',
    ];

    /**
     * The listing renders.
     *
     * @return void
     * @link \App\Controller\ServiceOverridesController::index()
     */
    public function testIndex(): void
    {
        $this->login();
        $this->get('/service-overrides');

        $this->assertResponseOk();
    }

    /**
     * The listing renders with the search filled in, which builds a different query than the plain
     * listing does and is therefore worth requesting on its own.
     *
     * @return void
     * @link \App\Controller\ServiceOverridesController::index()
     */
    public function testIndexWithSearch(): void
    {
        $this->login();
        $this->get('/service-overrides?search=Lorem');

        $this->assertResponseOk();
    }

    /**
     * The detail of a record renders.
     *
     * @return void
     * @link \App\Controller\ServiceOverridesController::view()
     */
    public function testView(): void
    {
        $this->login();
        $this->get('/service-overrides/view/' . $this->firstId('ServiceOverrides'));

        $this->assertResponseOk();
    }

    /**
     * The form for a new record renders.
     *
     * @return void
     * @link \App\Controller\ServiceOverridesController::add()
     */
    public function testAdd(): void
    {
        $this->login();
        $this->get('/service-overrides/add');

        $this->assertResponseOk();
    }

    /**
     * The form of an existing record renders.
     *
     * @return void
     * @link \App\Controller\ServiceOverridesController::edit()
     */
    public function testEdit(): void
    {
        $this->login();
        $this->get('/service-overrides/edit/' . $this->firstId('ServiceOverrides'));

        $this->assertResponseOk();
    }

    /**
     * The delete action runs and redirects. Whether the record really goes depends on what else
     * still references it, which is the application rules' business rather than this test's.
     *
     * @return void
     * @link \App\Controller\ServiceOverridesController::delete()
     */
    public function testDelete(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/service-overrides/delete/' . $this->firstId('ServiceOverrides'));

        $this->assertRedirect();
    }
}
