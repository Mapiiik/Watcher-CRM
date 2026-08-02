<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\ContractVersionsController;
use App\Test\Traits\ControllerTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\ContractVersionsController Test Case
 *
 * Smoke tests: every action is requested once and has to answer. They are deliberately shallow -
 * their job is to notice an action that stopped answering at all, which is how the query building
 * bugs in this application have shown up.
 */
#[UsesClass(ContractVersionsController::class)]
class ContractVersionsControllerTest extends TestCase
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
        'app.ContractVersions',
    ];

    /**
     * The listing renders.
     *
     * @return void
     * @link \App\Controller\ContractVersionsController::index()
     */
    public function testIndex(): void
    {
        $this->login();
        $this->get('/contract-versions');

        $this->assertResponseOk();
    }

    /**
     * The listing renders with the search filled in, which builds a different query than the plain
     * listing does and is therefore worth requesting on its own.
     *
     * @return void
     * @link \App\Controller\ContractVersionsController::index()
     */
    public function testIndexWithSearch(): void
    {
        $this->login();
        $this->get('/contract-versions?search=Lorem');

        $this->assertResponseOk();
    }

    /**
     * The detail of a record renders.
     *
     * @return void
     * @link \App\Controller\ContractVersionsController::view()
     */
    public function testView(): void
    {
        $this->login();
        $this->get('/contract-versions/view/' . $this->firstId('ContractVersions'));

        $this->assertResponseOk();
    }

    /**
     * The form for a new record renders.
     *
     * @return void
     * @link \App\Controller\ContractVersionsController::add()
     */
    public function testAdd(): void
    {
        $this->login();
        $this->get('/contract-versions/add');

        $this->assertResponseOk();
    }

    /**
     * The form of an existing record renders.
     *
     * @return void
     * @link \App\Controller\ContractVersionsController::edit()
     */
    public function testEdit(): void
    {
        $this->login();
        $this->get('/contract-versions/edit/' . $this->firstId('ContractVersions'));

        $this->assertResponseOk();
    }

    /**
     * The delete action runs and redirects. Whether the record really goes depends on what else
     * still references it, which is the application rules' business rather than this test's.
     *
     * @return void
     * @link \App\Controller\ContractVersionsController::delete()
     */
    public function testDelete(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/contract-versions/delete/' . $this->firstId('ContractVersions'));

        $this->assertRedirect();
    }
}
