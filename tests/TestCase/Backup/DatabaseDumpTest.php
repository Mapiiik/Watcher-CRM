<?php
declare(strict_types=1);

namespace App\Test\TestCase\Backup;

use App\Backup\DatabaseDump;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\Backup\DatabaseDump Test Case
 *
 * What is asked of it is what can be asked without a client installed: that the connection it was
 * given turns into the right arguments, and that the password is not among them.
 */
#[CoversClass(DatabaseDump::class)]
class DatabaseDumpTest extends TestCase
{
    /**
     * A connection as it looks once it has been parsed out of a URL.
     *
     * @var array<string, mixed>
     */
    private const CONNECTION = [
        'host' => 'database',
        'port' => 5433,
        'username' => 'watcher',
        'password' => 'a secret nobody should see',
        'database' => 'watcher_crm',
    ];

    /**
     * @link \App\Backup\DatabaseDump::filename()
     * @return void
     */
    public function testTheDumpIsNamedAfterItsConnection(): void
    {
        $this->assertSame('database-default.dump', (new DatabaseDump('default', []))->filename());
        $this->assertSame('database-radius.dump', (new DatabaseDump('radius', []))->filename());
    }

    /**
     * @link \App\Backup\DatabaseDump::dumpArguments()
     * @return void
     */
    public function testTheDumpIsToldWhichServerAndWhereItGoes(): void
    {
        $arguments = (new DatabaseDump('default', self::CONNECTION))->dumpArguments('/backup/db.dump');

        $this->assertSame([
            '--host=database',
            '--port=5433',
            '--username=watcher',
            '--dbname=watcher_crm',
            '--format=custom',
            '--no-owner',
            '--no-privileges',
            '--file=/backup/db.dump',
        ], $arguments);
    }

    /**
     * The whole reason the environment is used at all.
     *
     * @link \App\Backup\DatabaseDump::dumpArguments()
     * @link \App\Backup\DatabaseDump::environment()
     * @return void
     */
    public function testThePasswordIsPassedThroughTheEnvironmentAndNotAsAnArgument(): void
    {
        $dump = new DatabaseDump('default', self::CONNECTION);

        foreach ($dump->dumpArguments('/backup/db.dump') as $argument) {
            $this->assertStringNotContainsString(self::CONNECTION['password'], $argument);
        }
        foreach ($dump->restoreArguments('/backup/db.dump') as $argument) {
            $this->assertStringNotContainsString(self::CONNECTION['password'], $argument);
        }

        $this->assertSame(['PGPASSWORD' => self::CONNECTION['password']], $dump->environment());
    }

    /**
     * @link \App\Backup\DatabaseDump::environment()
     * @return void
     */
    public function testNothingIsPutInTheEnvironmentWhereThereIsNoPassword(): void
    {
        $this->assertSame([], (new DatabaseDump('default', ['database' => 'watcher_crm']))->environment());
        $this->assertSame(
            [],
            (new DatabaseDump('default', ['database' => 'watcher_crm', 'password' => '']))->environment(),
        );
    }

    /**
     * A connection that names no host or port is one reached over a local socket, and the client
     * works that out for itself as long as nobody hands it an empty flag.
     *
     * @link \App\Backup\DatabaseDump::dumpArguments()
     * @return void
     */
    public function testWhatTheConnectionDoesNotSayIsNotPassedAtAll(): void
    {
        $arguments = (new DatabaseDump('default', ['database' => 'watcher_crm']))->dumpArguments('/backup/db.dump');

        $this->assertContains('--dbname=watcher_crm', $arguments);
        foreach ($arguments as $argument) {
            $this->assertStringStartsNotWith('--host=', $argument);
            $this->assertStringStartsNotWith('--port=', $argument);
            $this->assertStringStartsNotWith('--username=', $argument);
        }
    }

    /**
     * @link \App\Backup\DatabaseDump::restoreArguments()
     * @return void
     */
    public function testTheRestoreDropsWhatIsThereOnlyWhenItIsTold(): void
    {
        $dump = new DatabaseDump('default', self::CONNECTION);

        $plain = $dump->restoreArguments('/backup/db.dump');
        $this->assertNotContains('--clean', $plain);
        $this->assertNotContains('--if-exists', $plain);
        $this->assertContains('--exit-on-error', $plain);
        $this->assertSame('/backup/db.dump', end($plain));

        $clean = $dump->restoreArguments('/backup/db.dump', true);
        $this->assertContains('--clean', $clean);
        $this->assertContains('--if-exists', $clean);
    }

    /**
     * @link \App\Backup\DatabaseDump::elsewhere()
     * @return void
     */
    public function testItCanSayWhereToRunTheSameThingInstead(): void
    {
        $dump = new DatabaseDump('default', self::CONNECTION);

        $said = $dump->elsewhere(DatabaseDump::DUMP_PROGRAM, $dump->dumpArguments('/backup/db.dump'));

        $this->assertStringStartsWith('docker compose exec -T database pg_dump ', $said);
        $this->assertStringContainsString('--dbname=watcher_crm', $said);
        $this->assertStringContainsString('--file=/backup/db.dump', $said);
        $this->assertStringNotContainsString(self::CONNECTION['password'], $said);
    }
}
