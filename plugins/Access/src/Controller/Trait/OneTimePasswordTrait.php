<?php
declare(strict_types=1);

namespace Access\Controller\Trait;

use Access\Authentication\AuthLog;
use Access\Authentication\LoginThrottle;
use Access\Authentication\OneTimePassword;
use Access\Authentication\SecurityPolicy;
use Access\Model\Entity\User;
use Access\Model\Table\RecoveryCodesTable;
use ArrayAccess;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Http\Response;
use CakeDC\Auth\Authentication\AuthenticationService;
use CakeDC\Auth\Authenticator\TwoFactorAuthenticator;

/**
 * Signing in with a one-time code from an authenticator app, and looking after it in the profile.
 *
 * A code is accepted once: the time step it belongs to has to be later than that of the last code
 * used. A recovery code stands in for a one-time code when the phone is lost. Ten of them are made
 * when the app is set up, shown once, and each signs in once.
 */
trait OneTimePasswordTrait
{
    /**
     * Asks for the one-time code after the password, and sets the app up first where the account
     * has to use one and has not yet.
     *
     * @return \Cake\Http\Response|null
     */
    public function verify(): ?Response
    {
        $loginAction = $this->loginAction();
        $session = $this->getRequest()->getSession();
        $pending = $session->read(AuthenticationService::TWO_FACTOR_VERIFY_SESSION_KEY);
        if (
            !Configure::read('OneTimePasswordAuthenticator.login')
            || !(is_array($pending) || $pending instanceof ArrayAccess)
            || empty($pending['id'])
        ) {
            return $this->redirect($loginAction);
        }

        $user = $this->getUsersTable()->get($pending['id'], finder: 'withSecrets');
        if (!$user->get('secret_verified')) {
            $this->showSetUp($user);
        }
        if (!$this->getRequest()->is('post')) {
            return null;
        }

        $username = (string)$user->get('username');
        $address = AuthLog::address($this->getRequest());
        $throttle = LoginThrottle::fromSettings();
        $blocked = $throttle->blocked($username, $address);
        $how = $blocked ? null : $this->acceptCode($user, (string)$this->getRequest()->getData('code'));
        if ($how === null) {
            if (!$blocked) {
                $throttle->failed($username, $address);
            }
            AuthLog::warning($blocked ? 'one-time code blocked' : 'one-time code refused', [
                'user' => $username,
                'ip' => $address,
            ]);
            $session->destroy();
            $this->Flash->error(
                $blocked
                    ? __d('access', 'Too many failed sign-ins. Try again in a few minutes.')
                    : __d('access', 'The code is not right. Please sign in again.'),
                ['key' => 'auth'],
            );

            return $this->redirect($loginAction);
        }

        if ($how === 'recovery code') {
            AuthLog::notice('recovery code used', [
                'user' => $username,
                'ip' => $address,
                'left' => $this->recoveryCodesTable()->left((string)$user->get('id')),
            ]);
        }
        if (!$user->get('secret_verified')) {
            $this->confirmSetUp($user);
            $pending['secret_verified'] = true;
        }

        unset($pending['secret']);
        $session->delete(AuthenticationService::TWO_FACTOR_VERIFY_SESSION_KEY);
        $session->write(TwoFactorAuthenticator::USER_SESSION_KEY, $pending);

        return $this->redirect($loginAction);
    }

    /**
     * Sets the app up from the profile, for somebody who wants to use it without having to.
     *
     * @return \Cake\Http\Response|null
     */
    public function oneTimePassword(): ?Response
    {
        $user = $this->signedInAccount(secrets: true);
        if ($user->get('secret_verified')) {
            return $this->redirect(['action' => 'profile']);
        }

        if ($this->getRequest()->is('post')) {
            if ($this->acceptCode($user, (string)$this->getRequest()->getData('code')) === 'one-time code') {
                $this->confirmSetUp($user);

                return $this->redirect(['action' => 'recoveryCodes']);
            }
            $this->Flash->error(__d('access', 'The code is not right. Please try again.'));
        }

        $this->showSetUp($user);

        return null;
    }

