<?php
declare(strict_types=1);

namespace App\Test\Traits;

/**
 * Lets a test say what the environment holds, the way a deployment's `.env` does.
 *
 * The commands take their arguments from the environment when the command line leaves them out,
 * which is how cron calls them - it names the command and nothing else. Reaching them only with
 * arguments spelled out would leave that fallback untried, and it is the path that actually runs.
 *
 * `env()` reads `$_SERVER` before anything else, so that is what is set here; whatever was there
 * before is put back, including its having been absent.
 */
trait EnvironmentTestTrait
{
    /**
     * What the environment held before a test spoke for it.
     *
     * @var array<string, string|null>
     */
    private array $environmentBefore = [];

    /**
     * Set environment values for the length of the test.
     *
     * @param array<string, string> $values Values to set, keyed by name.
     * @return void
     */
    protected function withEnvironment(array $values): void
    {
        foreach ($values as $key => $value) {
            if (!array_key_exists($key, $this->environmentBefore)) {
                $this->environmentBefore[$key] = isset($_SERVER[$key]) ? (string)$_SERVER[$key] : null;
            }

            $_SERVER[$key] = $value;
        }
    }

    /**
     * Put back what the environment held. Call from `tearDown()`.
     *
     * @return void
     */
    protected function restoreEnvironment(): void
    {
        foreach ($this->environmentBefore as $key => $value) {
            if ($value === null) {
                unset($_SERVER[$key]);
                continue;
            }

            $_SERVER[$key] = $value;
        }

        $this->environmentBefore = [];
    }
}
