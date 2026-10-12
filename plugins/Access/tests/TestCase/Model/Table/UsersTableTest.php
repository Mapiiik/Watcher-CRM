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
        'app.AppUsers',
    ];

    /**
     * A new account's password is held to the rules of its role.
     *
     * @return void
     */
    public function testANewPasswordIsHeldToTheRulesOfItsRole(): void
    {
        $users = $this->getTableLocator()->get('AppUsers');

        $user = $users->newEntity([
            'username' => 'jnovak',
            'password' => 'Kopr na zahrade',
            'role' => 'user',
        ]);
        $this->assertSame([], $user->getError('password'));
        $this->assertStringStartsWith('$argon2id$', $user->get('password'));

        $admin = $users->newEntity([
            'username' => 'jnovak',
            'password' => 'Kopr na zahrade',
            'role' => 'admin',
        ]);
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
        $users = $this->getTableLocator()->get('AppUsers');
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
        $users = $this->getTableLocator()->get('AppUsers');
        $user = $this->addAccount();

        $user->set('password', 'short');

        $this->assertNotFalse($users->save($user));
    }
}
