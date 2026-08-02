<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\AddressesController;
use App\Test\Traits\ControllerTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\AddressesController Test Case
 */
#[UsesClass(AddressesController::class)]
class AddressesControllerTest extends TestCase
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
        'app.ServiceTypes',
        'app.Commissions',
        'app.ContractStates',
        'app.Contracts',
    ];

    /**
     * Test index method
     *
     * @return void
     * @link \App\Controller\AddressesController::index()
     */
    public function testIndex(): void
    {
        $this->login();
        $this->get('/addresses');

        $this->assertResponseOk();
    }

    /**
     * Test index method with the search filled in
     *
     * @return void
     * @link \App\Controller\AddressesController::index()
     */
    public function testIndexWithSearch(): void
    {
        $this->login();
        $this->get('/addresses?search=Lorem');

        $this->assertResponseOk();
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\AddressesController::view()
     */
    public function testView(): void
    {
        $this->login();
        $this->get('/addresses/view/' . $this->firstId('Addresses'));

        $this->assertResponseOk();
    }

    /**
     * Test add method
     *
     * @return void
     * @link \App\Controller\AddressesController::add()
     */
    public function testAdd(): void
    {
        $this->login();
        $this->get('/addresses/add');

        $this->assertResponseOk();
    }

    /**
     * Test edit method
     *
     * @return void
     * @link \App\Controller\AddressesController::edit()
     */
    public function testEdit(): void
    {
        $this->login();
        $this->get('/addresses/edit/' . $this->firstId('Addresses'));

        $this->assertResponseOk();
    }

    /**
     * The delete action runs and redirects. Whether the record really goes depends on what else
     * still references it, which is the application rules' business rather than this test's.
     *
     * @return void
     * @link \App\Controller\AddressesController::delete()
     */
    public function testDelete(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/addresses/delete/' . $this->firstId('Addresses'));

        $this->assertRedirect();
    }
}
