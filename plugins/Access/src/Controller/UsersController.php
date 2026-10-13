<?php
declare(strict_types=1);

namespace Access\Controller;

use Access\Authentication\Argon2idPasswordHasher;
use Access\Authentication\AuthLog;
use Access\Authentication\FormAuthenticator;
use Access\Authentication\SecurityPolicy;
use Access\Controller\Trait\ApiTokenTrait;
use Access\Controller\Trait\OneTimePasswordTrait;
use Access\Controller\Trait\PasswordResetTrait;
use Access\Controller\Trait\SocialAccountsTrait;
use Access\Model\Entity\User;
use Access\Model\Table\UsersTable;
use App\Controller\AppController;
use Authentication\Authenticator\AuthenticatorInterface;
use Authentication\Authenticator\SessionAuthenticator;
use Authentication\Identifier\IdentifierCollection;
use Authentication\Identifier\PasswordIdentifier;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\I18n\DateTime;
use CakeDC\Auth\Authentication\AuthenticationService;
use CakeDC\Auth\Traits\IsAuthorizedTrait;
use Override;

/**
 * Signing in, the profile and the accounts, shared by the applications.
 *
 * Each application extends this as its `AppUsersController`, which is what the configuration and
 * the permissions name.
 *
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FormProtectionComponent $FormProtection
 */
class UsersController extends AppController
{
    use ApiTokenTrait;
    use IsAuthorizedTrait;
    use OneTimePasswordTrait;
    use PasswordResetTrait;
    use SocialAccountsTrait;

    /**
     * Initialize
     *
     * @return void
     */
    #[Override]
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Authentication.Authentication', (array)Configure::read('Auth.AuthenticationComponent'));
        $this->loadComponent('Authorization.Authorization', (array)Configure::read('Auth.AuthorizationComponent'));

