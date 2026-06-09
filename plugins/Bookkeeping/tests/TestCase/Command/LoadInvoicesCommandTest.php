<?php
declare(strict_types=1);

namespace Bookkeeping\Test\TestCase\Command;

use Bookkeeping\Command\LoadInvoicesCommand;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Bookkeeping\Command\LoadInvoicesCommand Test Case
 */
#[UsesClass(LoadInvoicesCommand::class)]
class LoadInvoicesCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * Test getDescription method
     *
     * @return void
     * @link \Bookkeeping\Command\LoadInvoicesCommand::getDescription()
     */
    public function testGetDescription(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildOptionParser method
     *
     * @return void
     * @link \Bookkeeping\Command\LoadInvoicesCommand::buildOptionParser()
     */
    public function testBuildOptionParser(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test execute method
     *
     * @return void
     * @link \Bookkeeping\Command\LoadInvoicesCommand::execute()
     */
    public function testExecute(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
