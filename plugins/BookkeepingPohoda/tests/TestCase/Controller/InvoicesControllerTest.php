<?php
declare(strict_types=1);

namespace BookkeepingPohoda\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * BookkeepingPohoda\Controller\InvoicesController Test Case
 *
 * @uses \BookkeepingPohoda\Controller\InvoicesController
 */
class InvoicesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'plugin.BookkeepingPohoda.Invoices',
    ];

    /**
     * Test index method
     *
     * @return void
     * @uses \BookkeepingPohoda\Controller\InvoicesController::index()
     */
    public function testIndex(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test view method
     *
     * @return void
     * @uses \BookkeepingPohoda\Controller\InvoicesController::view()
     */
    public function testView(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test add method
     *
     * @return void
     * @uses \BookkeepingPohoda\Controller\InvoicesController::add()
     */
    public function testAdd(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test edit method
     *
     * @return void
     * @uses \BookkeepingPohoda\Controller\InvoicesController::edit()
     */
    public function testEdit(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test delete method
     *
     * @return void
     * @uses \BookkeepingPohoda\Controller\InvoicesController::delete()
     */
    public function testDelete(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
