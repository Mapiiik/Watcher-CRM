<?php
declare(strict_types=1);

namespace App\Test\TestCase\Command;

use App\Command\AutoAssignContractsToAccessPointsCommand;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Command\AutoAssignContractsToAccessPointsCommand Test Case
 */
#[UsesClass(AutoAssignContractsToAccessPointsCommand::class)]
class AutoAssignContractsToAccessPointsCommandTest extends TestCase
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
     * @link \App\Command\AutoAssignContractsToAccessPointsCommand::buildOptionParser()
     */
    public function testBuildOptionParser(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test execute method
     *
     * @return void
     * @link \App\Command\AutoAssignContractsToAccessPointsCommand::execute()
     */
    public function testExecute(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