    /**
     * Turns the one-time codes off, for somebody who is not required to use them, with a current
     * code as proof that it is them.
     *
     * @return \Cake\Http\Response|null
     */
    public function disableOneTimePassword(): ?Response
    {
        $this->getRequest()->allowMethod('post');
        $user = $this->signedInAccount(secrets: true);

        if (SecurityPolicy::requiresOneTimePassword($user->toArray())) {
            $this->Flash->error(__d('access', 'Your account has to use two-step verification.'));
        } elseif ($this->acceptCode($user, (string)$this->getRequest()->getData('code')) !== 'one-time code') {
            $this->Flash->error(__d('access', 'The code is not right. Please try again.'));
        } else {
            $this->forgetOneTimePassword($user);
            AuthLog::notice('one-time password turned off', [
                'user' => (string)$user->get('username'),
                'ip' => AuthLog::address($this->getRequest()),
            ]);
            $this->Flash->success(__d('access', 'Two-step verification is turned off.'));
        }

        return $this->redirect(['action' => 'profile']);
    }

    /**
     * Makes new recovery codes in place of the old ones, with a current code as proof that it is
     * the owner asking.
     *
     * @return \Cake\Http\Response|null
     */
    public function newRecoveryCodes(): ?Response
    {
        $this->getRequest()->allowMethod('post');
        $user = $this->signedInAccount(secrets: true);

        if (
            !$user->get('secret_verified')
            || $this->acceptCode($user, (string)$this->getRequest()->getData('code')) !== 'one-time code'
        ) {
            $this->Flash->error(__d('access', 'The code is not right. Please try again.'));

            return $this->redirect(['action' => 'profile']);
        }

        $this->getRequest()->getSession()->write(
            self::recoveryCodesKey(),
            $this->recoveryCodesTable()->renew((string)$user->get('id')),
        );
        AuthLog::notice('recovery codes renewed', [
            'user' => (string)$user->get('username'),
            'ip' => AuthLog::address($this->getRequest()),
        ]);

        return $this->redirect(['action' => 'recoveryCodes']);
    }

    /**
     * Shows new recovery codes, once.
     *
     * @return \Cake\Http\Response|null
     */
    public function recoveryCodes(): ?Response
    {
        $codes = $this->getRequest()->getSession()->consume(self::recoveryCodesKey());
        if (!is_array($codes) || $codes === []) {
            return $this->redirect(['action' => 'profile']);
        }

        $this->set('codes', $codes);

        return null;
    }

    /**
     * Takes somebody's one-time codes away, for them to set the app up again on signing in, as
     * when they have lost their phone and their recovery codes.
     *
     * @param string|null $id The account.
     * @return \Cake\Http\Response|null
     */
    public function resetOneTimePasswordAuthenticator(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod('post');
        $user = $this->getUsersTable()->get($id);

        $this->forgetOneTimePassword($user);
        AuthLog::notice('one-time password reset', [
            'user' => (string)$user->get('username'),
            'by' => (string)($this->getRequest()->getAttribute('identity')['username'] ?? ''),
            'ip' => AuthLog::address($this->getRequest()),
        ]);
        $this->Flash->success(__d('access', 'The two-step verification of {0} is reset.', $user->get('username')));

        return $this->redirect($this->getRequest()->referer() ?? ['action' => 'view', $id]);
    }

    /**
     * Whether a code typed in is a one-time code or a recovery code of the account, and which.
     *
     * A one-time code is accepted once, so a code seen over somebody's shoulder or kept by a
     * browser is no use afterwards.
     *
     * @param \Cake\Datasource\EntityInterface $user The account.
     * @param string $code The code as typed.
     * @return string|null `one-time code`, `recovery code`, or null for neither.
     */
    protected function acceptCode(EntityInterface $user, string $code): ?string
    {
        $digits = (string)preg_replace('/\s+/', '', $code);
        $secret = (string)$user->get('secret');
        if (preg_match('/^\d{6}$/', $digits) === 1 && $secret !== '') {
            $timeslice = OneTimePassword::verify($secret, $digits);
            if ($timeslice === null) {
                return null;
            }

            $accepted = $this->getUsersTable()->updateQuery()
                ->set(['otp_last_timestep' => $timeslice])
                ->where([
                    'id' => $user->get('id'),
                    'OR' => ['otp_last_timestep IS' => null, 'otp_last_timestep <' => $timeslice],
                ])
                ->execute()
                ->rowCount() === 1;

            return $accepted ? 'one-time code' : null;
        }

        if ($user->get('secret_verified') && $this->recoveryCodesTable()->useUp((string)$user->get('id'), $code)) {
            return 'recovery code';
        }

        return null;
    }

