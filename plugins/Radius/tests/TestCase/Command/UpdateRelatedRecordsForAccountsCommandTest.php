<?php
declare(strict_types=1);

namespace Radius\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;
use Radius\Command\UpdateRelatedRecordsForAccountsCommand;

/**
 * Radius\Command\UpdateRelatedRecordsForAllAccountsCommand Test Case
 */
#[UsesClass(UpdateRelatedRecordsForAccountsCommand::class)]
class UpdateRelatedRecordsForAccountsCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * Test buildOptionParser method
     *
     * @return void
     * @link \Radius\Command\UpdateRelatedRecordsForAllAccountsCommand::buildOptionParser()
     */
    public function testBuildOptionParser(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test execute method
     *
     * @return void
     * @link \Radius\Command\UpdateRelatedRecordsForAllAccountsCommand::execute()
     */
    public function testExecute(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
