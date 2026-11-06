<?php
declare(strict_types=1);

namespace Access\Test\TestCase\Controller;

use Access\Authentication\LoginThrottle;
use Access\Authentication\SessionAuthenticator;
use Access\Identifier\LinkedSocialIdentifier;
use Access\Test\TestCase\AccountsTrait;
use Cake\Cache\Cache;
use Cake\Chronos\Chronos;
use Cake\Datasource\EntityInterface;
use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * A session lasts only as long as the account behind it allows, whatever it remembers from
 * signing in.
 */
#[CoversClass(SessionAuthenticator::class)]
#[UsesClass(LoginThrottle::class)]
class SessionsTest extends TestCase
{
    use AccountsTrait;
    use IntegrationTestTrait;

    private const NEW_PASSWORD = 'Celer v zahradce 8y';

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
        Chronos::setTestNow('2026-11-06 10:00:00');
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function tearDown(): void
    {
        Chronos::setTestNow(null);
        Cache::clear(LoginThrottle::CACHE);

        parent::tearDown();
    }

    /**
     * An account switched off stops working on its very next request, with the window still open.
     *
     * @return void
     */
    public function testAnAccountSwitchedOffIsSignedOutOnItsNextRequest(): void
    {
        $account = $this->signIn($this->addAccount());
        $this->get('/profile');
        $this->assertResponseOk();

        $this->getTableLocator()->get('Users')->updateAll(['active' => false], ['id' => $account->get('id')]);

        $this->get('/profile');
        $this->assertRedirectContains('/login');
        $this->assertArrayNotHasKey('Auth', $_SESSION);
        $this->assertArrayNotHasKey(SessionAuthenticator::SIGNED_IN_AT, $_SESSION);
    }

    /**
     * A deleted account has nothing left to be signed in to.
     *
     * @return void
     */
    public function testADeletedAccountIsSignedOutOnItsNextRequest(): void
    {
        $account = $this->signIn($this->addAccount());

        $this->getTableLocator()->get('Users')->deleteAll(['id' => $account->get('id')]);

        $this->get('/profile');
        $this->assertRedirectContains('/login');
    }

    /**
     * What an account may do comes from the account, not from what the session remembers.
     *
     * @return void
     */
    public function testARoleTakenAwayHoldsAtOnce(): void
    {
        $account = $this->signIn($this->addAccount(role: 'admin'));
        $this->get('/users');
        $this->assertResponseOk();

        $this->getTableLocator()->get('Users')->updateAll(['role' => 'user'], ['id' => $account->get('id')]);

        $this->get('/users');
        $this->assertRedirect();
    }

    /**
     * Being a superuser is taken away the same way.
     *
     * @return void
     */
    public function testASuperuserTakenBackHoldsAtOnce(): void
    {
        $account = $this->signIn($this->addAccount(superuser: true));
        $this->get('/users');
        $this->assertResponseOk();

        $this->getTableLocator()->get('Users')->updateAll(['is_superuser' => false], ['id' => $account->get('id')]);

        $this->get('/users');
        $this->assertRedirect();
    }

    /**
     * Rights given while signed in need no new sign-in either.
     *
     * @return void
     */
    public function testARoleGivenHoldsAtOnce(): void
    {
        $account = $this->signIn($this->addAccount());
        $this->getTableLocator()->get('Users')->updateAll(['role' => 'admin'], ['id' => $account->get('id')]);

        $this->get('/users');

        $this->assertResponseOk();
    }

    /**
     * What the session remembers from signing in beyond that stays, the token for signing out of
     * the identity provider among it.
     *
     * @return void
     */
    public function testTheRestOfWhatTheSessionRemembersStays(): void
    {
        $account = $this->addAccount();
        $account->set(LinkedSocialIdentifier::ID_TOKEN, 'id-token-of-the-provider');
        $this->signIn($account);

        $this->get('/profile');

        $this->assertResponseOk();
        $identity = $_SESSION['Auth'] ?? null;
        $this->assertInstanceOf(EntityInterface::class, $identity);
        $this->assertSame('id-token-of-the-provider', $identity->get(LinkedSocialIdentifier::ID_TOKEN));
    }

    /**
     * Signing in notes the moment, which is what a later change of the password is compared with.
     *
     * @return void
     */
    public function testSigningInNotesWhen(): void
    {
        $this->addAccount();

        $this->enableCsrfToken();
        $this->post('/login', ['username' => 'jnovak', 'password' => 'Kopr na zahrade 7x']);

        $this->assertRedirect('/');
        $this->assertSame(Chronos::now()->getTimestamp(), $_SESSION[SessionAuthenticator::SIGNED_IN_AT] ?? null);
    }

