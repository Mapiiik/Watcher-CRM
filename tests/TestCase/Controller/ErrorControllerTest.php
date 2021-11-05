<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\ErrorController;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\ErrorController Test Case
 *
 * @uses \App\Controller\ErrorController
 */
class ErrorControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.Error',
    ];

    /**
     * Test beforeFilter method
     *
     * @return void
     * @uses \App\Controller\ErrorController::beforeFilter()
     */
    public function testBeforeFilter(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test beforeRender method
     *
     * @return void
     * @uses \App\Controller\ErrorController::beforeRender()
     */
    public function testBeforeRender(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test afterFilter method
     *
     * @return void
     * @uses \App\Controller\ErrorController::afterFilter()
     */
    public function testAfterFilter(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