        if ($this->components()->has('FormProtection')) {
            $this->FormProtection->setConfig('unlockedActions', ['login']);
        }
    }

    /**
     * The pages are this plugin's. An application that has one of them otherwise, or an action of
     * its own, puts the template in `templates/plugin/Access/Users/`.
     *
     * @param \Cake\Event\EventInterface<\Cake\Controller\Controller> $event The event.
     * @return void
     */
    #[Override]
    public function beforeRender(EventInterface $event): void
    {
        parent::beforeRender($event);

        $this->viewBuilder()->setPlugin('Access')->setTemplatePath('Users');
    }

    /**
     * The accounts, as the application extends them.
     *
     * @return \Access\Model\Table\UsersTable
     */
    public function getUsersTable(): UsersTable
    {
        /** @var \Access\Model\Table\UsersTable */
        return $this->fetchTable((string)Configure::read('Users.table'));
    }

    /**
     * Signs in with the form, saying why it was refused when it was.
     *
     * @return \Cake\Http\Response|null
     */
    public function login(): ?Response
    {
        $this->getRequest()->getSession()->delete(AuthenticationService::TWO_FACTOR_VERIFY_SESSION_KEY);

        if ($this->Authentication->getResult()?->isValid()) {
            return $this->signedIn();
        }

        if ($this->getRequest()->is('post')) {
            $this->Flash->error(
                $this->failureOf(FormAuthenticator::class) === FormAuthenticator::FAILURE_BLOCKED
                    ? __d('access', 'Too many failed sign-ins. Try again in a few minutes.')
                    : __d('access', 'The username or the password is not right.'),
                ['key' => 'auth'],
            );
        }

        return null;
    }

    /**
     * Signs in with an identity at another provider, which somebody has to have linked to an
     * account here first.
     *
     * @return \Cake\Http\Response|null
     */
    public function socialLogin(): ?Response
    {
        if ($this->Authentication->getResult()?->isValid()) {
            return $this->signedIn();
        }

        $this->Flash->error(
            __d(
                'access',
                'No account here is linked to that identity. '
                . 'Sign in with your password and link it in your profile.',
            ),
            ['key' => 'auth'],
        );

        return $this->redirect($this->loginAction());
    }

    /**
     * Signs out.
     *
     * @return \Cake\Http\Response|null
     */
    public function logout(): ?Response
    {
        $user = $this->Authentication->getIdentity()?->getOriginalData();
        if ($user instanceof User) {
            AuthLog::info('signed out', [
                'user' => (string)$user->username,
                'ip' => AuthLog::address($this->getRequest()),
            ]);
        }

        $this->getRequest()->getSession()->destroy();
        $this->Flash->success(__d('access', 'You have signed out.'));

        return $this->redirect($this->Authentication->logout() ?? $this->loginAction());
    }

    /**
     * The profile of the account signed in, with how many of its recovery codes are left.
     *
     * @return \Cake\Http\Response|null
     */
    public function profile(): ?Response
    {
        $user = $this->getUsersTable()->get(
            $this->signedInAccount()->get('id'),
            contain: Configure::read('Users.Social.login') ? ['SocialAccounts'] : [],
        );

        $this->set([
            'user' => $user,
            'recoveryCodesLeft' => $this->recoveryCodesTable()->left((string)$user->id),
        ]);

        return null;
    }

    /**
     * Changes the password of the account signed in, with the current one as proof - or, for a
     * superuser, the password of somebody else.
     *
     * @param string|null $id The account, for a superuser changing somebody else's password.
     * @return \Cake\Http\Response|null
     */
    public function changePassword(?string $id = null): ?Response
    {
        $signedIn = $this->signedInAccount();
        $own = $id === null || $id === $signedIn->get('id');
        if (!$own && !$signedIn->get('is_superuser')) {
            $this->Flash->error(__d('access', 'Only a superuser changes the password of somebody else.'));

            return $this->redirect(['action' => 'profile']);
        }

        $users = $this->getUsersTable();
        $user = $own ? $signedIn : $users->get($id);
        if ($this->getRequest()->is(['post', 'put'])) {
            $data = array_intersect_key(
                (array)$this->getRequest()->getData(),
                array_flip(['password', 'password_confirm', 'current_password']),
            );
            // checked before the new password takes the place of the old one
            $current = (string)($data['current_password'] ?? '');
            $proven = !$own || $this->signedInAccount(secrets: true)->checkPassword($current);
            $user = $users->patchEntity($user, $data, [
                'validate' => $own ? 'passwordChange' : 'passwordConfirm',
                'accessibleFields' => ['password' => true, 'password_confirm' => true, 'current_password' => true],
            ]);

            if (!$proven && $current !== '') {
                $user->setError('current_password', __d('access', 'This is not the current password.'));
            }
            if ($own && $current !== '' && $current === ($data['password'] ?? null)) {
                $user->setError('password', __d('access', 'The new password has to differ from the current one.'));
            }

            if (!$user->hasErrors() && $users->save($user)) {
                AuthLog::notice('password changed', [
                    'user' => (string)$user->get('username'),
                    'by' => $own ? null : (string)$signedIn->get('username'),
                    'ip' => AuthLog::address($this->getRequest()),
                ]);
                $this->Flash->success(__d('access', 'The password has been changed.'));

                return $this->redirect($own ? ['action' => 'profile'] : ['action' => 'view', $user->get('id')]);
            }
            $this->Flash->error(__d('access', 'The password could not be changed.'));
        }

        $this->set([
            'user' => $user,
            'validatePassword' => $own,
            'passwordMinLength' => SecurityPolicy::minimumPasswordLength($user->toArray()),
        ]);

        return null;
    }

    /**
     * The accounts.
     *
     * @return \Cake\Http\Response|null
     */
    public function index(): ?Response
    {
        $this->set('users', $this->paginate($this->getUsersTable()));

        return null;
    }

    /**
     * An account.
     *
     * @param string|null $id The account.
     * @return \Cake\Http\Response|null
     */
    public function view(?string $id = null): ?Response
    {
        $this->set('user', $this->getUsersTable()->get($id));

        return null;
    }

    /**
     * Adds an account.
     *
     * @return \Cake\Http\Response|null
     */
    public function add(): ?Response
    {
        $users = $this->getUsersTable();
        $user = $users->newEmptyEntity();
        if ($this->getRequest()->is('post')) {
            $user = $users->patchEntity($user, (array)$this->getRequest()->getData(), [
                'accessibleFields' => ['password' => true],
            ]);
            if ($users->save($user)) {
                $this->Flash->success(__d('access', 'The account has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__d('access', 'The account could not be saved.'));
        }
        $this->set('user', $user);

        return null;
    }

    /**
     * Edits an account.
     *
     * @param string|null $id The account.
     * @return \Cake\Http\Response|null
     */
    public function edit(?string $id = null): ?Response
    {
        $users = $this->getUsersTable();
        $user = $users->get($id);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $user = $users->patchEntity($user, (array)$this->getRequest()->getData());
            if ($users->save($user)) {
                $this->Flash->success(__d('access', 'The account has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__d('access', 'The account could not be saved.'));
        }
        $this->set('user', $user);

        return null;
    }

    /**
     * Deletes an account.
     *
     * @param string|null $id The account.
     * @return \Cake\Http\Response|null
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $users = $this->getUsersTable();
        if ($users->delete($users->get($id))) {
            $this->Flash->success(__d('access', 'The account has been deleted.'));
        } else {
            $this->Flash->error(__d('access', 'The account could not be deleted.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Finishes signing in: stores a password hashed the old way again, notes the time and sends
     * the account where it was going.
     *
     * @return \Cake\Http\Response|null
     */
    protected function signedIn(): ?Response
    {
        $provider = $this->Authentication->getAuthenticationService()->getAuthenticationProvider();
        $identity = $this->Authentication->getIdentity();
        $user = $identity?->getOriginalData();
        // somebody signed in already only comes back here
        if ($provider === null || $provider instanceof SessionAuthenticator || !$user instanceof User) {
            return $this->redirect($this->afterSignIn());
        }

        $this->rehashPassword($provider, $user);
        $user->set('last_login', DateTime::now());
        $this->getUsersTable()->updateQuery()
            ->set(['last_login' => $user->get('last_login')])
            ->where(['id' => $user->get('id')])
            ->execute();

        AuthLog::info('signed in', [
            'user' => (string)$user->get('username'),
            'ip' => AuthLog::address($this->getRequest()),
            'with' => namespaceSplit($provider::class)[1],
        ]);

        // somebody who has just set their app up is shown the recovery codes made for them
        if ($this->getRequest()->getSession()->check(self::recoveryCodesKey())) {
            return $this->redirect(['action' => 'recoveryCodes']);
        }

        return $this->redirect($this->afterSignIn());
    }

    /**
     * Stores a password hashed the old way as Argon2id, now that it is known.
     *
     * @param \Authentication\Authenticator\AuthenticatorInterface $provider What signed the account in.
     * @param \Access\Model\Entity\User $user The account.
     * @return void
     */
    private function rehashPassword(AuthenticatorInterface $provider, User $user): void
    {
        $password = $this->getRequest()->getData('password');
        $identifiers = $provider instanceof FormAuthenticator ? $provider->getIdentifier() : null;
        $checker = $identifiers instanceof IdentifierCollection && $identifiers->has('Password')
            ? $identifiers->get('Password')
            : null;
        if (!is_string($password) || !$checker instanceof PasswordIdentifier || !$checker->needsPasswordRehash()) {
            return;
        }

        $user->set('password', (new Argon2idPasswordHasher())->hash($password), ['setter' => false]);
        $this->getUsersTable()->updateQuery()
            ->set(['password' => $user->get('password')])
            ->where(['id' => $user->get('id')])
            ->execute();
    }

    /**
     * Where to go once signed in: the page the account was on its way to, where it is a page of
     * this application the account may open, or the root.
     *
     * @return array<string, mixed>|string
     */
    private function afterSignIn(): array|string
    {
        $target = $this->getRequest()->getQuery('redirect');
        if (
            is_string($target)
            && str_starts_with($target, '/')
            && !str_starts_with($target, '//')
            && !str_contains($target, '\\')
            && $this->isAuthorized($target)
        ) {
            return $target;
        }

        return $this->Authentication->getConfig('loginRedirect') ?? '/';
    }

    /**
     * Why an authenticator refused the request, if it did.
     *
     * @param class-string<\Authentication\Authenticator\AuthenticatorInterface> $class The authenticator.
     * @return string|null
     */
    private function failureOf(string $class): ?string
    {
        $service = $this->Authentication->getAuthenticationService();
        if (!$service instanceof AuthenticationService) {
            return null;
        }
        foreach ($service->getFailures() as $failure) {
            if ($failure->getAuthenticator() instanceof $class) {
                return $failure->getResult()->getStatus();
            }
        }

        return null;
    }
}
