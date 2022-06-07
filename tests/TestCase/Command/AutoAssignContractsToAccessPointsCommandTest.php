<?php
declare(strict_types=1);

namespace App\Test\TestCase\Command;

use Cake\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Command\AutoAssignContractsToAccessPointsCommand Test Case
 *
 * @uses \App\Command\AutoAssignContractsToAccessPointsCommand
 */
class AutoAssignContractsToAccessPointsCommandTest extends TestCase
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
     * @uses \App\Command\AutoAssignContractsToAccessPointsCommand::buildOptionParser()
     */
    public function testBuildOptionParser(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test execute method
     *
     * @return void
     * @uses \App\Command\AutoAssignContractsToAccessPointsCommand::execute()
     */
    public function testExecute(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
