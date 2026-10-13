<?php
declare(strict_types=1);

namespace Access\Test\TestCase;

use Cake\Datasource\EntityInterface;

/**
 * Accounts made by the test itself, as each application's fixtures hold different ones.
 *
 * The fixtures' own accounts are taken out first. One of them sets a number the database would
 * then hand out again to the next account.
 */
trait AccountsTrait
{
    /**
     * Adds an account that can sign in.
     *
     * @param string $password Its password.
     * @param string $role Its role.
     * @param bool $superuser Whether it is a superuser.
     * @return \Cake\Datasource\EntityInterface
     */
    protected function addAccount(
        string $password = 'Kopr na zahrade 7x',
        string $role = 'user',
        bool $superuser = false,
    ): EntityInterface {
        $this->removeFixtureAccounts();
        $users = $this->getTableLocator()->get('AppUsers');
        $user = $users->newEmptyEntity();
        $user->patch([
            'username' => 'jnovak',
            'email' => 'jan.novak@example.com',
            'first_name' => 'Jan',
            'last_name' => 'Novak',
            'password' => $password,
            'role' => $role,
            'active' => true,
        ], ['guard' => false]);
        $user->set('is_superuser', $superuser);

        return $users->saveOrFail($user);
    }

    /**
     * Adds a second account, beside the one of `addAccount()`.
     *
     * @param string|null $email Its e-mail.
     * @return \Cake\Datasource\EntityInterface
     */
    protected function addOtherAccount(?string $email = null): EntityInterface
    {
        $users = $this->getTableLocator()->get('AppUsers');
        $user = $users->newEmptyEntity();
        $user->patch([
            'username' => 'pdvorak',
            'email' => $email,
            'password' => 'Mrkev na zahrade 9z',
            'role' => 'user',
            'active' => true,
        ], ['guard' => false]);

        return $users->saveOrFail($user);
    }

    /**
     * Takes out the fixtures' accounts, before an account is added some other way.
     *
     * @return void
     */
    protected function removeFixtureAccounts(): void
    {
        // the identities linked to them hold on to them
        $this->getTableLocator()->get('Access.SocialAccounts')->deleteAll(['1 = 1']);
        $this->getTableLocator()->get('AppUsers')->deleteAll(['username !=' => 'jnovak']);
    }
}
