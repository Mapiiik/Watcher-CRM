<?php
declare(strict_types=1);

namespace Access\Test\TestCase\Model\Table;

use Access\Model\Table\UsersTable;
use Access\Test\TestCase\AccountsTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Access\Model\Table\UsersTable Test Case
 */
#[UsesClass(UsersTable::class)]
class UsersTableTest extends TestCase
{
    use AccountsTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
    ];

    /**
     * A new account's password is held to the rules of its role.
     *
     * @return void
     */
    public function testANewPasswordIsHeldToTheRulesOfItsRole(): void
    {
        $users = $this->getTableLocator()->get('Users');

        $user = $users->newEntity([
            'username' => 'jnovak',
            'password' => 'Kopr na zahrade',
            'role' => 'user',
        ], ['accessibleFields' => ['password' => true]]);
        $this->assertSame([], $user->getError('password'));
        $this->assertStringStartsWith('$argon2id$', $user->get('password'));

        $admin = $users->newEntity([
            'username' => 'jnovak',
            'password' => 'Kopr na zahrade',
            'role' => 'admin',
        ], ['accessibleFields' => ['password' => true]]);
        $this->assertSame(
            ['The password has to have at least 17 characters.'],
            array_values($admin->getError('password')),
        );
    }

    /**
     * A password changed on its own is held to the rules of the account as it is stored, which
     * the form changing it does not carry.
     *
     * @return void
     */
    public function testAChangedPasswordIsHeldToTheRulesOfTheStoredAccount(): void
    {
        $users = $this->getTableLocator()->get('Users');
        $superuser = $this->addAccount(superuser: true);
        $user = $users->newEntity([], ['validate' => false]);
        $user->setNew(false);
        $user->set('id', $superuser->get('id'));

        $users->patchEntity($user, ['password' => 'Kopr na zahrade'], [
            'validate' => false,
            'accessibleFields' => ['password' => true],
        ]);

        $this->assertSame(
            ['The password has to have at least 17 characters.'],
            array_values($user->getError('password')),
        );
    }

    /**
     * A password set without the form, as when it is hashed again on signing in, is not checked,
     * so a password somebody already has keeps working.
     *
     * @return void
     */
    public function testAPasswordSetWithoutTheFormIsNotChecked(): void
    {
        $users = $this->getTableLocator()->get('Users');
        $user = $this->addAccount();

        $user->set('password', 'short');

        $this->assertNotFalse($users->save($user));
    }

    /**
     * The password and the one-time secret are read only by a query that asks for them - not
     * with the account, nor with an account joined to another record.
     *
     * @return void
     */
    public function testSecretsAreReadOnlyWhenAskedFor(): void
    {
        $users = $this->getTableLocator()->get('Users');
        $id = (string)$this->addAccount()->get('id');
        $users->updateQuery()->set(['secret' => 'JBSWY3DPEHPK3PXP'])->where(['id' => $id])->execute();
        $links = $this->getTableLocator()->get('Access.SocialAccounts');
        $links->link($id, 'google', '108234567890', null);

        $plain = $users->get($id);
        $joined = $links->find()->contain('Users')->firstOrFail()->get('user');
        foreach ([$plain, $joined] as $account) {
            $this->assertSame('jnovak', $account->get('username'));
            $this->assertNull($account->get('password'));
            $this->assertNull($account->get('secret'));
        }

        // a join that only filters adds no columns, so its query serves as a list of ids
        $linked = $links->find()->select(['SocialAccounts.user_id'])->innerJoinWith('Users');
        $this->assertSame(1, $users->find()->where(['Users.id IN' => $linked])->count());

        $asked = $users->get($id, finder: 'withSecrets');
        $this->assertStringStartsWith('$argon2id$', $asked->get('password'));
        $this->assertSame('JBSWY3DPEHPK3PXP', $asked->get('secret'));
    }

    /**
     * A form sets only what an account is made of. A password, a key or anything signing in keeps
     * is set only where the action asks for it by name.
     *
     * @return void
     */
    public function testAFormSetsOnlyWhatAnAccountIsMadeOf(): void
    {
        $users = $this->getTableLocator()->get('Users');

        $user = $users->newEntity([
            'username' => 'jnovak',
            'first_name' => 'Jan',
            'password' => 'Kopr na zahrade 7x',
            'api_token' => 'a key',
            'secret' => 'JBSWY3DPEHPK3PXP',
            'is_superuser' => true,
            'last_login' => '2026-10-13 12:00:00',
        ], ['validate' => false]);

        $this->assertSame('Jan', $user->get('first_name'));
        foreach (['password', 'api_token', 'secret', 'is_superuser', 'last_login'] as $field) {
            $this->assertFalse($user->has($field), $field);
        }
    }
}
