<?php
declare(strict_types=1);

namespace App\Test\TestCase\Log;

use Access\Authentication\AuthLog;
use Cake\Log\Engine\ConsoleLog;
use Cake\Log\Engine\FileLog;
use Cake\Log\Log;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * The sign-in log, configured as the other logs of the application are: a file in `logs/` unless
 * `LOG_AUTH_URL` says otherwise.
 *
 * Read from `config/app.php` afresh, as the environment is only read when that file is.
 */
#[UsesClass(AuthLog::class)]
class AuthLogConfigTest extends TestCase
{
    /**
     * A log of its own, beside the one the suite runs with.
     */
    private const PROBE = 'auth_probe';

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        Log::drop(self::PROBE);
        $this->setEnvironment(null);

        parent::tearDown();
    }

    /**
     * Sets the variable, or takes it away, wherever `env()` might find it.
     *
     * @param string|null $url The value, or null for none.
     * @return void
     */
    private function setEnvironment(?string $url): void
    {
        if ($url === null) {
            unset($_SERVER['LOG_AUTH_URL'], $_ENV['LOG_AUTH_URL']);
            putenv('LOG_AUTH_URL');

            return;
        }
        $_SERVER['LOG_AUTH_URL'] = $_ENV['LOG_AUTH_URL'] = $url;
        putenv('LOG_AUTH_URL=' . $url);
    }

    /**
     * The sign-in log as `config/app.php` describes it, set up under a name of its own.
     *
     * @return void
     */
    private function configure(): void
    {
        /** @var array{Log: array{auth: array<string, mixed>}} $config */
        $config = include dirname(__DIR__, 3) . '/config/app.php';

        Log::setConfig(self::PROBE, $config['Log']['auth']);
    }

    /**
     * Without the variable, a file beside the other logs.
     *
     * @return void
     */
    public function testWithoutTheVariableItIsAFileBesideTheOtherLogs(): void
    {
        $this->setEnvironment(null);
        $this->configure();

        $engine = Log::engine(self::PROBE);

        $this->assertInstanceOf(FileLog::class, $engine);
        $this->assertSame(LOGS, $engine->getConfig('path'));
        $this->assertSame('auth', $engine->getConfig('file'));
        $this->assertSame(['auth'], $engine->scopes());
    }

    /**
     * The variable hands the lines to a stream, as a container wants them, and only those of signing in.
     *
     * @return void
     */
    public function testTheVariableHandsTheLinesToAStream(): void
    {
        // a file standing in for standard output, which a test cannot read back
        $stream = TMP . 'auth-log-probe-' . uniqid() . '.log';
        $this->setEnvironment('console:///?stream=' . $stream);
        $this->configure();

        $this->assertInstanceOf(ConsoleLog::class, Log::engine(self::PROBE));

        AuthLog::warning('sign-in refused', ['user' => 'probe', 'ip' => '192.0.2.10']);
        Log::info('a line of another scope', ['scope' => ['other']]);
        Log::drop(self::PROBE);
        $written = (string)file_get_contents($stream);
        unlink($stream);

        $this->assertStringContainsString('warning: sign-in refused user="probe" ip="192.0.2.10"', $written);
        $this->assertStringNotContainsString('another scope', $written);
    }
}
