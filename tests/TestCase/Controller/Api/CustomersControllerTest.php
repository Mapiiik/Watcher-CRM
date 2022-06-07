<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\Api\CustomersController Test Case
 *
 * @uses \App\Controller\Api\CustomersController
 */
class CustomersControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected $fixtures = [
        'app.Customers',
        'app.TaxRates',
        'app.Addresses',
        'app.Billings',
        'app.BorrowedEquipments',
        'app.Contracts',
        'app.Emails',
        'app.Ips',
        'app.CustomerLabels',
        'app.Logins',
        'app.Phones',
        'app.RemovedIps',
        'app.SoldEquipments',
        'app.Tasks',
    ];

    /**
     * Test index method
     *
     * @return void
     * @uses \App\Controller\Api\CustomersController::index()
     */
    public function testIndex(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test view method
     *
     * @return void
     * @uses \App\Controller\Api\CustomersController::view()
     */
    public function testView(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test add method
     *
     * @return void
     * @uses \App\Controller\Api\CustomersController::add()
     */
    public function testAdd(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test edit method
     *
     * @return void
     * @uses \App\Controller\Api\CustomersController::edit()
     */
    public function testEdit(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test delete method
     *
     * @return void
     * @uses \App\Controller\Api\CustomersController::delete()
     */
    public function testDelete(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
