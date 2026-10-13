<?php
declare(strict_types=1);

namespace Access\Test\TestCase\Controller;

use Access\Controller\UsersController;
use Access\Test\TestCase\AccountsTrait;
use Cake\Log\Engine\ArrayLog;
use Cake\Log\Log;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * API keys, and what of an account the audit log keeps.
 */
#[UsesClass(UsersController::class)]
class ApiTokenTest extends TestCase
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
     * @inheritDoc
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Log::drop('auth');
        Log::setConfig('auth', ['className' => ArrayLog::class, 'scopes' => ['auth']]);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function tearDown(): void
    {
        Log::drop('auth');

        parent::tearDown();
    }

    /**
     * An admin makes a key, which is shown once and kept only as its hash, and signs the client in
     * when it comes as a Bearer token.
     *
     * @return void
     */
    public function testAnAdminMakesAKeyThatIsShownOnce(): void
    {
        $id = (string)$this->addAccount(role: 'api')->get('id');
        $this->session(['Auth' => ['id' => $id, 'username' => 'admin', 'role' => 'admin']]);
        $this->get('/users/view/' . $id);
        $this->assertResponseContains('New API Key');

        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/users/generate-api-token/' . $id);
        $this->assertRedirectContains('/users/api-token');
        $this->session($_SESSION ?? []);
        $this->get('/users/api-token');

        $this->assertResponseOk();
        $this->assertSame(1, preg_match('/<code>([A-Za-z0-9_-]{43})<\/code>/', $this->_getBodyAsString(), $match));
        $key = $match[1] ?? '';
        $stored = (string)$this->getTableLocator()->get('AppUsers')->get($id)->get('api_token');
        $this->assertSame(hash('sha256', $key), $stored);
        $this->session($_SESSION ?? []);
        $this->get('/users/api-token');
        $this->assertRedirect(['action' => 'index']);

        $this->_session = [];
        $this->configRequest(['headers' => ['Authorization' => 'Bearer ' . $key]]);
        $this->get('/login');
        $this->assertRedirect('/');
    }

    /**
     * An admin takes a key away, after which it signs nobody in.
     *
     * @return void
     */
    public function testAnAdminRemovesAKey(): void
    {
        $user = $this->addAccount(role: 'api');
        $user->set('api_token', 'client-key');
        $this->getTableLocator()->get('AppUsers')->saveOrFail($user);
        $id = (string)$user->get('id');
        $this->session(['Auth' => ['id' => $id, 'username' => 'admin', 'role' => 'admin']]);
        $this->get('/users/view/' . $id);
        $this->assertResponseContains('Remove API Key');

        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/users/remove-api-token/' . $id);

        $this->assertRedirectContains('/users/view/' . $id);
        $this->assertNull($this->getTableLocator()->get('AppUsers')->get($id)->get('api_token'));
        $this->_session = [];
        $this->configRequest(['headers' => ['Authorization' => 'Bearer client-key']]);
        $this->get('/login');
        $this->assertResponseOk();
    }

    /**
     * A key in the address still signs in, and says so in the log, so that the clients left on it
     * can be found. A key that is not one does not sign in.
     *
     * @return void
     */
    public function testAKeyInTheAddressStillSignsInAndIsLogged(): void
    {
        $user = $this->addAccount(role: 'api');
        $user->set('api_token', 'client-key');
        $this->getTableLocator()->get('AppUsers')->saveOrFail($user);

        $this->get('/login?api_key=client-key');
        $this->assertRedirect('/');
        /** @var \Cake\Log\Engine\ArrayLog $log */
        $log = Log::engine('auth');
        $this->assertStringContainsString('api key in the address path="/login"', implode("\n", $log->read()));

        $this->get('/login?api_key=' . hash('sha256', 'client-key'));
        $this->assertResponseOk();
    }

    /**
     * The audit log says that a password changed, but not what it changed to.
     *
     * @return void
     */
    public function testTheAuditLogKeepsNoSecrets(): void
    {
        $users = $this->getTableLocator()->get('AppUsers');
        $user = $this->addAccount();
        $auditLogs = $this->getTableLocator()->get('AuditStash.AuditLogs');
        $auditLogs->deleteAll(['1 = 1']);

        $user->set('password', 'Another garden 2026');
        $user->set('api_token', 'client-key');
        $users->saveOrFail($user);

        $row = $auditLogs->find()->orderByDesc('created')->firstOrFail();
        $changed = $row->get('changed');
        $changed = is_string($changed) ? json_decode($changed, true) : $changed;
        $this->assertSame('****', $changed['password']);
        $this->assertSame('****', $changed['api_token']);
    }
}
