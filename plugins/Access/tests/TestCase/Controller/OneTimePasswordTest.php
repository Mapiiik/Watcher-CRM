<?php
declare(strict_types=1);

namespace Access\Test\TestCase\Controller;

use Access\Authentication\LoginThrottle;
use Access\Authentication\OneTimePassword;
use Access\Controller\UsersController;
use Access\Test\TestCase\AccountsTrait;
use Cake\Cache\Cache;
use Cake\Datasource\EntityInterface;
use Cake\Log\Engine\ArrayLog;
use Cake\Log\Log;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;
use Settings\Utility\Settings;

/**
 * Signing in with a one-time code, and looking after the codes.
 */
#[UsesClass(UsersController::class)]
class OneTimePasswordTest extends TestCase
{
    use AccountsTrait;
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.AppUsers',
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
        Cache::clear(LoginThrottle::CACHE);
        Log::drop('auth');
        Log::setConfig('auth', ['className' => ArrayLog::class, 'scopes' => ['auth']]);
        $this->getTableLocator()->get('Access.RecoveryCodes')->deleteAll(['1 = 1']);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function tearDown(): void
    {
        Log::drop('auth');
        Cache::clear(LoginThrottle::CACHE);
        Cache::clear('default');

        parent::tearDown();
    }

    /**
     * Somebody whose account has to use one-time codes and has not set them up yet does so on
     * signing in, and is then shown their recovery codes, once.
     *
     * @return void
     */
    public function testAnAccountThatHasToUseCodesSetsThemUpOnSigningIn(): void
    {
        Settings::set('access.security.two_step_verification_required', true);
        $id = (string)$this->addAccount()->get('id');

        $this->signIn();
        $this->assertRedirectContains('/verify');
        $this->get('/verify');
        $this->assertResponseContains('data:image/svg+xml;base64,');
        $secret = (string)$this->account($id)->get('secret');
        $this->assertResponseContains(substr($secret, 0, 4));

        $this->enterCode(OneTimePassword::code($secret, intdiv(time(), 30)));
        $this->assertRedirectContains('/login');
        $this->get('/login');
        $this->assertRedirectContains('/users/recovery-codes');
        $this->carrySession();
        $this->get('/users/recovery-codes');

        $this->assertResponseOk();
        $this->assertSame(10, preg_match_all('/<code>[A-Z2-9]{5}-[A-Z2-9]{5}<\/code>/', $this->_getBodyAsString()));
        $this->assertTrue($this->account($id)->get('secret_verified'));
        $this->carrySession();
        $this->get('/users/recovery-codes');
        $this->assertRedirectContains('/profile');
    }

    /**
     * A one-time code signs in once: the same code again is refused, and sends back to the start.
     *
     * @return void
     */
    public function testAOneTimeCodeSignsInOnce(): void
    {
        $id = $this->addAccountWithCodes();
        $code = OneTimePassword::code(self::SECRET, intdiv(time(), 30));

        $this->signIn();
        $this->assertRedirectContains('/verify');
        $this->enterCode($code);
        $this->get('/login');
        $this->assertRedirect('/');

        $this->_session = [];
        $this->signIn();
        $this->enterCode($code);

        $this->assertRedirectContains('/login');
        $this->assertSession(null, 'Auth');
        /** @var \Cake\Log\Engine\ArrayLog $log */
        $log = Log::engine('auth');
        $this->assertStringContainsString('one-time code refused user="jnovak"', implode("\n", $log->read()));
        $this->assertNotNull($this->account($id)->get('otp_last_timestep'));
    }

    /**
     * A recovery code signs in in place of a one-time code, once.
     *
     * @return void
     */
    public function testARecoveryCodeSignsInOnce(): void
    {
        $id = $this->addAccountWithCodes();
        $codes = $this->getTableLocator()->get('Access.RecoveryCodes')->renew($id);

        $this->signIn();
        $this->enterCode(strtolower($codes[3]));
        $this->get('/login');
        $this->assertRedirect('/');

        $this->_session = [];
        $this->signIn();
        $this->enterCode($codes[3]);
        $this->assertRedirectContains('/login');
        $this->assertSame(9, $this->getTableLocator()->get('Access.RecoveryCodes')->left($id));
        /** @var \Cake\Log\Engine\ArrayLog $log */
        $log = Log::engine('auth');
        $this->assertStringContainsString('recovery code used user="jnovak"', implode("\n", $log->read()));
    }

