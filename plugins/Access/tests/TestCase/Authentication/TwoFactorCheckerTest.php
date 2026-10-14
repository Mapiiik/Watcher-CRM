<?php
declare(strict_types=1);

namespace Access\Test\TestCase\Authentication;

use Access\Authentication\TwoFactorChecker;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use Settings\Utility\Settings;

/**
 * Access\Authentication\TwoFactorChecker Test Case
 */
#[UsesClass(TwoFactorChecker::class)]
class TwoFactorCheckerTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'plugin.Settings.Settings',
    ];

    /**
     * @inheritDoc
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Cache::clear('default');
        Configure::write('OneTimePasswordAuthenticator.login', true);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function tearDown(): void
    {
        Cache::clear('default');

        parent::tearDown();
    }

    /**
     * A code is asked for where the settings require one of the account, and where its owner set
     * one up of their own accord.
     *
     * @param bool $everybody Whether everybody has to use one.
     * @param bool $highSecurity Whether the stricter roles have to.
     * @param array<string, mixed> $user The account.
     * @param bool $asked Whether a code is asked for.
     * @return void
     */
    #[DataProvider('accountProvider')]
    public function testACodeIsAskedForWhereRequiredOrChosen(
        bool $everybody,
        bool $highSecurity,
        array $user,
        bool $asked,
    ): void {
        Settings::set('access.security.two_step_verification_required', $everybody);
        Settings::set('access.security.two_step_verification_required_high_security', $highSecurity);

        $this->assertSame($asked, (new TwoFactorChecker())->isRequired($user));
    }

    /**
     * @return array<string, array{bool, bool, array<string, mixed>, bool}>
     */
    public static function accountProvider(): array
    {
        $user = ['id' => 'u', 'role' => 'user', 'is_superuser' => false, 'secret_verified' => false];
        $admin = ['role' => 'admin'] + $user;
        $superuser = ['is_superuser' => true] + $user;
        $chosen = ['secret_verified' => true] + $user;

        return [
            'nothing required, nothing chosen' => [false, false, $user, false],
            'nothing required, an admin' => [false, false, $admin, false],
            'nothing required, chosen' => [false, false, $chosen, true],
            'the stricter roles, a user' => [false, true, $user, false],
            'the stricter roles, an admin' => [false, true, $admin, true],
            'the stricter roles, a superuser' => [false, true, $superuser, true],
            'everybody, a user' => [true, false, $user, true],
            'everybody, an admin' => [true, false, $admin, true],
        ];
    }

    /**
     * Nothing is asked for where one-time codes are off altogether, or of nobody.
     *
     * @return void
     */
    public function testNothingIsAskedForWhereCodesAreOff(): void
    {
        Settings::set('access.security.two_step_verification_required', true);

        $this->assertFalse((new TwoFactorChecker())->isRequired(null));
        Configure::write('OneTimePasswordAuthenticator.login', false);
        $this->assertFalse((new TwoFactorChecker())->isRequired(['id' => 'u', 'role' => 'user']));
    }
}
