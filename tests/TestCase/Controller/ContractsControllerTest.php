<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\ContractsController;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\ContractsController Test Case
 *
 * @uses \App\Controller\ContractsController
 */
class ContractsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.Contracts',
        'app.Customers',
        'app.Addresses',
        'app.ServiceTypes',
        'app.Brokerages',
        'app.Billings',
        'app.BorrowedEquipments',
        'app.Ips',
        'app.RemovedIps',
        'app.SoldEquipments',
    ];

    /**
     * Test index method
     *
     * @return void
     * @uses \App\Controller\ContractsController::index()
     */
    public function testIndex(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test view method
     *
     * @return void
     * @uses \App\Controller\ContractsController::view()
     */
    public function testView(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test add method
     *
     * @return void
     * @uses \App\Controller\ContractsController::add()
     */
    public function testAdd(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test edit method
     *
     * @return void
     * @uses \App\Controller\ContractsController::edit()
     */
    public function testEdit(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test delete method
     *
     * @return void
     * @uses \App\Controller\ContractsController::delete()
     */
    public function testDelete(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test print method
     *
     * @return void
     * @uses \App\Controller\ContractsController::print()
     */
    public function testPrint(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
