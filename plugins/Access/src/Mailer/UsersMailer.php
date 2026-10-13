<?php
declare(strict_types=1);

namespace Access\Mailer;

use Access\Model\Entity\User;
use Cake\Mailer\Mailer;

/**
 * Mail to the owners of accounts.
 */
class UsersMailer extends Mailer
{
    /**
     * A link for setting a new password.
     *
     * @param \Access\Model\Entity\User $user The account.
     * @param string $url The link.
     * @return void
     */
    public function resetPassword(User $user, string $url): void
    {
        $this
            ->setTo((string)$user->email)
            ->setSubject(__d('access', 'A new password for {0}', (string)$user->username))
            ->setEmailFormat('text')
            ->setViewVars([
                'username' => (string)$user->username,
                'firstName' => (string)$user->first_name,
                'url' => $url,
            ]);
        $this->viewBuilder()->setTemplate('Access.reset_password');
    }
}
