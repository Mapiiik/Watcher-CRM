<?php
declare(strict_types=1);

namespace Access\Test\TestCase\Database\Type;

use Access\Database\Type\EncryptedType;
use Access\Test\TestCase\AccountsTrait;
use Cake\TestSuite\TestCase;
use Cake\Utility\Security;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Access\Database\Type\EncryptedType Test Case
 */
#[UsesClass(EncryptedType::class)]
class EncryptedTypeTest extends TestCase
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
     * A value reads back as it was, and is stored differently each time.
     *
     * @return void
     */
    public function testAValueReadsBackAndIsNeverStoredTheSameWay(): void
    {
        $sealed = EncryptedType::seal('GEZDGNBVGY3TQOJQ');

        $this->assertSame('GEZDGNBVGY3TQOJQ', EncryptedType::open($sealed));
        $this->assertStringNotContainsString('GEZDGNBVGY3TQOJQ', $sealed);
        $this->assertNotSame($sealed, EncryptedType::seal('GEZDGNBVGY3TQOJQ'));
    }

    /**
     * A value changed since, stored under another salt, or never encrypted reads as null.
     *
     * @return void
     */
    public function testAValueThatCannotBeReadIsNull(): void
    {
        $sealed = EncryptedType::seal('GEZDGNBVGY3TQOJQ');
        $raw = (string)base64_decode(substr($sealed, 3));
        $changed = 'v1:' . base64_encode(substr($raw, 0, -1) . chr(ord(substr($raw, -1)) ^ 1));

        $this->assertNull(EncryptedType::open($changed));
        $this->assertNull(EncryptedType::open('GEZDGNBVGY3TQOJQ'));
        $this->assertNull(EncryptedType::open('v1:not base64'));

        $salt = Security::getSalt();
        try {
            Security::setSalt(str_repeat('x', 64));
            $this->assertNull(EncryptedType::open($sealed));
        } finally {
            Security::setSalt($salt);
        }
    }

    /**
     * The key of an account's one-time codes is stored encrypted, and reads back as it was.
     *
     * @return void
     */
    public function testTheKeyOfTheCodesIsStoredEncrypted(): void
    {
        $users = $this->getTableLocator()->get('Users');
        $id = (string)$this->addAccount()->get('id');
        $users->updateQuery()->set(['secret' => 'GEZDGNBVGY3TQOJQ'])->where(['id' => $id])->execute();

        $stored = (string)$users->getConnection()->selectQuery('secret', 'users')
            ->where(['id' => $id])
            ->execute()
            ->fetchColumn(0);
        $this->assertStringStartsWith('v1:', $stored);
        $this->assertStringNotContainsString('GEZDGNBVGY3TQOJQ', $stored);
        $this->assertSame('GEZDGNBVGY3TQOJQ', $users->get($id, finder: 'withSecrets')->get('secret'));
    }
}
