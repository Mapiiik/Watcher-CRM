<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\SoldEquipmentsController;
use App\Test\Traits\ControllerTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\SoldEquipmentsController Test Case
 *
 * Smoke tests: every action is requested once and has to answer. They are deliberately shallow -
 * their job is to notice an action that stopped answering at all, which is how the query building
 * bugs in this application have shown up.
 */
#[UsesClass(SoldEquipmentsController::class)]
class SoldEquipmentsControllerTest extends TestCase
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
        'app.Contracts',
        'app.EquipmentTypes',
        'app.SoldEquipments',
    ];

    /**
     * The listing renders.
     *
     * @return void
     * @link \App\Controller\SoldEquipmentsController::index()
     */
    public function testIndex(): void
    {
        $this->login();
        $this->get('/sold-equipments');

        $this->assertResponseOk();
    }

    /**
     * The listing renders with the search filled in, which builds a different query than the plain
     * listing does and is therefore worth requesting on its own.
     *
     * @return void
     * @link \App\Controller\SoldEquipmentsController::index()
     */
    public function testIndexWithSearch(): void
    {
        $this->login();
        $this->get('/sold-equipments?search=Lorem');

        $this->assertResponseOk();
    }

    /**
     * The detail of a record renders.
     *
     * @return void
     * @link \App\Controller\SoldEquipmentsController::view()
     */
    public function testView(): void
    {
        $this->login();
        $this->get('/sold-equipments/view/' . $this->firstId('SoldEquipments'));

        $this->assertResponseOk();
    }

    /**
     * The form for a new record renders.
     *
     * @return void
     * @link \App\Controller\SoldEquipmentsController::add()
     */
    public function testAdd(): void
    {
        $this->login();
        $this->get('/sold-equipments/add');

        $this->assertResponseOk();
    }

    /**
     * The form of an existing record renders.
     *
     * @return void
     * @link \App\Controller\SoldEquipmentsController::edit()
     */
    public function testEdit(): void
    {
        $this->login();
        $this->get('/sold-equipments/edit/' . $this->firstId('SoldEquipments'));

        $this->assertResponseOk();
    }

    /**
     * The delete action runs and redirects. Whether the record really goes depends on what else
     * still references it, which is the application rules' business rather than this test's.
     *
     * @return void
     * @link \App\Controller\SoldEquipmentsController::delete()
     */
    public function testDelete(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/sold-equipments/delete/' . $this->firstId('SoldEquipments'));

        $this->assertRedirect();
    }
}
