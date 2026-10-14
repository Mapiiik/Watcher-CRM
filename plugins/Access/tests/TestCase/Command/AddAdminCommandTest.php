<?php
declare(strict_types=1);

namespace Access\Test\TestCase\Command;

use Access\Authentication\Argon2idPasswordHasher;
use Access\Command\AddAdminCommand;
use Access\Test\TestCase\AccountsTrait;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Access\Command\AddAdminCommand Test Case
 */
#[UsesClass(AddAdminCommand::class)]
class AddAdminCommandTest extends TestCase
{
    use AccountsTrait;
    use ConsoleIntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
    ];

    /**
     * The admin can sign in with the password shown, which is kept only as its hash.
     *
     * @return void
     */
    public function testTheAdminSignsInWithThePasswordShown(): void
    {
        $this->removeFixtureAccounts();

        $this->exec('access add_admin anovak anna.novak@example.com');

        $this->assertExitSuccess();
        $this->assertOutputRegExp('/password \S+$/m');
        preg_match('/password (\S+)$/m', (string)$this->_out?->output(), $shown);
        $admin = $this->getTableLocator()->get('Users')
            ->find('withSecrets')
            ->where(['username' => 'anovak'])
            ->firstOrFail();
        $this->assertSame('admin', $admin->get('role'));
        $this->assertTrue($admin->get('active'));
        $this->assertFalse($admin->get('is_superuser'));
        $this->assertStringStartsWith('$argon2id$', $admin->get('password'));
        $this->assertTrue((new Argon2idPasswordHasher())->check($shown[1] ?? '', $admin->get('password')));
    }

    /**
     * A name already taken adds nobody and says why.
     *
     * @return void
     */
    public function testANameAlreadyTakenAddsNobody(): void
    {
        $this->addAccount();
        $users = $this->getTableLocator()->get('Users');
        $before = $users->find()->count();

        $this->exec('access add_admin jnovak anna.novak@example.com');

        $this->assertExitError();
        $this->assertErrorContains('username');
        $this->assertSame($before, $users->find()->count());
    }
}
