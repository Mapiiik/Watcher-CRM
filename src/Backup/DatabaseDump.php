<?php
declare(strict_types=1);

namespace App\Backup;

use RuntimeException;

/**
 * One database, written out and read back in.
 *
 * The connection is addressed as a server rather than as a local socket, so it makes no difference
 * whether it runs beside the application, in another container or on another machine. The password
 * goes through the environment, never through an argument.
 *
 * The client has to be at least as new as the server, which is why a deployment that has none of
 * its own is pointed at the database container rather than at anything older.
 */
final class DatabaseDump
{
    /**
     * The program that writes a dump, and the one that reads it back.
     */
    public const DUMP_PROGRAM = 'pg_dump';
    public const RESTORE_PROGRAM = 'pg_restore';

    /**
     * @param string $name What the connection is called, which is also what its dump is called.
     * @param array<string, mixed> $connection The connection as it has been parsed - host, port,
     *   database, username and password, rather than the URL they were written as.
     */
    public function __construct(
        private string $name,
        private array $connection,
    ) {
    }

    /**
     * What this connection's dump is called inside the backup.
     *
     * @return string
     */
    public function filename(): string
    {
        return 'database-' . $this->name . '.dump';
    }

    /**
     * What `pg_dump` is told, apart from the program itself.
     *
     * Owners and privileges are left out so that the dump can be read back by whichever role the
     * restoring deployment happens to use, which is rarely the one that wrote it.
     *
     * @param string $file Where the dump is written.
     * @return list<string>
     */
    public function dumpArguments(string $file): array
    {
        return [
            ...$this->serverArguments(),
            '--format=custom',
            '--no-owner',
            '--no-privileges',
            '--file=' . $file,
        ];
    }

    /**
     * What `pg_restore` is told, apart from the program itself.
     *
     * `--exit-on-error` because a restore that half worked and said nothing is worse than one that
     * stops: the point of running it is to know the papers are back.
     *
     * @param string $file The dump to read.
     * @param bool $clean Whether what is already there is dropped first.
     * @return list<string>
     */
    public function restoreArguments(string $file, bool $clean = false): array
    {
        return [
            ...$this->serverArguments(),
            '--no-owner',
            '--no-privileges',
            '--exit-on-error',
            ...($clean ? ['--clean', '--if-exists'] : []),
            $file,
        ];
    }

    /**
     * What the program is told through the environment rather than through its arguments.
     *
     * @return array<string, string>
     */
    public function environment(): array
    {
        $password = $this->connection['password'] ?? null;

        return is_string($password) && $password !== '' ? ['PGPASSWORD' => $password] : [];
    }

    /**
     * Writes the dump.
     *
     * @param string $file Where it goes.
     * @return string Whatever the program had to say.
     * @throws \RuntimeException When the program is missing or does not succeed.
     */
    public function dump(string $file): string
    {
        return Process::run(
            [$this->program(self::DUMP_PROGRAM), ...$this->dumpArguments($file)],
            $this->environment(),
        );
    }

    /**
     * Reads the dump back.
     *
     * @param string $file The dump to read.
     * @param bool $clean Whether what is already there is dropped first.
     * @return string Whatever the program had to say.
     * @throws \RuntimeException When the program is missing or does not succeed.
     */
    public function restore(string $file, bool $clean = false): string
    {
        return Process::run(
            [$this->program(self::RESTORE_PROGRAM), ...$this->restoreArguments($file, $clean)],
            $this->environment(),
        );
    }

    /**
     * The same command, for somebody to run where the client actually is.
     *
     * The application's own image carries no client, so under Docker this is the normal way round
     * rather than an exception - and the database container's client matches its server by
     * definition, which is what the version rule asks for.
     *
     * @param string $program Which of the two programs.
     * @param list<string> $arguments What it would have been told.
     * @return string
     */
    public function elsewhere(string $program, array $arguments): string
    {
        return 'docker compose exec -T database ' . $program . ' ' . implode(' ', $arguments);
    }

    /**
     * Where the program is.
     *
     * @param string $program Which one.
     * @return string
     * @throws \RuntimeException When it is not on the path.
     */
    private function program(string $program): string
    {
        $located = Process::locate($program);

        if ($located === null) {
            throw new RuntimeException(sprintf('%s is not installed here.', $program));
        }

        return $located;
    }

    /**
     * Which server, and as whom.
     *
     * @return list<string>
     */
    private function serverArguments(): array
    {
        $arguments = [];

        foreach (['host' => '--host=', 'port' => '--port=', 'username' => '--username='] as $key => $flag) {
            $value = $this->connection[$key] ?? null;
            if ($value !== null && $value !== '') {
                $arguments[] = $flag . $value;
            }
        }

        $arguments[] = '--dbname=' . (string)($this->connection['database'] ?? '');

        return $arguments;
    }
}
