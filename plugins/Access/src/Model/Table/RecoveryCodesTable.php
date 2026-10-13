<?php
declare(strict_types=1);

namespace Access\Model\Table;

use Access\Authentication\Argon2idPasswordHasher;
use Cake\I18n\DateTime;
use Cake\ORM\Table;
use Override;

/**
 * Codes that sign somebody in who has lost the phone with their one-time codes, each one once.
 *
 * Only a hash of a code is kept. A code is shown once, when it is made.
 *
 * @method \Access\Model\Entity\RecoveryCode newEmptyEntity()
 * @method \Access\Model\Entity\RecoveryCode saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class RecoveryCodesTable extends Table
{
    /**
     * How many codes are made at once.
     */
    public const COUNT = 10;

    /**
     * The characters of a code: capitals and digits, without the ones easy to mistake for others.
     */
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    #[Override]
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('recovery_codes');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp', ['events' => ['Model.beforeSave' => ['created' => 'new']]]);
    }

    /**
     * Makes new codes for an account, replacing the ones it had.
     *
     * @param string $userId The account.
     * @return list<string> The codes, to be shown once.
     */
    public function renew(string $userId): array
    {
        $hasher = new Argon2idPasswordHasher();

        return $this->getConnection()->transactional(function () use ($userId, $hasher): array {
            $this->deleteAll(['user_id' => $userId]);

            $codes = [];
            for ($i = 0; $i < self::COUNT; $i++) {
                $code = self::generate();
                $entity = $this->newEmptyEntity();
                $entity->patch(['user_id' => $userId, 'code_hash' => $hasher->hash(self::normalize($code))]);
                $this->saveOrFail($entity);
                $codes[] = $code;
            }

            return $codes;
        });
    }

    /**
     * Uses up a code of an account, if it is one of its unused codes.
     *
     * @param string $userId The account.
     * @param string $code The code as typed.
     * @return bool Whether it was one.
     */
    public function useUp(string $userId, string $code): bool
    {
        $code = self::normalize($code);
        if (strlen($code) !== 10) {
            return false;
        }

        $hasher = new Argon2idPasswordHasher();
        $unused = $this->find()->where(['user_id' => $userId, 'used IS' => null])->all();
        foreach ($unused as $entity) {
            if (!$hasher->check($code, (string)$entity->get('code_hash'))) {
                continue;
            }

            // used up only by whoever marks it first, should two try it at once
            return $this->updateQuery()
                ->set(['used' => DateTime::now()])
                ->where(['id' => $entity->get('id'), 'used IS' => null])
                ->execute()
                ->rowCount() === 1;
        }

        return false;
    }

    /**
     * How many unused codes an account has left.
     *
     * @param string $userId The account.
     * @return int
     */
    public function left(string $userId): int
    {
        return $this->find()->where(['user_id' => $userId, 'used IS' => null])->count();
    }

    /**
     * A new code, written `XXXXX-XXXXX`.
     *
     * @return string
     */
    private static function generate(): string
    {
        $code = '';
        for ($i = 0; $i < 10; $i++) {
            $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        return substr($code, 0, 5) . '-' . substr($code, 5);
    }

    /**
     * A code as it is hashed: capitals, without the dash and spaces.
     *
     * @param string $code The code as typed.
     * @return string
     */
    private static function normalize(string $code): string
    {
        return strtoupper((string)preg_replace('/[\s-]+/', '', $code));
    }
}
