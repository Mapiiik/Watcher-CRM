<?php
declare(strict_types=1);

namespace App\Test\TestCase\Command;

use App\Command\RestoreCommand;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Command\RestoreCommand Test Case
 *
 * Nothing here ever passes `--force`: that is the option which drops what the database holds, and
 * what it holds while these run is the test database.
 */
#[UsesClass(RestoreCommand::class)]
class RestoreCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * @link \App\Command\RestoreCommand::execute()
     * @return void
     */
    public function testARestoreIsRefusedWhereThereIsNoBackup(): void
    {
        $this->exec('restore ' . TMP . 'there-is-no-such-backup-here');

        $this->assertExitError();
        $this->assertErrorContains('There is no backup at');
    }

    /**
     * The guard that stands between a restore and a database somebody is using. It is asked before
     * anything is written, so a run that is going to refuse has not put the files back first.
     *
     * @link \App\Command\RestoreCommand::execute()
     * @return void
     */
    public function testARestoreIsRefusedIntoADatabaseThatAlreadyHoldsSomething(): void
    {
        $this->exec('restore ' . TMP);

        $this->assertExitError();
        $this->assertErrorContains('already holds');
        $this->assertErrorContains('--force');
    }
}
