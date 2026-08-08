<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\CommissionsController;
use App\Test\Traits\ControllerTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\CommissionsController Test Case
 *
 * Smoke tests: every action is requested once and has to answer. They are deliberately shallow -
 * their job is to notice an action that stopped answering at all, which is how the query building
 * bugs in this application have shown up.
 */
#[UsesClass(CommissionsController::class)]
class CommissionsControllerTest extends TestCase
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
        'app.DealerCommissions',
    ];

    /**
     * The listing renders.
     *
     * @return void
     * @link \App\Controller\CommissionsController::index()
     */
    public function testIndex(): void
    {
        $this->login();
        $this->get('/commissions');

        $this->assertResponseOk();
    }

    /**
     * The listing renders with the search filled in, which builds a different query than the plain
     * listing does and is therefore worth requesting on its own.
     *
     * @return void
     * @link \App\Controller\CommissionsController::index()
     */
    public function testIndexWithSearch(): void
    {
        $this->login();
        $this->get('/commissions?search=Lorem');

        $this->assertResponseOk();
    }

    /**
     * The detail of a record renders.
     *
     * @return void
     * @link \App\Controller\CommissionsController::view()
     */
    public function testView(): void
    {
        $this->login();
        $this->get('/commissions/view/' . $this->firstId('Commissions'));

        $this->assertResponseOk();
    }

    /**
     * The form for a new record renders.
     *
     * @return void
     * @link \App\Controller\CommissionsController::add()
     */
    public function testAdd(): void
    {
        $this->login();
        $this->get('/commissions/add');

        $this->assertResponseOk();
    }

    /**
     * The form of an existing record renders.
     *
     * @return void
     * @link \App\Controller\CommissionsController::edit()
     */
    public function testEdit(): void
    {
        $this->login();
        $this->get('/commissions/edit/' . $this->firstId('Commissions'));

        $this->assertResponseOk();
    }

    /**
     * The delete action runs and redirects. Whether the record really goes depends on what else
     * still references it, which is the application rules' business rather than this test's.
     *
     * @return void
     * @link \App\Controller\CommissionsController::delete()
     */
    public function testDelete(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/commissions/delete/' . $this->firstId('Commissions'));

        $this->assertRedirect();
    }

    /**
     * A commission filled in on the form is really stored. Rendering the form proves the page is
     * there; marshalling, validation, the application rules and the save only ever run on a request
     * that carries data.
     *
     * There is no counterpart refusing a commission, because the model asks nothing of one - see
     * the note on {@see \App\Test\TestCase\Model\Table\CommissionsTableTest::testValidationDefault()}.
     *
     * @return void
     * @link \App\Controller\CommissionsController::add()
     */
    public function testAddStoresACommission(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $before = $this->idsIn('Commissions');
        $this->post('/commissions/add', ['name' => 'Referral bonus']);

        $this->assertRedirect();
        $this->assertSame('Referral bonus', $this->addedRecord('Commissions', $before)->get('name'));
    }

    /**
     * A change made on the form reaches the record.
     *
     * @return void
     * @link \App\Controller\CommissionsController::edit()
     */
    public function testEditStoresTheChange(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $commissionId = $this->firstId('Commissions');
        $this->post('/commissions/edit/' . $commissionId, ['name' => 'Renamed commission']);

        $this->assertRedirect();
        $this->assertSame(
            'Renamed commission',
            $this->getTableLocator()->get('Commissions')->get($commissionId)->name,
        );
    }
}
