<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\ServiceOverridesController;
use App\Test\Traits\ControllerTestTrait;
use Cake\I18n\Date;
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
     * Customer the nested routes hang off.
     *
     * @var string
     */
    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    /**
     * Contract the nested routes hang off.
     *
     * @var string
     */
    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

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

    /**
     * Added under its contract, the record is filed under them without the form saying so.
     *
     * The form under a contract leaves those fields out - the route already says which record it is,
     * and the controller fills them in. Posting them in the body instead, as a test reaching the
     * flat route does, asks a different question and leaves this one unasked.
     *
     * @return void
     * @link \App\Controller\ServiceOverridesController::add()
     */
    public function testAddUnderTheRouteFilesItUnderTheRoute(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $before = $this->idsIn('ServiceOverrides');
        $this->post('/customers/' . self::CUSTOMER_ID . '/contracts/' . self::CONTRACT_ID . '/service-overrides/add', [
            'service_id' => $this->firstId('Services'),
            // the period is constrained to start today and to run no longer than a few days
            'valid_from' => Date::today()->toDateString(),
            'valid_until' => Date::today()->addDays(2)->toDateString(),
            'reason' => 'Nested override',
        ]);

        $this->assertRedirect();
        $added = $this->addedRecord('ServiceOverrides', $before);
        $this->assertSame(self::CONTRACT_ID, $added->get('contract_id'));
    }

    /**
     * A change made on the form reaches the record.
     *
     * @return void
     * @link \App\Controller\ServiceOverridesController::edit()
     */
    public function testEditStoresTheChange(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $serviceOverrideId = $this->firstId('ServiceOverrides');
        $this->post('/service-overrides/edit/' . $serviceOverrideId, ['reason' => 'Renamed override']);

        $this->assertRedirect();
        $this->assertSame(
            'Renamed override',
            $this->getTableLocator()->get('ServiceOverrides')->get($serviceOverrideId)->reason,
        );
    }
}
