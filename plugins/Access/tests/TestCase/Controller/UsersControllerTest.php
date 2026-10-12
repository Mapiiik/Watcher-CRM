<?php
declare(strict_types=1);

namespace Access\Test\TestCase\Controller;

use Access\Controller\UsersController;
use Access\Test\TestCase\AccountsTrait;
use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Access\Controller\UsersController Test Case
 */
#[UsesClass(UsersController::class)]
class UsersControllerTest extends TestCase
{
    use AccountsTrait;
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.AppUsers',
    ];

    /**
     * The sign-in page is there for anybody.
     *
     * @return void
     */
    public function testTheSignInPageIsOffered(): void
    {
        $this->get('/login');

        $this->assertResponseOk();
        $this->assertResponseContains('name="username"');
        $this->assertResponseContains('name="password"');
    }

    /**
     * Accounts are made by an administrator, so there is nowhere to register - not even for one.
     *
     * @return void
     */
    public function testNobodyRegisters(): void
    {
        $this->get('/register');
        $this->assertRedirectContains('/login');

        $this->session(['Auth' => $this->addAccount(role: 'admin')]);
        $this->get('/register');
        $this->assertResponseCode(404);
    }

    /**
     * Only a token for a new password is ever sent. One that would confirm a registration is
     * turned away before it is looked at.
     *
     * @return void
     */
    public function testOnlyATokenForANewPasswordIsValidated(): void
    {
        $this->session(['Auth' => $this->addAccount(role: 'admin')]);
        $this->get('/users/validate/email/any-token');

        $this->assertResponseCode(404);
    }

    /**
     * A password hashed the old way still signs its owner in, and is stored with Argon2id from then
     * on.
     *
     * @return void
     */
    public function testSigningInMovesAnOldPasswordToArgon2id(): void
    {
        $users = $this->getTableLocator()->get('AppUsers');
        $id = $this->addAccount()->get('id');
        $users->updateQuery()
            ->set(['password' => password_hash('Kopr na zahrade 7x', PASSWORD_BCRYPT)])
            ->where(['id' => $id])
            ->execute();

        $this->enableCsrfToken();
        $this->post('/login', ['username' => 'jnovak', 'password' => 'Kopr na zahrade 7x']);

        $this->assertRedirect('/');
        $stored = (string)$users->get($id)->get('password');
        $this->assertStringStartsWith('$argon2id$', $stored);
        $this->assertTrue(password_verify('Kopr na zahrade 7x', $stored));
    }

    /**
     * A new password is held to the rules of the account, and the form says what they are.
     *
     * @return void
     */
    public function testANewPasswordTooShortForTheAccountIsRefused(): void
    {
        $this->session(['Auth' => $this->addAccount(superuser: true)]);
        $this->get('/users/change-password');
        $this->assertResponseContains('At least 17 characters');

        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/users/change-password', [
            'password' => 'Kopr na zahrade',
            'password_confirm' => 'Kopr na zahrade',
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('The password has to have at least 17 characters.');
    }

    /**
     * Every application reads the same configuration, from this plugin.
     *
     * @return void
     */
    public function testTheConfigurationComesFromThisPlugin(): void
    {
        $this->get('/login');

        $this->assertSame('AppUsers', Configure::read('Users.controller'));
        $this->assertFalse(Configure::read('Users.Profile.viewOthers'));
    }
}
