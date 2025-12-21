<?php
declare(strict_types=1);

namespace Bookkeeping\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Override;

/**
 * Bookkeeping\Command\SendInvoicesCommand Test Case
 *
 * @uses \Bookkeeping\Command\SendInvoicesCommand
 */
class SendInvoicesCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test buildOptionParser method
     *
     * @return void
     * @link \Bookkeeping\Command\SendIssuedInvoicesCommand::buildOptionParser()
     */
    public function testBuildOptionParser(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test execute method
     *
     * @return void
     * @link \Bookkeeping\Command\SendIssuedInvoicesCommand::execute()
     */
    public function testExecute(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
