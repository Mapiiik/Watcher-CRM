<?php
declare(strict_types=1);

namespace Access\Controller\Trait;

use Access\Authentication\AuthLog;
use Access\Authentication\SecurityPolicy;
use Access\Mailer\UsersMailer;
use Cake\Http\Response;
use Cake\Log\Log;
use Cake\Routing\Router;
use Throwable;

/**
 * A new password for somebody who has forgotten theirs, through a link sent to the e-mail of the
 * account.
 *
 * The answer to asking is the same whether an account was found or not, so it tells nobody which
 * accounts exist. The link works for an hour and once, and only its hash is kept.
 */
trait PasswordResetTrait
{
    /**
     * Sends a link for setting a new password to the account a username or an e-mail names.
     *
     * @return \Cake\Http\Response|null
     */
    public function requestResetPassword(): ?Response
    {
        $this->set('user', $this->getUsersTable()->newEmptyEntity());
        if (!$this->getRequest()->is('post')) {
            return null;
        }

        $reference = (string)$this->getRequest()->getData('reference');
        $address = AuthLog::address($this->getRequest());
        $issued = $this->getUsersTable()->newPasswordToken($reference);
        if ($issued === null) {
            AuthLog::notice('password link not sent', ['reference' => $reference, 'ip' => $address]);
        } else {
            [$user, $token] = $issued;
            try {
                (new UsersMailer())->send('resetPassword', [
                    $user,
                    Router::url(['action' => 'resetPassword', $token], true),
                ]);
                AuthLog::notice('password link sent', ['user' => $user->username, 'ip' => $address]);
            } catch (Throwable $exception) {
                // the same answer as ever, so that a failing mail says nothing about the account
                Log::error('The link for a new password could not be sent: ' . $exception->getMessage());
            }
        }

        $this->Flash->success(__d(
            'access',
            'If the username or the e-mail belongs to an active account, '
            . 'a link for setting a new password is on its way to its e-mail.',
        ));

        return $this->redirect(['action' => 'login']);
    }

    /**
     * Sets a new password through a link sent by e-mail.
     *
     * @param string|null $token The token from the link.
     * @return \Cake\Http\Response|null
     */
    public function resetPassword(?string $token = null): ?Response
    {
        $users = $this->getUsersTable();
        $user = $users->findByPasswordToken((string)$token);
        if ($user === null) {
            $this->Flash->error(
                __d('access', 'The link for setting a new password does not work any more. Ask for a new one.'),
            );

            return $this->redirect(['action' => 'requestResetPassword']);
        }

        if ($this->getRequest()->is(['post', 'put'])) {
            $data = array_intersect_key(
                (array)$this->getRequest()->getData(),
                array_flip(['password', 'password_confirm']),
            );
            $user = $users->patchEntity($user, $data, [
                'validate' => 'passwordConfirm',
                'accessibleFields' => ['password_confirm' => true],
            ]);
            // the link works once
            $user->patch(['token' => null, 'token_expires' => null], ['guard' => false]);

            if (!$user->hasErrors() && $users->save($user)) {
                AuthLog::notice('password reset', [
                    'user' => (string)$user->get('username'),
                    'ip' => AuthLog::address($this->getRequest()),
                ]);
                $this->Flash->success(__d('access', 'The new password is set. Sign in with it.'));

                return $this->redirect(['action' => 'login']);
            }
            $this->Flash->error(__d('access', 'The password could not be set.'));
        }

        $this->set([
            'user' => $user,
            'passwordMinLength' => SecurityPolicy::minimumPasswordLength($user->toArray()),
        ]);

        return null;
    }
}
