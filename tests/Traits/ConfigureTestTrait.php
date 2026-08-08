<?php
declare(strict_types=1);

namespace App\Test\Traits;

use Cake\Core\Configure;

/**
 * Lets a test say what the installation is configured with, the way a deployment's `.env` does.
 *
 * The commands take their arguments from the configuration when the command line leaves them out,
 * which is how cron calls them - it names the command and nothing else. Reaching them only with
 * arguments spelled out would leave that fallback untried, and it is the path that actually runs.
 *
 * The configuration outlives the test that changed it, so whatever was there before is put back,
 * including its having been absent.
 */
trait ConfigureTestTrait
{
    /**
     * What the configuration held before a test spoke for it.
     *
     * @var array<string, mixed>
     */
    private array $configureBefore = [];

    /**
     * Set configuration values for the length of the test.
     *
     * @param array<string, mixed> $values Values to set, keyed by the dotted path to them.
     * @return void
     */
    protected function withConfigure(array $values): void
    {
        foreach ($values as $key => $value) {
            if (!array_key_exists($key, $this->configureBefore)) {
                $this->configureBefore[$key] = Configure::read($key);
            }

            Configure::write($key, $value);
        }
    }

    /**
     * Put back what the configuration held. Call from `tearDown()`.
     *
     * @return void
     */
    protected function restoreConfigure(): void
    {
        foreach ($this->configureBefore as $key => $value) {
            if ($value === null) {
                Configure::delete($key);
                continue;
            }

            Configure::write($key, $value);
        }

        $this->configureBefore = [];
    }
}
