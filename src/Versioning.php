<?php
declare(strict_types=1);

namespace App;

use Cake\Cache\Cache;

/**
 * The version of the application and its last changes, as git says them.
 *
 * Where the application runs without its repository, as in the image, they are read from the
 * files `VERSION` and `CHANGELOG`, which the build writes from git.
 */
class Versioning
{
    /**
     * Check if Git binary is callable.
     *
     * @return bool
     */
    public static function gitBinaryCallable(): bool
    {
        exec('git > /dev/null 2>&1', $response, $exit_code);

        return $exit_code === 1;
    }

    /**
     * Check if Git repository is present.
     *
     * @return bool
     */
    public static function gitRepositoryPresent(): bool
    {
        return file_exists(dirname(__DIR__, 1) . '/.git');
    }

    /**
     * Get application version if installed from Git.
     *
     * @return string
     */
    public static function getVersion(): string
    {
        return Cache::remember(
            'app_version',
            function () {
                if (Versioning::gitRepositoryPresent() && Versioning::gitBinaryCallable()) {
                    /** @psalm-suppress ForbiddenCode */
                    $version = shell_exec('git describe --tags 2>/dev/null');
                    if (is_string($version)) {
                        return rtrim($version);
                    }
                }

                return self::written('VERSION') ?? __('Not installed via Git');
            },
        );
    }

    /**
     * Get application changelog if installed from Git.
     *
     * @return string
     */
    public static function getChangelog(): string
    {
        return Cache::remember(
            'app_changelog',
            function () {
                if (Versioning::gitRepositoryPresent() && Versioning::gitBinaryCallable()) {
                    /** @psalm-suppress ForbiddenCode */
                    $changelog = shell_exec('git log -10 --decorate=short');
                    if (is_string($changelog)) {
                            return rtrim($changelog);
                    }
                }

                return self::written('CHANGELOG') ?? __('Not installed via Git');
            },
        );
    }

    /**
     * What the build wrote from git into a file beside the application, if it did.
     *
     * @param string $name The file.
     * @return string|null
     */
    private static function written(string $name): ?string
    {
        $file = dirname(__DIR__, 1) . '/' . $name;
        if (!is_file($file) || !is_readable($file)) {
            return null;
        }
        $written = rtrim((string)file_get_contents($file));

        return $written === '' ? null : $written;
    }
}
