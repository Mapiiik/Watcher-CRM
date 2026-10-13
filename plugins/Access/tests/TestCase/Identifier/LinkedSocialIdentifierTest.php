<?php
declare(strict_types=1);

namespace Access\Test\TestCase\Identifier;

use Access\Identifier\LinkedSocialIdentifier;
use Access\Test\TestCase\AccountsTrait;
use ArrayAccess;
use Cake\Log\Engine\ArrayLog;
use Cake\Log\Log;
use Cake\TestSuite\TestCase;
use CakeDC\Auth\Identifier\SocialIdentifier;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Access\Identifier\LinkedSocialIdentifier Test Case
 */
#[UsesClass(LinkedSocialIdentifier::class)]
class LinkedSocialIdentifierTest extends TestCase
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
     * An identity nobody linked signs nobody in and makes no account - not even one with the
     * same e-mail - and is logged for an admin to link it.
     *
     * @return void
     */
    public function testAnIdentityNobodyLinkedSignsNobodyIn(): void
    {
        $this->addAccount();
        $users = $this->getTableLocator()->get('AppUsers');
        $before = $users->find()->count();

        $found = $this->identifier()->identify($this->identity('108234567890', 'jan.novak@example.com'));

        $this->assertNull($found);
        $this->assertSame($before, $users->find()->count());
        /** @var \Cake\Log\Engine\ArrayLog $log */
        $log = Log::engine('auth');
        $this->assertStringContainsString(
            'social account not linked provider="google" reference="108234567890"',
            implode("\n", $log->read()),
        );
    }

    /**
     * A linked identity signs in the account it is linked to, while the account is active.
     *
     * @return void
     */
    public function testALinkedIdentitySignsInItsActiveAccount(): void
    {
        $user = $this->addAccount();
        $this->getTableLocator()->get('Access.SocialAccounts')
            ->link((string)$user->get('id'), 'google', '108234567890', 'jan.novak@example.com');

        $found = $this->identifier()->identify($this->identity('108234567890', 'somebody@example.com'));
        $this->assertInstanceOf(ArrayAccess::class, $found);
        $this->assertSame($user->get('id'), $found['id']);

        $this->getTableLocator()->get('AppUsers')->updateQuery()
            ->set(['active' => false])
            ->where(['id' => $user->get('id')])
            ->execute();
        $this->assertNull($this->identifier()->identify($this->identity('108234567890', null)));
    }

    /**
     * An identity is linked to one account only.
     *
     * @return void
     */
    public function testAnIdentityIsLinkedToOneAccountOnly(): void
    {
        $user = $this->addAccount();
        $other = $this->addOtherAccount();
        $links = $this->getTableLocator()->get('Access.SocialAccounts');
        $links->link((string)$user->get('id'), 'google', '108234567890', null);

        $refused = $links->link((string)$other->get('id'), 'google', '108234567890', null);

        $this->assertNotEmpty($refused->getError('reference'));
    }

    /**
     * The identifier as the configuration sets it up.
     *
     * @return \Access\Identifier\LinkedSocialIdentifier
     */
    private function identifier(): LinkedSocialIdentifier
    {
        return new LinkedSocialIdentifier([
            'resolver' => ['className' => 'Authentication.Orm', 'userModel' => 'AppUsers', 'finder' => 'active'],
        ]);
    }

    /**
     * An identity as a provider describes it.
     *
     * @param string $reference What the provider calls it.
     * @param string|null $email Its e-mail.
     * @return array<string, mixed>
     */
    private function identity(string $reference, ?string $email): array
    {
        return [SocialIdentifier::CREDENTIAL_KEY => ['provider' => 'google', 'id' => $reference, 'email' => $email]];
    }
}
