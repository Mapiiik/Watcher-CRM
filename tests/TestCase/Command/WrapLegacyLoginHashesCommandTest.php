<?php
declare(strict_types=1);

namespace App\Test\TestCase\Command;

use App\Command\WrapLegacyLoginHashesCommand;
use App\CustomerPortal\Passwords;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Command\WrapLegacyLoginHashesCommand Test Case
 *
 * What a run is asked for is that the customer still signs in with the same password, and that a
 * second run changes nothing.
 */
#[UsesClass(WrapLegacyLoginHashesCommand::class)]
class WrapLegacyLoginHashesCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    private const LOGIN_ID = '5457ac8a-1c92-4d5f-a035-41de7fad3509';

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.AppUsers',
        'app.AccountingProfiles',
        'app.Customers',
        'app.Countries',
        'app.Logins',
    ];

    /**
     * A bare SHA-1 is wrapped, still lets the same password in, and stays as it is on the next run.
     *
     * @return void
     * @link \App\Command\WrapLegacyLoginHashesCommand::execute()
     */
    public function testABareSha1IsWrappedOnce(): void
    {
        $this->storePassword(strtoupper(sha1('correct horse')));

        $this->exec('logins wrap_legacy_hashes');

        $this->assertExitSuccess();
        $this->assertOutputContains('Wrapped: 1');
        $wrapped = $this->storedPassword();
        $this->assertTrue(Passwords::isWrapped($wrapped));
        $this->assertTrue(Passwords::verify('correct horse', $wrapped));

        $this->exec('logins wrap_legacy_hashes');

        $this->assertExitSuccess();
        $this->assertOutputContains('Wrapped: 0');
        $this->assertSame($wrapped, $this->storedPassword());
    }

    /**
     * A password already stored as Argon2id is left alone.
     *
     * @return void
     * @link \App\Command\WrapLegacyLoginHashesCommand::execute()
     */
    public function testArgon2idIsLeftAlone(): void
    {
        $stored = Passwords::hash('correct horse');
        $this->storePassword($stored);

        $this->exec('logins wrap_legacy_hashes');

        $this->assertExitSuccess();
        $this->assertSame($stored, $this->storedPassword());
    }

    /**
     * @param string $stored What is to be stored.
     * @return void
     */
    private function storePassword(string $stored): void
    {
        $this->getTableLocator()->get('Logins')->updateAll(['password' => $stored], ['id' => self::LOGIN_ID]);
    }

    /**
     * @return string
     */
    private function storedPassword(): string
    {
        return (string)$this->getTableLocator()->get('Logins')->get(self::LOGIN_ID)->get('password');
    }
}
