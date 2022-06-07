<?php
declare(strict_types=1);

namespace BookkeepingPohoda\Test\TestCase\Command;

//use BookkeepingPohoda\Command\SendIssuedInvoicesCommand;
use Cake\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * BookkeepingPohoda\Command\SendInvoicesCommand Test Case
 *
 * @uses \BookkeepingPohoda\Command\SendInvoicesCommand
 */
class SendInvoicesCommandTest extends TestCase
{
    use \Cake\Console\TestSuite\ConsoleIntegrationTestTrait;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->useCommandRunner();
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