    /**
     * Shows the QR code and the key to set the app up with, making the key where there is none.
     *
     * @param \Cake\Datasource\EntityInterface $user The account.
     * @return void
     */
    protected function showSetUp(EntityInterface $user): void
    {
        $secret = (string)$user->get('secret');
        if ($secret === '') {
            $secret = OneTimePassword::createSecret();
            $this->getUsersTable()->updateQuery()
                ->set(['secret' => $secret, 'secret_verified' => false, 'otp_last_timestep' => null])
                ->where(['id' => $user->get('id')])
                ->execute();
            $user->set('secret', $secret);
        }

        $issuer = (string)Configure::read('OneTimePasswordAuthenticator.issuer', 'Watcher');
        $this->set([
            'secretDataUri' => OneTimePassword::qrCode(
                OneTimePassword::uri($issuer, (string)$user->get('username'), $secret),
            ),
            'secret' => $secret,
        ]);
    }

    /**
     * Marks the app as set up, and makes the recovery codes to be shown next.
     *
     * @param \Cake\Datasource\EntityInterface $user The account.
     * @return void
     */
    protected function confirmSetUp(EntityInterface $user): void
    {
        $this->getUsersTable()->updateQuery()
            ->set(['secret_verified' => true])
            ->where(['id' => $user->get('id')])
            ->execute();
        $this->getRequest()->getSession()->write(
            self::recoveryCodesKey(),
            $this->recoveryCodesTable()->renew((string)$user->get('id')),
        );
        AuthLog::notice('one-time password set up', [
            'user' => (string)$user->get('username'),
            'ip' => AuthLog::address($this->getRequest()),
        ]);
    }

    /**
     * Takes the one-time codes of an account away, with its recovery codes.
     *
     * @param \Cake\Datasource\EntityInterface $user The account.
     * @return void
     */
    protected function forgetOneTimePassword(EntityInterface $user): void
    {
        $this->getUsersTable()->updateQuery()
            ->set(['secret' => null, 'secret_verified' => false, 'otp_last_timestep' => null])
            ->where(['id' => $user->get('id')])
            ->execute();
        $this->recoveryCodesTable()->deleteAll(['user_id' => $user->get('id')]);
    }

    /**
     * Where the recovery codes wait to be shown.
     *
     * @return string
     */
    protected static function recoveryCodesKey(): string
    {
        return 'Access.recoveryCodes';
    }

    /**
     * The account signed in, as stored.
     *
     * @param bool $secrets Whether to read its password and one-time secret too, to check them.
     * @return \Access\Model\Entity\User
     */
    protected function signedInAccount(bool $secrets = false): User
    {
        /** @var \Authentication\IdentityInterface $identity */
        $identity = $this->getRequest()->getAttribute('identity');

        return $this->getUsersTable()->get($identity->getIdentifier(), finder: $secrets ? 'withSecrets' : 'all');
    }

    /**
     * The recovery codes.
     *
     * @return \Access\Model\Table\RecoveryCodesTable
     */
    protected function recoveryCodesTable(): RecoveryCodesTable
    {
        /** @var \Access\Model\Table\RecoveryCodesTable */
        return $this->fetchTable('Access.RecoveryCodes');
    }

    /**
     * Where signing in starts, keeping where it was meant to lead.
     *
     * @return array<string, mixed>
     */
    protected function loginAction(): array
    {
        return array_merge(
            (array)Configure::read('Auth.AuthenticationComponent.loginAction'),
            ['?' => $this->getRequest()->getQueryParams()],
        );
    }
}
