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
        'app.Users',
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
        $stored = (string)$this->getTableLocator()->get('Users')->get($id)->get('api_token');
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
        $this->getTableLocator()->get('Users')->saveOrFail($user);
        $id = (string)$user->get('id');
        $this->session(['Auth' => ['id' => $id, 'username' => 'admin', 'role' => 'admin']]);
        $this->get('/users/view/' . $id);
        $this->assertResponseContains('Remove API Key');
        $this->assertResponseContains("<th>API Key Sign-In</th>\n    <td>On</td>");

        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/users/remove-api-token/' . $id);

        $this->assertRedirectContains('/users/view/' . $id);
        $this->assertNull($this->getTableLocator()->get('Users')->get($id)->get('api_token'));
        $this->_session = [];
        $this->configRequest(['headers' => ['Authorization' => 'Bearer client-key']]);
        $this->get('/login');
        $this->assertResponseOk();
    }

    /**
     * A key signs in only as a Bearer token. In the address, where it would end up in the logs,
     * it is not read at all, and neither is its hash as a key.
     *
     * @return void
     */
    public function testAKeyInTheAddressSignsNobodyIn(): void
    {
        $user = $this->addAccount(role: 'api');
        $user->set('api_token', 'client-key');
        $this->getTableLocator()->get('Users')->saveOrFail($user);

        $this->get('/login?api_key=client-key');
        $this->assertResponseOk();

        $this->configRequest(['headers' => ['Authorization' => 'Bearer ' . hash('sha256', 'client-key')]]);
        $this->get('/login');
        $this->assertResponseOk();
    }

    /**
     * A client asking for data is not sent to sign in: without a valid key it gets 401, and with a
     * key that may not do it 403.
     *
     * @return void
     */
    public function testAClientAskingForDataIsAnsweredWithAStatusNotTheSignInPage(): void
    {
        $user = $this->addAccount(role: 'user');
        $user->set('api_token', 'client-key');
        $this->getTableLocator()->get('Users')->saveOrFail($user);

        $this->configRequest(['headers' => ['Accept' => 'application/json']]);
        $this->get('/users');
        $this->assertResponseCode(401);
        $this->assertHeader('WWW-Authenticate', 'Bearer');
        $this->assertContentType('application/json');

        $this->configRequest(['headers' => ['Accept' => 'application/json', 'Authorization' => 'Bearer wrong']]);
        $this->get('/users');
        $this->assertResponseCode(401);

        $this->configRequest(['headers' => ['Accept' => 'application/json', 'Authorization' => 'Bearer client-key']]);
        $this->get('/users');
        $this->assertResponseCode(403);

        $this->configRequest(['headers' => []]);
        $this->get('/users');
        $this->assertRedirectContains('/login');
    }

    /**
     * The audit log says that a password changed, but not what it changed to.
     *
     * @return void
     */
    public function testTheAuditLogKeepsNoSecrets(): void
    {
        $users = $this->getTableLocator()->get('Users');
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
