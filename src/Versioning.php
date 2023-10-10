<?php
declare(strict_types=1);

namespace App;

use Cake\Cache\Cache;

/**
 * Versioning
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
                    $version = shell_exec('git describe --tags 2>/dev/null');
                    if (is_string($version)) {
                        return rtrim($version);
                    }
                }

                return __('Not installed via Git');
            }
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
                    $changelog = shell_exec('git log -10 --decorate=short');
                    if (is_string($changelog)) {
                            return rtrim($changelog);
                    }
                }

                return __('Not installed via Git');
            }
        );
    }
}