    /**
     * Somebody who is not required to use one-time codes may turn them off with a current code,
     * and is not asked for one any more. Where they are required, they stay on.
     *
     * @return void
     */
    public function testCodesTurnOffOnlyWhereTheyAreNotRequired(): void
    {
        $id = $this->addAccountWithCodes();
        $this->session(['Auth' => $this->account($id)]);
        Settings::set('access.security.two_step_verification_required', true);
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/users/disable-one-time-password', [
            'code' => OneTimePassword::code(self::SECRET, intdiv(time(), 30)),
        ]);
        $this->assertTrue($this->account($id)->get('secret_verified'));

        Settings::set('access.security.two_step_verification_required', false);
        Cache::clear('default');
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/users/disable-one-time-password', [
            'code' => OneTimePassword::code(self::SECRET, intdiv(time(), 30) + 1),
        ]);

        $this->assertRedirectContains('/profile');
        $account = $this->account($id);
        $this->assertFalse($account->get('secret_verified'));
        $this->assertNull($account->get('secret'));
        $this->assertSame(0, $this->getTableLocator()->get('Access.RecoveryCodes')->left($id));
        $this->_session = [];
        $this->signIn();
        $this->assertRedirect('/');
    }

    /**
     * An admin sees in the list of accounts who has two-step verification on, and takes it away
     * from somebody, for them to set the app up again. The log says who did.
     *
     * @return void
     */
    public function testAnAdminResetsTheCodes(): void
    {
        $id = $this->addAccountWithCodes();
        $this->session(['Auth' => ['id' => $id, 'username' => 'admin', 'role' => 'admin']]);
        $this->get('/users/index');
        $this->assertResponseOk();
        $this->assertResponseContains('Two-Step Verification');
        $this->assertResponseContains('<td>On</td>');
        $this->get('/users/view/' . $id);
        $this->assertResponseContains('Reset Two-Step Verification');
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/users/reset-one-time-password-authenticator/' . $id);

        $this->assertNull($this->account($id)->get('secret'));
        $this->assertSame(0, $this->getTableLocator()->get('Access.RecoveryCodes')->left($id));
        $this->get('/users/index');
        $this->assertResponseNotContains('<td>On</td>');
        /** @var \Cake\Log\Engine\ArrayLog $log */
        $log = Log::engine('auth');
        $this->assertStringContainsString(
            'one-time password reset user="jnovak" by="admin"',
            implode("\n", $log->read()),
        );
    }

    /**
     * Somebody not required to use one-time codes sets them up from their profile, and the
     * profile then says how many recovery codes are left.
     *
     * @return void
     */
    public function testCodesAreSetUpFromTheProfile(): void
    {
        $id = (string)$this->addAccount()->get('id');
        $this->session(['Auth' => $this->account($id)]);
        $this->get('/profile');
        $this->assertResponseContains('/users/one-time-password');

        $this->get('/users/one-time-password');
        $this->assertResponseContains('data:image/svg+xml;base64,');
        $secret = (string)$this->account($id)->get('secret');
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/users/one-time-password', ['code' => OneTimePassword::code($secret, intdiv(time(), 30))]);
        $this->assertRedirectContains('/users/recovery-codes');

        $this->carrySession();
        $this->session(['Auth' => $this->account($id)]);
        $this->get('/users/recovery-codes');
        $this->assertResponseContains('Recovery Codes');
        $this->get('/profile');
        $this->assertResponseOk();
        $this->assertResponseContains('<h5>Authenticator Application (TOTP)</h5>');
        $this->assertResponseContains('On. Recovery codes left: 10.');
        $this->assertResponseContains('action="/users/new-recovery-codes"');
        $this->assertResponseContains('action="/users/disable-one-time-password"');
    }

    /**
     * A secret every test of an account with codes set up shares.
     */
    private const SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    /**
     * Adds an account that has set its one-time codes up.
     *
     * @return string Its id.
     */
    private function addAccountWithCodes(): string
    {
        $id = (string)$this->addAccount()->get('id');
        $this->getTableLocator()->get('AppUsers')->updateQuery()
            ->set(['secret' => self::SECRET, 'secret_verified' => true])
            ->where(['id' => $id])
            ->execute();

        return $id;
    }

    /**
     * The account as stored, secrets and all.
     *
     * @param string $id The account.
     * @return \Cake\Datasource\EntityInterface
     */
    private function account(string $id): EntityInterface
    {
        return $this->getTableLocator()->get('AppUsers')->get($id, finder: 'withSecrets');
    }

    /**
     * Signs in with the password of the test account.
     *
     * @return void
     */
    private function signIn(): void
    {
        $this->enableCsrfToken();
        $this->post('/login', ['username' => 'jnovak', 'password' => 'Kopr na zahrade 7x']);
        $this->carrySession();
    }

    /**
     * Enters a code on the page that asks for it.
     *
     * @param string $code The code.
     * @return void
     */
    private function enterCode(string $code): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/verify', ['code' => $code]);
        $this->carrySession();
    }

    /**
     * Carries what the last request left in the session on to the next one.
     *
     * Read from `$_SESSION`, as asking the request's session for all of it starts it again, which on
     * the command line empties it.
     *
     * @return void
     */
    private function carrySession(): void
    {
        $this->session($_SESSION ?? []);
    }
}
