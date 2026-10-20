<?php
declare(strict_types=1);

namespace Access\Test\TestCase\Controller;

use Access\Authentication\LoginThrottle;
use Access\Authentication\OneTimePassword;
use Access\Authentication\OneTimePasswordAlgorithm;
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
use Settings\Service\SettingsService;
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
        'app.Users',
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
        // the settings are kept in memory too, so the next test reads them again
        Settings::setInstance(new SettingsService());

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
        $this->assertNotNull($this->account($id)->get('last_granted'));
        $this->assertNull($this->account($id)->get('last_denied'));

        $this->_session = [];
        $this->signIn();
        $this->enterCode($code);

        $this->assertRedirectContains('/login');
        $this->assertSession(null, 'Auth');
        // the password was right, the code was not
        $this->assertNotNull($this->account($id)->get('last_denied'));
        /** @var \Cake\Log\Engine\ArrayLog $log */
        $log = Log::engine('auth');
        $this->assertStringContainsString('one-time code refused user="jnovak"', implode("\n", $log->read()));
        $this->assertNotNull($this->account($id)->get('otp_last_timestep'));
    }

    /**
     * A sign-in waiting for its code outlasts the sign-in page shown in the meantime, as a browser
     * sent there by a request of its own, or another tab, does.
     *
     * @return void
     */
    public function testASignInWaitingForItsCodeOutlastsTheSignInPage(): void
    {
        $this->addAccountWithCodes();

        $this->signIn();
        $this->assertRedirectContains('/verify');
        $this->get('/login');
        $this->assertResponseOk();
        $this->carrySession();
        $this->enterCode(OneTimePassword::code(self::SECRET, intdiv(time(), 30)));
        $this->get('/login');

        $this->assertRedirect('/');
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
        $this->get('/users/disable-one-time-password');
        $this->assertResponseOk();
        $this->assertResponseContains('action="/users/disable-one-time-password"');
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/users/disable-one-time-password', [
            'code' => OneTimePassword::code(self::SECRET, intdiv(time(), 30) - 10),
        ]);
        $this->assertResponseOk();
        $this->assertResponseContains('The code is not right.');
        $this->assertTrue($this->account($id)->get('secret_verified'));

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
        $this->assertSame(['jnovak' => true], $this->listedTwoStepVerification());
        $this->get('/users/view/' . $id);
        $this->assertResponseContains('action="/users/reset-one-time-password-authenticator/' . $id . '"');
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/users/reset-one-time-password-authenticator/' . $id);

        $this->assertNull($this->account($id)->get('secret'));
        $this->assertSame(0, $this->getTableLocator()->get('Access.RecoveryCodes')->left($id));
        $this->get('/users/index');
        $this->assertSame(['jnovak' => false], $this->listedTwoStepVerification());
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
        $this->assertResponseContains('<td>Authenticator Application (TOTP)</td>');
        $this->assertResponseContains('<td>10</td>');
        $this->assertResponseContains('<td>SHA-1</td>');
        $this->assertResponseContains('href="/users/new-recovery-codes"');
        $this->assertResponseContains('href="/users/disable-one-time-password"');
    }

    /**
     * New recovery codes are made on a page of their own, with a verification code. A wrong code
     * leaves the codes there are.
     *
     * @return void
     */
    public function testNewRecoveryCodesAskForAVerificationCode(): void
    {
        $id = $this->addAccountWithCodes();
        $codes = $this->getTableLocator()->get('Access.RecoveryCodes');
        $codes->renew($id);
        $before = $codes->find()->where(['user_id' => $id])->all()->extract('id')->toList();
        $this->session(['Auth' => $this->account($id)]);
        $this->get('/users/new-recovery-codes');
        $this->assertResponseOk();
        $this->assertResponseContains('action="/users/new-recovery-codes"');

        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/users/new-recovery-codes', [
            'code' => OneTimePassword::code(self::SECRET, intdiv(time(), 30) - 10),
        ]);
        $this->assertResponseOk();
        $this->assertResponseContains('The code is not right.');
        $this->assertSame($before, $codes->find()->where(['user_id' => $id])->all()->extract('id')->toList());

        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/users/new-recovery-codes', ['code' => OneTimePassword::code(self::SECRET, intdiv(time(), 30))]);
        $this->assertRedirectContains('/users/recovery-codes');
        $this->carrySession();
        $this->session(['Auth' => $this->account($id)]);
        $this->get('/users/recovery-codes');
        $this->assertResponseOk();
        $this->assertSame(10, preg_match_all('/<td><code>/', $this->_getBodyAsString()));
        $this->assertNotContains($before[0], $codes->find()->where(['user_id' => $id])->all()->extract('id')->toList());
    }

    /**
     * Codes are set up with the hash function the settings name, and an account keeps its own
     * after the settings name another.
     *
     * @return void
     */
    public function testCodesAreSetUpWithTheHashFunctionTheSettingsName(): void
    {
        Settings::set('access.security.two_step_verification_required', true);
        Settings::set('access.security.two_step_verification_algorithm', 'SHA256');
        $id = (string)$this->addAccount()->get('id');

        $this->signIn();
        $this->get('/verify');
        $this->assertResponseContains('understands SHA-256');
        $this->assertResponseNotContains('Microsoft Authenticator or Bitwarden Authenticator');
        $account = $this->account($id);
        $this->assertSame('SHA256', $account->get('otp_algorithm'));
        $secret = (string)$account->get('secret');
        $this->assertSame(52, strlen($secret));

        $this->enterCode(OneTimePassword::code($secret, intdiv(time(), 30), OneTimePasswordAlgorithm::Sha256));
        $this->get('/login');
        $this->assertTrue($this->account($id)->get('secret_verified'));

        Settings::set('access.security.two_step_verification_algorithm', 'SHA1');
        $this->_session = [];
        $this->signIn();
        $this->enterCode(OneTimePassword::code($secret, intdiv(time(), 30) + 1, OneTimePasswordAlgorithm::Sha256));
        $this->get('/login');
        $this->assertRedirect('/');
        $this->assertSame('SHA256', $this->account($id)->get('otp_algorithm'));
    }

    /**
     * Whoever sets the app up on signing in chooses the hash function; the settings name only the
     * one offered first. Choosing draws a new QR code and asks for no code yet, so it is no failure.
     *
     * @return void
     */
    public function testTheHashFunctionIsChosenOnSigningIn(): void
    {
        Settings::set('access.security.two_step_verification_required', true);
        $id = (string)$this->addAccount()->get('id');

        $this->signIn();
        $this->get('/verify');
        $this->assertResponseContains('<option value="SHA1" selected="selected">');
        $this->assertSame(32, strlen((string)$this->account($id)->get('secret')));

        $this->chooseAlgorithm('/verify', 'SHA512');
        $this->assertResponseOk();
        $this->assertResponseContains('<option value="SHA512" selected="selected">');
        $account = $this->account($id);
        $this->assertSame('SHA512', $account->get('otp_algorithm'));
        $secret = (string)$account->get('secret');
        $this->assertSame(103, strlen($secret));
        $this->assertNull($account->get('last_denied'));

        $this->enterCode(OneTimePassword::code($secret, intdiv(time(), 30), OneTimePasswordAlgorithm::Sha512));
        $this->get('/login');
        $this->assertRedirect('/users/recovery-codes');
        $this->assertTrue($this->account($id)->get('secret_verified'));
    }

    /**
     * Whoever sets the app up from the profile chooses the hash function too.
     *
     * @return void
     */
    public function testTheHashFunctionIsChosenInTheProfile(): void
    {
        $id = (string)$this->addAccount()->get('id');
        $this->session(['Auth' => $this->account($id)]);
        $this->get('/users/one-time-password');

        $this->chooseAlgorithm('/users/one-time-password', 'SHA256');
        $this->assertResponseOk();
        $this->assertResponseContains('understands SHA-256');
        $secret = (string)$this->account($id)->get('secret');
        $this->assertSame(52, strlen($secret));

        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/users/one-time-password', [
            'algorithm' => 'SHA256',
            'code' => OneTimePassword::code($secret, intdiv(time(), 30), OneTimePasswordAlgorithm::Sha256),
        ]);
        $this->assertRedirectContains('/users/recovery-codes');
        $this->assertSame('SHA256', $this->account($id)->get('otp_algorithm'));
    }

    /**
     * A key that cannot be read, as after the salt changed, is set up again on signing in rather
     * than let the account in without a code.
     *
     * @return void
     */
    public function testAKeyThatCannotBeReadIsSetUpAgain(): void
    {
        $id = $this->addAccountWithCodes();
        $users = $this->getTableLocator()->get('Users');
        $users->getConnection()->updateQuery('users', ['secret' => 'v1:c3RvcmVkIHVuZGVyIGFub3RoZXIgc2FsdA=='])
            ->where(['id' => $id])
            ->execute();

        $this->signIn();
        $this->assertRedirectContains('/verify');
        $this->get('/verify');

        $this->assertResponseContains('data:image/svg+xml;base64,');
        $account = $this->account($id);
        $this->assertFalse($account->get('secret_verified'));
        $this->assertSame(32, strlen((string)$account->get('secret')));
        /** @var \Cake\Log\Engine\ArrayLog $log */
        $log = Log::engine('auth');
        $this->assertStringContainsString('one-time password unreadable user="jnovak"', implode("\n", $log->read()));
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
        $this->getTableLocator()->get('Users')->updateQuery()
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
        return $this->getTableLocator()->get('Users')->get($id, finder: 'withSecrets');
    }

    /**
     * Whether the accounts the list of accounts shows sign in with two-step verification, by
     * username.
     *
     * @return array<string, bool>
     */
    private function listedTwoStepVerification(): array
    {
        $listed = [];
        foreach ($this->viewVariable('users') as $user) {
            $listed[(string)$user->get('username')] = $user->hasTwoStepVerification();
        }

        return $listed;
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
     * Chooses a hash function on a page that sets the app up, as the list does when it changes.
     *
     * @param string $url The page.
     * @param string $algorithm The hash function.
     * @return void
     */
    private function chooseAlgorithm(string $url, string $algorithm): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->setUnlockedFields(['refresh']);
        $this->post($url, ['algorithm' => $algorithm, 'code' => '', 'refresh' => 'refresh']);
        $this->carrySession();
    }

    /**
     * Carries what the last request left in the session on to the next one.
     *
     * Read from `$_SESSION`, as asking the request's session for all of it starts it again, which on
     * the command line empties it. It takes the place of what was carried before, so that what the
     * request took out of the session stays out, as in a browser.
     *
     * @return void
     */
    private function carrySession(): void
    {
        $this->_session = $_SESSION ?? [];
    }
}
