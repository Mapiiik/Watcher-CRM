<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\TaskTypesController;
use App\Test\Traits\ControllerTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\TaskTypesController Test Case
 *
 * Smoke tests: every action is requested once and has to answer. They are deliberately shallow -
 * their job is to notice an action that stopped answering at all, which is how the query building
 * bugs in this application have shown up.
 */
#[UsesClass(TaskTypesController::class)]
class TaskTypesControllerTest extends TestCase
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
        'app.TaskStates',
        'app.TaskTypes',
        'app.Tasks',
    ];

    /**
     * The listing renders.
     *
     * @return void
     * @link \App\Controller\TaskTypesController::index()
     */
    public function testIndex(): void
    {
        $this->login();
        $this->get('/task-types');

        $this->assertResponseOk();
    }

    /**
     * The listing renders with the search filled in, which builds a different query than the plain
     * listing does and is therefore worth requesting on its own.
     *
     * @return void
     * @link \App\Controller\TaskTypesController::index()
     */
    public function testIndexWithSearch(): void
    {
        $this->login();
        $this->get('/task-types?search=Lorem');

        $this->assertResponseOk();
    }

    /**
     * The detail of a record renders.
     *
     * @return void
     * @link \App\Controller\TaskTypesController::view()
     */
    public function testView(): void
    {
        $this->login();
        $this->get('/task-types/view/' . $this->firstId('TaskTypes'));

        $this->assertResponseOk();
    }

    /**
     * The form for a new record renders.
     *
     * @return void
     * @link \App\Controller\TaskTypesController::add()
     */
    public function testAdd(): void
    {
        $this->login();
        $this->get('/task-types/add');

        $this->assertResponseOk();
    }

    /**
     * The form of an existing record renders.
     *
     * @return void
     * @link \App\Controller\TaskTypesController::edit()
     */
    public function testEdit(): void
    {
        $this->login();
        $this->get('/task-types/edit/' . $this->firstId('TaskTypes'));

        $this->assertResponseOk();
    }

    /**
     * The delete action runs and redirects. Whether the record really goes depends on what else
     * still references it, which is the application rules' business rather than this test's.
     *
     * @return void
     * @link \App\Controller\TaskTypesController::delete()
     */
    public function testDelete(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/task-types/delete/' . $this->firstId('TaskTypes'));

        $this->assertRedirect();
    }

    /**
     * A type filled in on the form is really stored. Rendering the form proves the page is there;
     * marshalling, validation, the application rules and the save only ever run on a request that
     * carries data.
     *
     * @return void
     * @link \App\Controller\TaskTypesController::add()
     */
    public function testAddStoresAType(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/task-types/add', [
            'name' => 'Installation',
            'customer_required' => '1',
            'contract_required' => '1',
        ]);

        $this->assertRedirect();
        /** @var \App\Model\Entity\TaskType $stored */
        $stored = $this->getTableLocator()->get('TaskTypes')
            ->find()
            ->where(['name' => 'Installation'])
            ->firstOrFail();
        $this->assertTrue($stored->customer_required);
    }

    /**
     * A type that leaves the customer switch empty is not stored, and the operator is given the
     * form back rather than a redirect that would suggest it went through.
     *
     * @return void
     * @link \App\Controller\TaskTypesController::add()
     */
    public function testAddRefusesATypeWithAnEmptyCustomerSwitch(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $taskTypes = $this->getTableLocator()->get('TaskTypes');
        $before = $taskTypes->find()->count();

        $this->post('/task-types/add', [
            'name' => 'Installation',
            'customer_required' => '',
        ]);

        $this->assertResponseOk();
        $this->assertSame($before, $taskTypes->find()->count());
    }

    /**
     * A change made on the form reaches the record.
     *
     * @return void
     * @link \App\Controller\TaskTypesController::edit()
     */
    public function testEditStoresTheChange(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $taskTypeId = $this->firstId('TaskTypes');
        $this->post('/task-types/edit/' . $taskTypeId, ['name' => 'Renamed type']);

        $this->assertRedirect();
        $this->assertSame(
            'Renamed type',
            $this->getTableLocator()->get('TaskTypes')->get($taskTypeId)->name,
        );
    }
}
