<?php
declare(strict_types=1);

namespace BookkeepingPohoda\Test\TestCase\View\Cell;

use BookkeepingPohoda\View\Cell\InvoicesCell;
use Cake\TestSuite\TestCase;

/**
 * BookkeepingPohoda\View\Cell\InvoicesCell Test Case
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
     * @var \BookkeepingPohoda\View\Cell\InvoicesCell
     */
    protected $Invoices;

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
        $this->Invoices = new InvoicesCell($this->request, $this->response);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Invoices);

        parent::tearDown();
    }

    /**
     * Test display method
     *
     * @return void
     * @uses \BookkeepingPohoda\View\Cell\InvoicesCell::display()
     */
    public function testDisplay(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
