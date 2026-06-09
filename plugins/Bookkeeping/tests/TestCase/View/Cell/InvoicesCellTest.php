<?php
declare(strict_types=1);

namespace Bookkeeping\Test\TestCase\View\Cell;

use Bookkeeping\View\Cell\InvoicesCell;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Override;

/**
 * Bookkeeping\View\Cell\InvoicesCell Test Case
 */
class InvoicesCellTest extends TestCase
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
     * @var \Bookkeeping\View\Cell\InvoicesCell
     */
    protected $Invoices;

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
        $this->Invoices = new InvoicesCell($this->request, $this->response);
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
        unset($this->Invoices);

        parent::tearDown();
    }

    /**
     * Test display method
     *
     * @return void
     * @link \Bookkeeping\View\Cell\InvoicesCell::display()
     */
    public function testDisplay(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
