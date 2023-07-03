<?php
declare(strict_types=1);

namespace BookkeepingPohoda\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * BookkeepingPohoda\Command\SendInvoicesCommand Test Case
 *
 * @uses \BookkeepingPohoda\Command\SendInvoicesCommand
 */
class SendInvoicesCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test buildOptionParser method
     *
     * @return void
     * @uses \BookkeepingPohoda\Command\SendIssuedInvoicesCommand::buildOptionParser()
     */
    public function testBuildOptionParser(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test execute method
     *
     * @return void
     * @uses \BookkeepingPohoda\Command\SendIssuedInvoicesCommand::execute()
     */
    public function testExecute(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
