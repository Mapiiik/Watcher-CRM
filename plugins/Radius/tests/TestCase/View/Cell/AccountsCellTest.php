<?php
declare(strict_types=1);

namespace Radius\Test\TestCase\View\Cell;

use Cake\TestSuite\TestCase;
use Radius\View\Cell\AccountsCell;

/**
 * Radius\View\Cell\AccountsCell Test Case
 */
class AccountsCellTest extends TestCase
{
    /**
     * Request mock
     *
     * @var \Cake\Http\ServerRequest|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $request;

    /**
     * Response mock
     *
     * @var \Cake\Http\Response|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $response;

    /**
     * Test subject
     *
     * @var \Radius\View\Cell\AccountsCell
     */
    protected $Accounts;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->request = $this->getMockBuilder('Cake\Http\ServerRequest')->getMock();
        $this->response = $this->getMockBuilder('Cake\Http\Response')->getMock();
        $this->Accounts = new AccountsCell($this->request, $this->response);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Accounts);

        parent::tearDown();
    }

    /**
     * Test display method
     *
     * @return void
     * @uses \Radius\View\Cell\AccountsCell::display()
     */
    public function testDisplay(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
