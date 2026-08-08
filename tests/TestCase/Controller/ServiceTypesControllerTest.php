<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\ServiceTypesController;
use App\Test\Traits\ControllerTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\ServiceTypesController Test Case
 *
 * Smoke tests: every action is requested once and has to answer. They are deliberately shallow -
 * their job is to notice an action that stopped answering at all, which is how the query building
 * bugs in this application have shown up.
 */
#[UsesClass(ServiceTypesController::class)]
class ServiceTypesControllerTest extends TestCase
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
        'app.Queues',
        'app.Services',
    ];

    /**
     * The listing renders.
     *
     * @return void
     * @link \App\Controller\ServiceTypesController::index()
     */
    public function testIndex(): void
    {
        $this->login();
        $this->get('/service-types');

        $this->assertResponseOk();
    }

    /**
     * The listing renders with the search filled in, which builds a different query than the plain
     * listing does and is therefore worth requesting on its own.
     *
     * @return void
     * @link \App\Controller\ServiceTypesController::index()
     */
    public function testIndexWithSearch(): void
    {
        $this->login();
        $this->get('/service-types?search=Lorem');

        $this->assertResponseOk();
    }

    /**
     * The detail of a record renders.
     *
     * @return void
     * @link \App\Controller\ServiceTypesController::view()
     */
    public function testView(): void
    {
        $this->login();
        $this->get('/service-types/view/' . $this->firstId('ServiceTypes'));

        $this->assertResponseOk();
    }

    /**
     * The form for a new record renders.
     *
     * @return void
     * @link \App\Controller\ServiceTypesController::add()
     */
    public function testAdd(): void
    {
        $this->login();
        $this->get('/service-types/add');

        $this->assertResponseOk();
    }

    /**
     * The form of an existing record renders.
     *
     * @return void
     * @link \App\Controller\ServiceTypesController::edit()
     */
    public function testEdit(): void
    {
        $this->login();
        $this->get('/service-types/edit/' . $this->firstId('ServiceTypes'));

        $this->assertResponseOk();
    }

    /**
     * The delete action runs and redirects. Whether the record really goes depends on what else
     * still references it, which is the application rules' business rather than this test's.
     *
     * @return void
     * @link \App\Controller\ServiceTypesController::delete()
     */
    public function testDelete(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/service-types/delete/' . $this->firstId('ServiceTypes'));

        $this->assertRedirect();
    }

    /**
     * A type filled in on the form is really stored. Rendering the form proves the page is there;
     * marshalling, validation, the application rules and the save only ever run on a request that
     * carries data.
     *
     * @return void
     * @link \App\Controller\ServiceTypesController::add()
     */
    public function testAddStoresAType(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/service-types/add', [
            'name' => 'Fibre',
            'contract_number_format' => 'F-%d',
            'invoice_text' => 'Fibre connection',
            'separate_invoice' => '1',
            'installation_address_required' => '1',
        ]);

        $this->assertRedirect();
        /** @var \App\Model\Entity\ServiceType $stored */
        $stored = $this->getTableLocator()->get('ServiceTypes')
            ->find()
            ->where(['name' => 'Fibre'])
            ->firstOrFail();
        $this->assertTrue($stored->separate_invoice);
    }

    /**
     * A type that leaves one of the switches empty is not stored, and the operator is given the
     * form back rather than a redirect that would suggest it went through.
     *
     * @return void
     * @link \App\Controller\ServiceTypesController::add()
     */
    public function testAddRefusesATypeWithAnEmptySwitch(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $serviceTypes = $this->getTableLocator()->get('ServiceTypes');
        $before = $serviceTypes->find()->count();

        $this->post('/service-types/add', [
            'name' => 'Fibre',
            'separate_invoice' => '',
        ]);

        $this->assertResponseOk();
        $this->assertSame($before, $serviceTypes->find()->count());
    }

    /**
     * A change made on the form reaches the record.
     *
     * @return void
     * @link \App\Controller\ServiceTypesController::edit()
     */
    public function testEditStoresTheChange(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $serviceTypeId = $this->firstId('ServiceTypes');
        $this->post('/service-types/edit/' . $serviceTypeId, ['name' => 'Renamed type']);

        $this->assertRedirect();
        $this->assertSame(
            'Renamed type',
            $this->getTableLocator()->get('ServiceTypes')->get($serviceTypeId)->name,
        );
    }
}
