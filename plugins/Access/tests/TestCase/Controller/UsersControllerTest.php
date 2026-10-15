<?php
declare(strict_types=1);

namespace Access\Test\TestCase\Controller;

use Access\Authentication\LoginThrottle;
use Access\Controller\UsersController;
use Access\Model\Entity\User;
use Access\Test\TestCase\AccountsTrait;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Log\Engine\ArrayLog;
use Cake\Log\Log;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Access\Controller\UsersController Test Case
 */
#[UsesClass(UsersController::class)]
class UsersControllerTest extends TestCase
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
    ];

    /**
     * @inheritDoc
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Cache::clear(LoginThrottle::CACHE);
        Log::drop('auth');
        Log::setConfig('auth', ['className' => ArrayLog::class, 'scopes' => ['auth']]);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function tearDown(): void
    {
        Log::drop('auth');
        Cache::clear(LoginThrottle::CACHE);

        parent::tearDown();
    }

    /**
     * The sign-in page is there for anybody.
     *
     * @return void
     */
    public function testTheSignInPageIsOffered(): void
    {
        $this->get('/login');

        $this->assertResponseOk();
        $this->assertResponseContains('name="username"');
        $this->assertResponseContains('name="password"');
    }

    /**
     * Accounts are made by an administrator, so there is nowhere to register, and no registration
     * to confirm - not even for one.
     *
     * @return void
     */
    public function testNobodyRegisters(): void
    {
        $this->get('/register');
        $this->assertRedirectContains('/login');

        $this->session(['Auth' => $this->addAccount(role: 'admin')]);
        $this->get('/register');
        $this->assertResponseCode(404);
        $this->get('/users/validate/email/any-token');
        $this->assertResponseCode(404);
    }

    /**
     * A password hashed the old way still signs its owner in, and is stored with Argon2id from then
     * on.
     *
     * @return void
     */
    public function testSigningInMovesAnOldPasswordToArgon2id(): void
    {
        $users = $this->getTableLocator()->get('Users');
        $id = $this->addAccount()->get('id');
        $users->updateQuery()
            ->set(['password' => password_hash('Kopr na zahrade 7x', PASSWORD_BCRYPT)])
            ->where(['id' => $id])
            ->execute();

        $this->enableCsrfToken();
        $this->post('/login', ['username' => 'jnovak', 'password' => 'Kopr na zahrade 7x']);

        $this->assertRedirect('/');
        $stored = (string)$users->get($id, finder: 'withSecrets')->get('password');
        $this->assertStringStartsWith('$argon2id$', $stored);
        $this->assertTrue(password_verify('Kopr na zahrade 7x', $stored));
    }

    /**
     * A new password is held to the rules of the account, and the form says what they are.
     *
     * @return void
     */
    public function testANewPasswordTooShortForTheAccountIsRefused(): void
    {
        $this->session(['Auth' => $this->addAccount(superuser: true)]);
        $this->get('/users/change-password');
        $this->assertResponseContains('At least 17 characters');

        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/users/change-password', [
            'password' => 'Kopr na zahrade',
            'password_confirm' => 'Kopr na zahrade',
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('The password has to have at least 17 characters.');
    }

    /**
     * After too many failures an account is refused from that address for a while, even with the
     * right password, and the log tells each failure and the refusal apart.
     *
     * @return void
     */
    public function testTooManyFailuresRefuseEvenTheRightPassword(): void
    {
        $this->addAccount();

        for ($i = 0; $i < 5; $i++) {
            $this->enableCsrfToken();
            $this->post('/login', ['username' => 'jnovak', 'password' => 'Wrong password ' . $i]);
            $this->assertNoRedirect();
        }
        $this->enableCsrfToken();
        $this->post('/login', ['username' => 'jnovak', 'password' => 'Kopr na zahrade 7x']);

        $this->assertNoRedirect();
        $this->assertResponseContains('Too many failed sign-ins.');
        /** @var \Cake\Log\Engine\ArrayLog $log */
        $log = Log::engine('auth');
        $lines = $log->read();
        $this->assertCount(6, $lines);
        $this->assertStringContainsString(
            'sign-in failed user="jnovak" reason="wrong username or password"',
            $lines[0],
        );
        $this->assertStringContainsString('sign-in blocked user="jnovak"', $lines[5]);
        $this->assertStringNotContainsString('Wrong password', implode("\n", $lines));
    }

    /**
     * The session keeps the account signed in without its password or one-time secret.
     *
     * @return void
     */
    public function testTheSessionKeepsNoSecret(): void
    {
        $this->addAccount();

        $this->enableCsrfToken();
        $this->post('/login', ['username' => 'jnovak', 'password' => 'Kopr na zahrade 7x']);

        $this->assertRedirect('/');
        // read from `$_SESSION`, as asking the request's session starts it again and empties it
        $identity = $_SESSION['Auth'] ?? null;
        $this->assertInstanceOf(EntityInterface::class, $identity);
        $this->assertSame('jnovak', $identity->get('username'));
        $this->assertNull($identity->get('password'));
        $this->assertNull($identity->get('secret'));
    }

    /**
     * Editing an account does not change its password, whatever the form sends.
     *
     * @return void
     */
    public function testEditingAnAccountLeavesItsPasswordAlone(): void
    {
        $users = $this->getTableLocator()->get('Users');
        $this->session(['Auth' => $this->addAccount(role: 'admin')]);
        $other = $this->addOtherAccount();
        $before = (string)$users->get($other->get('id'), finder: 'withSecrets')->get('password');

        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/users/edit/' . $other->get('id'), [
            'username' => 'pdvorak',
            'first_name' => 'Petr',
            'password' => 'Celer v zahradce 8y',
        ]);

        $this->assertRedirectContains('/users/index');
        $after = $users->get($other->get('id'), finder: 'withSecrets');
        $this->assertSame('Petr', $after->get('first_name'));
        $this->assertSame($before, $after->get('password'));
    }

    /**
     * A successful sign-in is logged with how it was made.
     *
     * @return void
     */
    public function testSigningInIsLogged(): void
    {
        $this->addAccount();

        $this->enableCsrfToken();
        $this->post('/login', ['username' => 'jnovak', 'password' => 'Kopr na zahrade 7x']);

        $this->assertRedirect('/');
        /** @var \Cake\Log\Engine\ArrayLog $log */
        $log = Log::engine('auth');
        $this->assertSame(['info: signed in user="jnovak" with="FormAuthenticator"'], $log->read());
    }

    /**
     * The account notes when and from where it last signed in, and when and from where somebody
     * last failed to, by its username or its e-mail. A name no account has notes nothing.
     *
     * @return void
     */
    public function testSignInsAndFailuresAreNotedOnTheAccount(): void
    {
        $id = $this->addAccount()->get('id');
        $users = $this->getTableLocator()->get('Users');

        $this->configRequest(['environment' => ['REMOTE_ADDR' => '192.0.2.10']]);
        $this->enableCsrfToken();
        $this->post('/login', ['username' => 'jan.novak@example.com', 'password' => 'Not the password 1']);
        $this->enableCsrfToken();
        $this->post('/login', ['username' => 'nobody', 'password' => 'Not the password 1']);
        $user = $users->get($id);
        $this->assertNotNull($user->get('last_denied'));
        $this->assertSame('192.0.2.10', $user->get('last_denied_ip'));
        $this->assertNull($user->get('last_granted'));

        // configRequest() merges, the address is replaced
        $this->_request = ['environment' => ['REMOTE_ADDR' => '2001:db8::20']];
        $this->enableCsrfToken();
        $this->post('/login', ['username' => 'jnovak', 'password' => 'Kopr na zahrade 7x']);
        $this->assertRedirect('/');
        $user = $users->get($id);
        $this->assertNotNull($user->get('last_granted'));
        $this->assertSame('2001:db8::20', $user->get('last_granted_ip'));
        $this->assertSame('192.0.2.10', $user->get('last_denied_ip'));

        $this->_session = $_SESSION ?? [];
        $this->get('/profile');
        $this->assertResponseOk();
        $this->assertResponseContains('<td>2001:db8::20</td>');
        $this->assertResponseContains('<td>192.0.2.10</td>');
        // the profile shows what the page of the account does
        foreach (['Is Superuser', 'Active', 'Modified', 'Password Sign-In', 'API Key Sign-In'] as $heading) {
            $this->assertResponseContains('<th>' . $heading . '</th>');
        }
    }

    /**
     * Nothing signs in without a password and without the second factor: no cookie remembering
     * somebody, no link sent by e-mail. A session ends after eight hours idle.
     *
     * @return void
     */
    public function testNoWayInSkipsThePasswordAndTheSessionEndsAfterEightHours(): void
    {
        $this->get('/login');

        $this->assertResponseNotContains('remember_me');
        $authenticators = array_keys((array)Configure::read('Auth.Authenticators'));
        $this->assertNotContains('Cookie', $authenticators);
        $this->assertNotContains('OneTimeToken', $authenticators);
        $this->assertSame(480, Configure::read('Session.timeout'));
    }

    /**
     * An account signs in by its username or its e-mail, and only while it is active.
     *
     * @return void
     */
    public function testOnlyAnActiveAccountSignsInByUsernameOrEmail(): void
    {
        $user = $this->addAccount();

        $this->enableCsrfToken();
        $this->post('/login', ['username' => 'jan.novak@example.com', 'password' => 'Kopr na zahrade 7x']);
        $this->assertRedirect('/');

        $this->getTableLocator()->get('Users')->updateQuery()
            ->set(['active' => false])
            ->where(['id' => $user->get('id')])
            ->execute();
        $this->_session = [];
        $this->enableCsrfToken();
        $this->post('/login', ['username' => 'jnovak', 'password' => 'Kopr na zahrade 7x']);
        $this->assertNoRedirect();
        $this->assertSession(null, 'Auth');
    }

    /**
     * Every application reads the same configuration, from this plugin.
     *
     * @return void
     */
    public function testTheConfigurationComesFromThisPlugin(): void
    {
        $this->get('/login');

        $this->assertSame('Users', Configure::read('Users.controller'));
        $this->assertSame('Users', Configure::read('Users.table'));
    }

    /**
     * An admin sees on the page of an account the identities linked to it at other providers.
     *
     * @return void
     */
    public function testTheAccountPageShowsItsLinkedSocialAccounts(): void
    {
        $this->session(['Auth' => $this->addAccount(role: 'admin')]);
        $id = (string)$this->addOtherAccount()->get('id');
        $this->getTableLocator()->get('Access.SocialAccounts')->link($id, 'google', '108234567890', null);

        $this->get('/users/view/' . $id);

        $this->assertResponseOk();
        $this->assertResponseContains('Social Accounts');
        $this->assertResponseContains('<td>108234567890</td>');
    }

    /**
     * The accounts are listed the active ones first, then by username, and a search looks in
     * their names and e-mails.
     *
     * @return void
     */
    public function testTheAccountsAreListedActiveFirstAndSearched(): void
    {
        $this->session(['Auth' => $this->addAccount(role: 'admin')]);
        $other = $this->addOtherAccount('petr.dvorak@example.com');
        $users = $this->getTableLocator()->get('Users');
        $inactive = $users->newEmptyEntity();
        $inactive->patch([
            'username' => 'aformer',
            'password' => 'Salat na zahrade 5w',
            'role' => 'user',
            'active' => false,
        ], ['guard' => false]);
        $users->saveOrFail($inactive);

        $this->get('/users');
        $this->assertResponseOk();
        $listed = array_map(
            fn(EntityInterface $user): string => (string)$user->get('username'),
            iterator_to_array($this->viewVariable('users')),
        );
        $this->assertSame(['jnovak', 'pdvorak', 'aformer'], $listed);

        $this->get('/users?search=DVORAK@');
        $this->assertResponseOk();
        $this->assertSame(
            [$other->get('id')],
            array_map(
                fn(EntityInterface $user): string => (string)$user->get('id'),
                iterator_to_array($this->viewVariable('users')),
            ),
        );
        $this->assertResponseContains('type="search"');
    }

    /**
     * Somebody not signed in is sent to sign in, and from there on to the page they wanted - but
     * never to another site.
     *
     * @return void
     */
    public function testSigningInLeadsOnToThePageAskedFor(): void
    {
        $this->addAccount(role: 'admin');

        $this->get('/users');
        $this->assertRedirectContains('/login?redirect=%2Fusers');

        $this->enableCsrfToken();
        $this->post('/login?redirect=%2Fusers', ['username' => 'jnovak', 'password' => 'Kopr na zahrade 7x']);
        $this->assertRedirect('/users');

        $this->_session = [];
        $this->enableCsrfToken();
        $this->post('/login?redirect=%2F%2Fexample.com', ['username' => 'jnovak', 'password' => 'Kopr na zahrade 7x']);
        $this->assertRedirect('/');
    }

    /**
     * Somebody changes their own password with the current one as proof, and the change is logged.
     *
     * @return void
     */
    public function testOwnPasswordIsChangedWithTheCurrentOne(): void
    {
        $id = (string)$this->addAccount()->get('id');
        $this->session(['Auth' => $this->getTableLocator()->get('Users')->get($id)]);

        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/users/change-password', [
            'current_password' => 'Not the password 1',
            'password' => 'Celer v zahradce 8y',
            'password_confirm' => 'Celer v zahradce 8y',
        ]);
        $this->assertResponseOk();
        $this->assertResponseContains('This is not the current password.');

        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/users/change-password', [
            'current_password' => 'Kopr na zahrade 7x',
            'password' => 'Celer v zahradce 8y',
            'password_confirm' => 'Celer v zahradce 8y',
        ]);
        $this->assertRedirect('/profile');
        $stored = (string)$this->getTableLocator()->get('Users')->get($id, finder: 'withSecrets')->get('password');
        $this->assertTrue(password_verify('Celer v zahradce 8y', $stored));
        /** @var \Cake\Log\Engine\ArrayLog $log */
        $log = Log::engine('auth');
        $this->assertStringContainsString('password changed user="jnovak"', implode("\n", $log->read()));
    }

    /**
     * The form for a new password starts empty, never with the hash the account has, whether
     * somebody changes their own or a superuser that of somebody else.
     *
     * @return void
     */
    public function testThePasswordFormDoesNotCarryTheHash(): void
    {
        $users = $this->getTableLocator()->get('Users');
        $id = (string)$this->addAccount(superuser: true)->get('id');
        $other = $this->addOtherAccount();
        $this->session(['Auth' => $users->get($id)]);

        $this->get('/users/change-password');
        $this->assertResponseOk();
        $this->assertResponseNotContains('$argon2id$');

        $this->get('/users/change-password/' . $other->get('id'));
        $this->assertResponseOk();
        $this->assertResponseNotContains('$argon2id$');
    }

    /**
     * Only a superuser changes the password of somebody else.
     *
     * @return void
     */
    public function testOnlyASuperuserChangesThePasswordOfSomebodyElse(): void
    {
        $id = (string)$this->addAccount()->get('id');
        $users = $this->getTableLocator()->get('Users');
        $other = $this->addOtherAccount();
        $this->session(['Auth' => $users->get($id)]);

        $this->get('/users/change-password/' . $other->get('id'));

        $this->assertRedirect('/profile');
    }

    /**
     * A superuser turns off signing in to an account with a password: the password no longer
     * signs it in, no link to set one is sent, and the list and the page of the account say so.
     * Setting a password turns it on again.
     *
     * @return void
     */
    public function testASuperuserTurnsOffSigningInWithAPassword(): void
    {
        $users = $this->getTableLocator()->get('Users');
        $this->session(['Auth' => $this->addAccount(role: 'admin', superuser: true)]);
        $other = (string)$this->addOtherAccount('petr.dvorak@example.com')->get('id');

        $this->get('/users/view/' . $other);
        $this->assertResponseContains('Turn Off Password Sign-In');
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/users/turn-off-password-sign-in/' . $other);

        $this->assertRedirectContains('/users/view/' . $other);
        $this->assertNull($users->get($other, finder: 'withSecrets')->get('password'));
        $this->assertFalse($users->get($other)->hasPasswordSignIn());
        $this->assertNull($users->newPasswordToken('pdvorak'));
        $this->get('/users');
        $this->assertResponseOk();
        $this->assertSame(
            [true, false],
            array_map(
                fn(User $user): bool => $user->hasPasswordSignIn(),
                iterator_to_array($this->viewVariable('users')),
            ),
        );

        $this->_session = [];
        $this->enableCsrfToken();
        $this->post('/login', ['username' => 'pdvorak', 'password' => 'Mrkev na zahrade 9z']);
        $this->assertNoRedirect();
        $this->assertSession(null, 'Auth');

        $this->session(['Auth' => $users->get($this->firstAccountId())]);
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/users/change-password/' . $other, [
            'password' => 'Celer v zahradce 8y',
            'password_confirm' => 'Celer v zahradce 8y',
        ]);
        $this->assertTrue($users->get($other)->hasPasswordSignIn());
    }

    /**
     * Only a superuser turns signing in with a password off, and not for their own account.
     *
     * @return void
     */
    public function testSigningInWithAPasswordIsTurnedOffOnlyByASuperuserForSomebodyElse(): void
    {
        $users = $this->getTableLocator()->get('Users');
        $admin = $this->addAccount(role: 'admin');
        $other = (string)$this->addOtherAccount()->get('id');

        $this->session(['Auth' => $admin]);
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/users/turn-off-password-sign-in/' . $other);
        $this->assertTrue($users->get($other)->hasPasswordSignIn());

        $users->updateAll(['is_superuser' => true], ['id' => $admin->get('id')]);
        $this->session(['Auth' => $users->get($admin->get('id'))]);
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/users/turn-off-password-sign-in/' . $admin->get('id'));
        $this->assertTrue($users->get($admin->get('id'))->hasPasswordSignIn());
    }

    /**
     * The account `addAccount()` made, by its username.
     *
     * @return string
     */
    private function firstAccountId(): string
    {
        return (string)$this->getTableLocator()->get('Users')->find()->where(['username' => 'jnovak'])->firstOrFail()->get('id');
    }
}
