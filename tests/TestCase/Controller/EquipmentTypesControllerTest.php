<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\EquipmentTypesController;
use App\Test\Traits\ControllerTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\EquipmentTypesController Test Case
 *
 * Smoke tests: every action is requested once and has to answer. They are deliberately shallow -
 * their job is to notice an action that stopped answering at all, which is how the query building
 * bugs in this application have shown up.
 */
#[UsesClass(EquipmentTypesController::class)]
class EquipmentTypesControllerTest extends TestCase
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
        'app.BorrowedEquipments',
        'app.SoldEquipments',
    ];

    /**
     * The listing renders.
     *
     * @return void
     * @link \App\Controller\EquipmentTypesController::index()
     */
    public function testIndex(): void
    {
        $this->login();
        $this->get('/equipment-types');

        $this->assertResponseOk();
    }

    /**
     * The listing renders with the search filled in, which builds a different query than the plain
     * listing does and is therefore worth requesting on its own.
     *
     * @return void
     * @link \App\Controller\EquipmentTypesController::index()
     */
    public function testIndexWithSearch(): void
    {
        $this->login();
        $this->get('/equipment-types?search=Lorem');

        $this->assertResponseOk();
    }

    /**
     * The detail of a record renders.
     *
     * @return void
     * @link \App\Controller\EquipmentTypesController::view()
     */
    public function testView(): void
    {
        $this->login();
        $this->get('/equipment-types/view/' . $this->firstId('EquipmentTypes'));

        $this->assertResponseOk();
    }

    /**
     * The form for a new record renders.
     *
     * @return void
     * @link \App\Controller\EquipmentTypesController::add()
     */
    public function testAdd(): void
    {
        $this->login();
        $this->get('/equipment-types/add');

        $this->assertResponseOk();
    }

    /**
     * The form of an existing record renders.
     *
     * @return void
     * @link \App\Controller\EquipmentTypesController::edit()
     */
    public function testEdit(): void
    {
        $this->login();
        $this->get('/equipment-types/edit/' . $this->firstId('EquipmentTypes'));

        $this->assertResponseOk();
    }

    /**
     * The delete action runs and redirects. Whether the record really goes depends on what else
     * still references it, which is the application rules' business rather than this test's.
     *
     * @return void
     * @link \App\Controller\EquipmentTypesController::delete()
     */
    public function testDelete(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/equipment-types/delete/' . $this->firstId('EquipmentTypes'));

        $this->assertRedirect();
    }

    /**
     * A type filled in on the form is really stored. Rendering the form proves the page is there;
     * marshalling, validation, the application rules and the save only ever run on a request that
     * carries data.
     *
     * @return void
     * @link \App\Controller\EquipmentTypesController::add()
     */
    public function testAddStoresAType(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $before = $this->idsIn('EquipmentTypes');
        $this->post('/equipment-types/add', [
            'name' => 'Outdoor unit',
            'price' => '1500',
            'price_with_obligation' => '1',
        ]);

        $this->assertRedirect();
        $this->assertSame('Outdoor unit', $this->addedRecord('EquipmentTypes', $before)->get('name'));
    }

    /**
     * A type without a name is not stored, and the operator is given the form back rather than a
     * redirect that would suggest it went through.
     *
     * @return void
     * @link \App\Controller\EquipmentTypesController::add()
     */
    public function testAddRefusesATypeWithoutAName(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $equipmentTypes = $this->getTableLocator()->get('EquipmentTypes');
        $before = $equipmentTypes->find()->count();

        $this->post('/equipment-types/add', [
            'name' => '',
            'price' => '1500',
        ]);

        $this->assertResponseOk();
        $this->assertSame($before, $equipmentTypes->find()->count());
    }

    /**
     * A change made on the form reaches the record.
     *
     * @return void
     * @link \App\Controller\EquipmentTypesController::edit()
     */
    public function testEditStoresTheChange(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $equipmentTypeId = $this->firstId('EquipmentTypes');
        $this->post('/equipment-types/edit/' . $equipmentTypeId, ['name' => 'Renamed type']);

        $this->assertRedirect();
        $this->assertSame(
            'Renamed type',
            $this->getTableLocator()->get('EquipmentTypes')->get($equipmentTypeId)->name,
        );
    }
}