    /**
     * Changing your own password throws out whoever is signed in with the old one, you stay.
     *
     * @return void
     */
    public function testChangingYourOwnPasswordEndsYourOtherSessions(): void
    {
        $this->signIn($this->addAccount());
        Chronos::setTestNow('2026-11-06 11:00:00');

        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/users/change-password', [
            'current_password' => 'Kopr na zahrade 7x',
            'password' => self::NEW_PASSWORD,
            'password_confirm' => self::NEW_PASSWORD,
        ]);
        $this->assertRedirect('/profile');
        $thisOne = $_SESSION[SessionAuthenticator::SIGNED_IN_AT] ?? null;
        $this->assertSame(Chronos::now()->getTimestamp(), $thisOne);

        // the test session still says the old moment, as another window of the account would
        $this->get('/profile');
        $this->assertRedirectContains('/login');

        $this->session([SessionAuthenticator::SIGNED_IN_AT => $thisOne]);
        $this->get('/profile');
        $this->assertResponseOk();
    }

    /**
     * A superuser changing the password of somebody else throws out all of their sessions, and
     * the superuser stays signed in.
     *
     * @return void
     */
    public function testASuperuserChangingAPasswordEndsAllSessionsOfTheAccount(): void
    {
        $this->signIn($this->addAccount(superuser: true));
        $other = $this->addOtherAccount();
        Chronos::setTestNow('2026-11-06 11:00:00');

        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/users/change-password/' . $other->get('id'), [
            'password' => self::NEW_PASSWORD,
            'password_confirm' => self::NEW_PASSWORD,
        ]);
        $this->assertRedirectContains('/users/view/' . $other->get('id'));

        $this->get('/profile');
        $this->assertResponseOk();

        $this->signIn($other, Chronos::now()->subSeconds(1)->getTimestamp());
        $this->get('/profile');
        $this->assertRedirectContains('/login');
    }

    /**
     * A link for a new password is what somebody uses when the account may be in the wrong hands,
     * so nothing signed in before it stays, the browser it was opened in included.
     *
     * @return void
     */
    public function testANewPasswordFromALinkEndsAllSessionsOfTheAccount(): void
    {
        $account = $this->signIn($this->addAccount());
        $users = $this->getTableLocator()->get('Users');
        $users->updateAll(
            ['token' => hash('sha256', 'the-link'), 'token_expires' => DateTime::now()->addHours(2)],
            ['id' => $account->get('id')],
        );
        Chronos::setTestNow('2026-11-06 11:00:00');

        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/users/reset-password/the-link', [
            'password' => self::NEW_PASSWORD,
            'password_confirm' => self::NEW_PASSWORD,
        ]);
        $this->assertRedirectContains('/login');

        $this->get('/profile');
        $this->assertRedirectContains('/login');
    }

    /**
     * A session started before sessions noted when, on an account nobody ended sessions of, goes on.
     * Once they are ended, it ends with them.
     *
     * @return void
     */
    public function testASessionThatNotedNothingEndsOnlyWhenSessionsEnd(): void
    {
        $account = $this->addAccount();
        $this->session(['Auth' => $account]);
        $this->get('/profile');
        $this->assertResponseOk();

        $this->getTableLocator()->get('Users')->updateAll(
            ['sessions_valid_from' => DateTime::now()],
            ['id' => $account->get('id')],
        );

        $this->get('/profile');
        $this->assertRedirectContains('/login');
    }

    /**
     * Signing out leaves nothing of the session behind.
     *
     * @return void
     */
    public function testSigningOutForgetsTheMoment(): void
    {
        $this->signIn($this->addAccount());

        $this->get('/logout');

        $this->assertArrayNotHasKey(SessionAuthenticator::SIGNED_IN_AT, $_SESSION);
    }

    /**
     * Puts an account into the session of the next requests.
     *
     * @param \Cake\Datasource\EntityInterface $account The account.
     * @param int|null $signedInAt When it signed in, now by default.
     * @return \Cake\Datasource\EntityInterface
     */
    private function signIn(EntityInterface $account, ?int $signedInAt = null): EntityInterface
    {
        $this->session([
            'Auth' => $account,
            SessionAuthenticator::SIGNED_IN_AT => $signedInAt ?? Chronos::now()->getTimestamp(),
        ]);

        return $account;
    }

    /**
     * The name of an account spelled with a NUL byte must not reach the account: the database
     * driver cuts the text there, while the failures are counted under the whole of it.
     *
     * @return void
     */
    public function testANameNobodyCouldHaveTypedSignsNobodyIn(): void
    {
        $this->addAccount();

        $this->enableCsrfToken();
        $this->post('/login', ['username' => "jnovak\0x", 'password' => 'Kopr na zahrade 7x']);

        $this->assertArrayNotHasKey('Auth', $_SESSION);
    }
}
