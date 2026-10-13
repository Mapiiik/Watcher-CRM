<?php
declare(strict_types=1);

namespace Access\Model\Table;

use Access\Authentication\PasswordRules;
use ArrayObject;
use AuditStash\Persister\TablePersister;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use CakeDC\Users\Model\Table\UsersTable as BaseUsersTable;
use Override;

/**
 * The accounts people sign in with, shared by the applications.
 *
 * Each application extends this as its `AppUsersTable` and adds what accounts are named on there.
 *
 * @method \Access\Model\Entity\User get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \Access\Model\Entity\User newEmptyEntity()
 * @method \Access\Model\Entity\User newEntity(array $data, array $options = [])
 * @method \Access\Model\Entity\User patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Access\Model\Entity\User|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Access\Model\Entity\User saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @psalm-suppress MethodSignatureMismatch
 */
class UsersTable extends BaseUsersTable
{
    /**
     * The fields of an account that decide which rules its password is held to.
     *
     * @var list<string>
     */
    private const PASSWORD_CONTEXT = ['username', 'email', 'role', 'is_superuser'];

    /**
     * Initialize method
     *
     * @param array<array-key, mixed> $config The configuration for the Table.
     * @return void
     */
    #[Override]
    public function initialize(array $config): void
    {
        parent::initialize($config);

        // Persisting audit log
        $this->addBehavior('AuditStash.AuditLog');
        /** @var \AuditStash\Model\Behavior\AuditLogBehavior $auditLog */
        $auditLog = $this->getBehavior('AuditLog');
        /** @var \AuditStash\Persister\TablePersister $auditLogPersister */
        $auditLogPersister = $auditLog->persister();
        $auditLogPersister->setConfig([
            'serializeFields' => false,
            'primaryKeyExtractionStrategy' => TablePersister::STRATEGY_RAW,
        ]);
        // that these changed is worth keeping, what they changed to is not
        $auditLog->setConfig('sensitive', ['password', 'secret', 'api_token', 'token', 'login_token']);
    }

    /**
     * Holds a new password to the rules of the account it is for.
     *
     * This is checked here rather than by a validator, as the rules depend on the account and a
     * validator is not told which account it checks. It also has to happen before the entity
     * hashes the password, which it does as soon as the password is set. A password set without
     * the form, such as one hashed again on signing in, is not checked, so the passwords people
     * already have keep working.
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event The event.
     * @param \Cake\Datasource\EntityInterface $entity The account, with the password already hashed.
     * @param \ArrayObject<string, mixed> $data What was submitted.
     * @param \ArrayObject<string, mixed> $options The options.
     * @return void
     */
    public function afterMarshal(
        EventInterface $event,
        EntityInterface $entity,
        ArrayObject $data,
        ArrayObject $options,
    ): void {
        $password = $data['password'] ?? null;
        if (!is_string($password) || $password === '' || !$entity->isDirty('password')) {
            return;
        }

        $account = $this->passwordContext($entity);
        $problems = PasswordRules::forAccount($account)->problems(
            $password,
            isset($account['username']) ? (string)$account['username'] : null,
            isset($account['email']) ? (string)$account['email'] : null,
        );
        if ($problems !== []) {
            $entity->setError('password', $problems);
        }
    }

    /**
     * What decides the rules for a password: the account as stored, with whatever the same form
     * changes on it.
     *
     * @param \Cake\Datasource\EntityInterface $entity The account.
     * @return array<string, mixed>
     */
    private function passwordContext(EntityInterface $entity): array
    {
        $account = [];
        if (!$entity->isNew() && $entity->get('id') !== null) {
            /** @var array<string, mixed> $account */
            $account = $this->find()
                ->select(self::PASSWORD_CONTEXT)
                ->where([$this->aliasField('id') => $entity->get('id')])
                ->disableHydration()
                ->first() ?? [];
        }
        foreach (self::PASSWORD_CONTEXT as $field) {
            if ($entity->has($field)) {
                $account[$field] = $entity->get($field);
            }
        }

        return $account;
    }
}
