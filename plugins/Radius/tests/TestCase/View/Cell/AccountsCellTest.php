<?php
declare(strict_types=1);

namespace Radius\Test\TestCase\View\Cell;

use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Override;
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
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->request = $this->getMockBuilder(ServerRequest::class)->getMock();
        $this->response = $this->getMockBuilder(Response::class)->getMock();
        $this->Accounts = new AccountsCell($this->request, $this->response);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        /** @phpstan-ignore unset.possiblyHookedProperty */
        unset($this->Accounts);

        parent::tearDown();
    }

    /**
     * Test display method
     *
     * @return void
     * @link \Radius\View\Cell\AccountsCell::display()
     */
    public function testDisplay(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
