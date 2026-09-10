<?php
declare(strict_types=1);

namespace App\Backup;

use RuntimeException;

/**
 * The outside programs a backup is made of, run so that a secret never reaches the process list.
 *
 * Everything here takes the command as a list rather than as a string, so nothing is handed to a
 * shell to take apart again, and anything sensitive is passed through the environment instead of
 * as an argument.
 */
final class Process
{
    /**
     * Where the program is, or nothing when it is not on the path.
     *
     * Asked rather than assumed because the answer decides what the caller says: a deployment
     * without the client is told which container to run the same thing in.
     *
     * @param string $program The program to look for.
     * @return string|null
     */
    public static function locate(string $program): ?string
    {
        $path = (string)getenv('PATH');

        foreach (explode(PATH_SEPARATOR, $path) as $directory) {
            if ($directory === '') {
                continue;
            }

            $candidate = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $program;
            if (is_file($candidate) && is_executable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Runs the command and hands back whatever it had to say.
     *
     * The environment given is added to the one this process runs under rather than replacing it,
     * because a replacement would take the path away with it and the program would not be found.
     *
     * @param list<string> $command The program and its arguments.
     * @param array<string, string> $environment What to add to the environment it runs under.
     * @return string What it wrote to standard error, which is where these programs report.
     * @throws \RuntimeException When the program cannot be started or does not succeed.
     */
    public static function run(array $command, array $environment = []): string
    {
        $descriptors = [
            0 => ['file', DIRECTORY_SEPARATOR === '\\' ? 'NUL' : '/dev/null', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        /** @var array<string, string> $inherited */
        $inherited = getenv();

        $process = proc_open($command, $descriptors, $pipes, null, $environment + $inherited);

        if (!is_resource($process)) {
            throw new RuntimeException(sprintf('Could not run %s.', $command[0]));
        }

        $output = (string)stream_get_contents($pipes[1]);
        $errors = (string)stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $status = proc_close($process);

        if ($status !== 0) {
            throw new RuntimeException(sprintf(
                '%s ended with %d: %s',
                $command[0],
                $status,
                trim($errors . "\n" . $output) ?: 'it said nothing about why',
            ));
        }

        return trim($errors);
    }
}
