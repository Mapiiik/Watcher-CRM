<?php
declare(strict_types=1);

namespace Access\Controller;

use Access\Authentication\AuthLog;
use Access\Authentication\FormAuthenticator;
use Access\Authentication\SecurityPolicy;
use App\Controller\AppController;
use ArrayAccess;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;
use CakeDC\Users\Controller\Traits\CustomUsersTableTrait;
use CakeDC\Users\Controller\Traits\LinkSocialTrait;
use CakeDC\Users\Controller\Traits\LoginTrait;
use CakeDC\Users\Controller\Traits\OneTimePasswordVerifyTrait;
use CakeDC\Users\Controller\Traits\PasswordManagementTrait;
use CakeDC\Users\Controller\Traits\ProfileTrait;
use CakeDC\Users\Controller\Traits\ReCaptchaTrait;
use CakeDC\Users\Controller\Traits\SimpleCrudTrait;
use CakeDC\Users\UsersPlugin;
use Override;

/**
 * Signing in, the profile and the accounts, shared by the applications.
 *
 * Each application extends this as its `AppUsersController`, which is what the CakeDC/Users
 * configuration and the permissions name. Registration, signing in by a link sent by e-mail and
 * the second factors other than TOTP are left out, so their actions do not exist.
 *
 * @property \Access\Model\Table\UsersTable $Users
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 * @property \Cake\Controller\Component\FormProtectionComponent $FormProtection
 */
class UsersController extends AppController
{
    use CustomUsersTableTrait;
    use LinkSocialTrait;
    use LoginTrait {
        login as protected loginWithForm;
    }
    use OneTimePasswordVerifyTrait;
    use PasswordManagementTrait {
        changePassword as protected changePasswordOfAccount;
        validate as protected validateToken;
    }
    use ProfileTrait;
    use ReCaptchaTrait;
    use SimpleCrudTrait;

    /**
     * Initialize
     *
     * @return void
     */
    #[Override]
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('CakeDC/Users.Setup');

        if ($this->components()->has('FormProtection')) {
            $this->FormProtection->setConfig('unlockedActions', ['login']);
        }

        $this->getEventManager()->on(UsersPlugin::EVENT_AFTER_LOGIN, $this->afterLogin(...));
    }

    /**
     * Signs in with the form, saying why when too many failures refuse it.
     *
     * @return \Cake\Http\Response|null
     */
    public function login()
    {
        Configure::write(
            'Auth.FormLoginFailure.messages.' . FormAuthenticator::FAILURE_BLOCKED,
            __d('access', 'Too many failed sign-ins. Try again in a few minutes.'),
        );

        return $this->loginWithForm();
    }

    /**
     * Logs who signed in, from where and how.
     *
     * @param \Cake\Event\EventInterface<\Cake\Controller\Controller> $event The event.
     * @param \ArrayAccess<string, mixed>|array<string, mixed> $user The account.
     * @return void
     */
    protected function afterLogin(EventInterface $event, mixed $user): void
    {
        $provider = $this->Authentication->getAuthenticationService()->getAuthenticationProvider();

        AuthLog::info('signed in', [
            'user' => is_array($user) || $user instanceof ArrayAccess ? (string)($user['username'] ?? '') : '',
            'ip' => AuthLog::address($this->getRequest()),
            'with' => $provider !== null ? namespaceSplit($provider::class)[1] : null,
        ]);
    }

    /**
     * Changes a password, telling the form how long the new one has to be for the account.
     *
     * @param string|null $id The account, for a superuser changing somebody else's password.
     * @return \Cake\Http\Response|null
     */
    public function changePassword(?string $id = null)
    {
        $response = $this->changePasswordOfAccount($id);

        $user = $this->viewBuilder()->getVar('user');
        $account = $user instanceof EntityInterface && $user->get('id') !== null
            ? $this->getUsersTable()->find()
                ->select(['role', 'is_superuser'])
                ->where([$this->getUsersTable()->aliasField('id') => $user->get('id')])
                ->disableHydration()
                ->first()
            : null;
        $minimumLength = SecurityPolicy::minimumPasswordLength(is_array($account) ? $account : []);
        Configure::write('Users.passwordMeter.pswMinLength', $minimumLength);
        $this->set('passwordMinLength', $minimumLength);

        return $response;
    }

    /**
     * Validates a token sent by e-mail. Only the one for a new password is ever sent, as nobody
     * registers.
     *
     * @param string|null $type What the token is for.
     * @param string|null $token The token.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException For anything but a new password.
     */
    public function validate(?string $type = null, ?string $token = null)
    {
        if ($type !== 'password') {
            throw new NotFoundException();
        }

        return $this->validateToken($type, $token);
    }

    /**
     * Sending the token that confirms a new account again, which nobody registering means nobody
     * needs.
     *
     * @return void
     * @throws \Cake\Http\Exception\NotFoundException Always.
     */
    public function resendTokenValidation()
    {
        throw new NotFoundException();
    }
}
