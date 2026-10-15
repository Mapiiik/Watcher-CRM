<?php
declare(strict_types=1);

namespace Access\Test\TestCase\Model\Entity;

use Access\Model\Entity\User;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Access\Model\Entity\User Test Case
 */
#[UsesClass(User::class)]
class UserTest extends TestCase
{
    /**
     * A password is kept as its Argon2id hash, which tells the right password from a wrong one.
     *
     * @return void
     */
    public function testAPasswordIsKeptAsItsHash(): void
    {
        $user = new User(['password' => 'Kopr na zahrade 7x']);

        $this->assertStringStartsWith('$argon2id$', (string)$user->password);
        $this->assertTrue($user->checkPassword('Kopr na zahrade 7x'));
        $this->assertFalse($user->checkPassword('Kopr na zahrade 8x'));
        $this->assertFalse((new User())->checkPassword(''));
    }

    /**
     * An API key is kept as its SHA-256, and no key stays no key.
     *
     * @return void
     */
    public function testAnApiKeyIsKeptAsItsHash(): void
    {
        $this->assertSame(hash('sha256', 'a key'), (new User(['api_token' => 'a key']))->api_token);
        $this->assertNull((new User(['api_token' => null]))->api_token);
        $this->assertSame('', (new User(['api_token' => '']))->api_token);
    }

    /**
     * An account written out whole leaves its secrets out.
     *
     * @return void
     */
    public function testSecretsAreLeftOutOfAnAccountWrittenOut(): void
    {
        $user = new User([
            'username' => 'jnovak',
            'password' => 'Kopr na zahrade 7x',
            'secret' => 'GEZDGNBVGY3TQOJQ',
            'api_token' => 'a key',
            'token' => 'a reset token',
            'token_expires' => '2026-10-14 12:00:00',
            '_joinData' => ['task_id' => 1],
        ]);

        $this->assertSame(['username' => 'jnovak'], $user->toArray());
    }

    /**
     * A form sets only what an account is made of.
     *
     * @return void
     */
    public function testAFormSetsOnlyWhatAnAccountIsMadeOf(): void
    {
        $user = new User();
        $user->patch([
            'username' => 'jnovak',
            'email' => 'jan.novak@example.com',
            'first_name' => 'Jan',
            'last_name' => 'Novák',
            'role' => 'admin',
            'active' => true,
            'password' => 'Kopr na zahrade 7x',
            'is_superuser' => true,
            'secret_verified' => true,
            'api_token' => 'a key',
            'user_settings' => ['customers.search' => true],
        ]);

        $this->assertSame(
            ['username', 'email', 'first_name', 'last_name', 'role', 'active'],
            $user->getVisible(),
        );
    }

    /**
     * Two-step verification is on where a method of it is set up, the authenticator application
     * being the one so far.
     *
     * @return void
     */
    public function testTwoStepVerificationIsOnWhereAMethodIsSetUp(): void
    {
        $this->assertTrue((new User(['secret_verified' => true]))->hasTwoStepVerification());
        $this->assertFalse((new User(['secret_verified' => false]))->hasTwoStepVerification());
        $this->assertFalse((new User())->hasTwoStepVerification());
    }

    /**
     * An account signs in with a password where it has one: as an account is read, by whether
     * there is one, and as read with its secrets, by the password itself.
     *
     * @return void
     */
    public function testAPasswordSignsInWhereThereIsOne(): void
    {
        $this->assertTrue((new User(['password_sign_in' => true]))->hasPasswordSignIn());
        $this->assertFalse((new User(['password_sign_in' => false]))->hasPasswordSignIn());
        $this->assertTrue((new User(['password' => 'Kopr na zahrade 7x']))->hasPasswordSignIn());
        $this->assertFalse((new User())->hasPasswordSignIn());
    }

    /**
     * A person is called by their name, given name first, and by the account where no name is
     * filled in.
     *
     * @return void
     */
    public function testAPersonIsCalledByTheirName(): void
    {
        $this->assertSame('Jan Novák', (new User([
            'username' => 'jnovak',
            'first_name' => 'Jan',
            'last_name' => 'Novák',
        ]))->name);
        $this->assertSame('Novák', (new User(['username' => 'jnovak', 'last_name' => 'Novák']))->name);
        $this->assertSame('nms-api', (new User(['username' => 'nms-api']))->name);
    }

    /**
     * A list names the surname first and the account beside it, and an account with no name by
     * the account alone.
     *
     * @return void
     */
    public function testAListNamesTheSurnameFirstWithTheAccount(): void
    {
        $this->assertSame('Novák Jan (jnovak)', (new User([
            'username' => 'jnovak',
            'first_name' => 'Jan',
            'last_name' => 'Novák',
        ]))->name_for_lists);
        $this->assertSame('nms-api', (new User(['username' => 'nms-api']))->name_for_lists);
    }

    /**
     * A role is named as the application lists it, and a role it does not list as it is stored.
     *
     * @return void
     */
    public function testARoleIsNamedAsTheApplicationListsIt(): void
    {
        $user = new class (['role' => 'admin']) extends User {
            /**
             * @inheritDoc
             */
            public function getRoleOptions(): array
            {
                return ['admin' => 'Administrator'];
            }
        };

        $this->assertSame('Administrator', $user->getRoleName());
        $this->assertSame('former-role', (new User(['role' => 'former-role']))->getRoleName());
        $this->assertSame('', (new User())->getRoleName());
    }
}
