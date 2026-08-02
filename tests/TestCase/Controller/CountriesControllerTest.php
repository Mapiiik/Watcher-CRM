<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\CountriesController;
use App\Test\Traits\ControllerTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\CountriesController Test Case
 *
 * Smoke tests: every action is requested once and has to answer. They are deliberately shallow -
 * their job is to notice an action that stopped answering at all, which is how the query building
 * bugs in this application have shown up.
 */
#[UsesClass(CountriesController::class)]
class CountriesControllerTest extends TestCase
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
    ];

    /**
     * The listing renders.
     *
     * @return void
     * @link \App\Controller\CountriesController::index()
     */
    public function testIndex(): void
    {
        $this->login();
        $this->get('/countries');

        $this->assertResponseOk();
    }

    /**
     * The listing renders with the search filled in, which builds a different query than the plain
     * listing does and is therefore worth requesting on its own.
     *
     * @return void
     * @link \App\Controller\CountriesController::index()
     */
    public function testIndexWithSearch(): void
    {
        $this->login();
        $this->get('/countries?search=Lorem');

        $this->assertResponseOk();
    }

    /**
     * The detail of a record renders.
     *
     * @return void
     * @link \App\Controller\CountriesController::view()
     */
    public function testView(): void
    {
        $this->login();
        $this->get('/countries/view/' . $this->firstId('Countries'));

        $this->assertResponseOk();
    }

    /**
     * The form for a new record renders.
     *
     * @return void
     * @link \App\Controller\CountriesController::add()
     */
    public function testAdd(): void
    {
        $this->login();
        $this->get('/countries/add');

        $this->assertResponseOk();
    }

    /**
     * The form of an existing record renders.
     *
     * @return void
     * @link \App\Controller\CountriesController::edit()
     */
    public function testEdit(): void
    {
        $this->login();
        $this->get('/countries/edit/' . $this->firstId('Countries'));

        $this->assertResponseOk();
    }

    /**
     * The delete action runs and redirects. Whether the record really goes depends on what else
     * still references it, which is the application rules' business rather than this test's.
     *
     * @return void
     * @link \App\Controller\CountriesController::delete()
     */
    public function testDelete(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/countries/delete/' . $this->firstId('Countries'));

        $this->assertRedirect();
    }
}
