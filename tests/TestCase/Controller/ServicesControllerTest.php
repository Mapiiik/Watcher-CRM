<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\ServicesController;
use App\Test\Traits\ControllerTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\ServicesController Test Case
 *
 * Smoke tests: every action is requested once and has to answer. They are deliberately shallow -
 * their job is to notice an action that stopped answering at all, which is how the query building
 * bugs in this application have shown up.
 */
#[UsesClass(ServicesController::class)]
class ServicesControllerTest extends TestCase
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
        'app.Billings',
    ];

    /**
     * The listing renders.
     *
     * @return void
     * @link \App\Controller\ServicesController::index()
     */
    public function testIndex(): void
    {
        $this->login();
        $this->get('/services');

        $this->assertResponseOk();
    }

    /**
     * The listing renders with the search filled in, which builds a different query than the plain
     * listing does and is therefore worth requesting on its own.
     *
     * @return void
     * @link \App\Controller\ServicesController::index()
     */
    public function testIndexWithSearch(): void
    {
        $this->login();
        $this->get('/services?search=Lorem');

        $this->assertResponseOk();
    }

    /**
     * The detail of a record renders.
     *
     * @return void
     * @link \App\Controller\ServicesController::view()
     */
    public function testView(): void
    {
        $this->login();
        $this->get('/services/view/' . $this->firstId('Services'));

        $this->assertResponseOk();
    }

    /**
     * The form for a new record renders.
     *
     * @return void
     * @link \App\Controller\ServicesController::add()
     */
    public function testAdd(): void
    {
        $this->login();
        $this->get('/services/add');

        $this->assertResponseOk();
    }

    /**
     * The form of an existing record renders.
     *
     * @return void
     * @link \App\Controller\ServicesController::edit()
     */
    public function testEdit(): void
    {
        $this->login();
        $this->get('/services/edit/' . $this->firstId('Services'));

        $this->assertResponseOk();
    }

    /**
     * The delete action runs and redirects. Whether the record really goes depends on what else
     * still references it, which is the application rules' business rather than this test's.
     *
     * @return void
     * @link \App\Controller\ServicesController::delete()
     */
    public function testDelete(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/services/delete/' . $this->firstId('Services'));

        $this->assertRedirect();
    }

    /**
     * A service filled in on the form is really stored. Rendering the form proves the page is
     * there; marshalling, validation, the application rules and the save only ever run on a request
     * that carries data.
     *
     * @return void
     * @link \App\Controller\ServicesController::add()
     */
    public function testAddStoresAService(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/services/add', [
            'name' => 'Fibre 250',
            'price' => '450',
            'service_type_id' => $this->firstId('ServiceTypes'),
            'queue_id' => $this->firstId('Queues'),
            'not_for_new_customers' => '0',
            'criticality_level' => '10',
        ]);

        $this->assertRedirect();
        /** @var \App\Model\Entity\Service $stored */
        $stored = $this->getTableLocator()->get('Services')
            ->find()
            ->where(['name' => 'Fibre 250'])
            ->firstOrFail();
        $this->assertSame($this->firstId('ServiceTypes'), $stored->service_type_id);
    }

    /**
     * A service of a type that is not there is not stored, and the operator is given the form back
     * rather than a redirect that would suggest it went through.
     *
     * @return void
     * @link \App\Controller\ServicesController::add()
     */
    public function testAddRefusesAServiceOfATypeThatIsNotThere(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $services = $this->getTableLocator()->get('Services');
        $before = $services->find()->count();

        $this->post('/services/add', [
            'name' => 'Fibre 250',
            'service_type_id' => '3f2b1a0c-0000-4000-8000-000000000000',
            'criticality_level' => '10',
        ]);

        $this->assertResponseOk();
        $this->assertSame($before, $services->find()->count());
    }

    /**
     * A change made on the form reaches the record.
     *
     * @return void
     * @link \App\Controller\ServicesController::edit()
     */
    public function testEditStoresTheChange(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $serviceId = $this->firstId('Services');
        $this->post('/services/edit/' . $serviceId, ['name' => 'Renamed service']);

        $this->assertRedirect();
        $this->assertSame(
            'Renamed service',
            $this->getTableLocator()->get('Services')->get($serviceId)->name,
        );
    }
}
