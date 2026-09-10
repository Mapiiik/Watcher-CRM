<?php
declare(strict_types=1);

namespace App\Test\TestCase\Command;

use App\Backup\DatabaseDump;
use App\Backup\Process;
use App\Command\BackupCommand;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Command\BackupCommand Test Case
 *
 * A run that actually writes a dump needs a client this deployment may not have, so what is tested
 * here is what happens on either side of that: that a target it cannot write to is refused, and
 * that a missing client is answered with the command to run instead rather than with a stack
 * trace.
 */
#[UsesClass(BackupCommand::class)]
class BackupCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * @link \App\Command\BackupCommand::execute()
     * @return void
     */
    public function testABackupIsRefusedWhereItCannotBeWritten(): void
    {
        $this->exec('backup ' . TMP . 'there-is-no-such-directory-here');

        $this->assertExitError();
        $this->assertErrorContains('is not a directory that can be written to');
    }

    /**
     * The application's own image carries no client, so under Docker this is the usual way round.
     * The operator has to be told where to run the same thing rather than left with a failure.
     *
     * @link \App\Command\BackupCommand::execute()
     * @return void
     */
    public function testAMissingClientNamesTheCommandToRunInstead(): void
    {
        if (Process::locate(DatabaseDump::DUMP_PROGRAM) !== null) {
            $this->markTestSkipped('pg_dump is installed here, so it has nothing to fall back to.');
        }

        $this->exec('backup ' . TMP);

        $this->assertExitError();
        $this->assertErrorContains('pg_dump is not installed here');
        $this->assertOutputContains('docker compose exec -T database pg_dump');
        $this->assertOutputContains('--format=custom');
    }
}
