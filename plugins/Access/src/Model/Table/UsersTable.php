<?php
declare(strict_types=1);

namespace Access\Model\Table;

use Access\Authentication\PasswordRules;
use Access\Model\Entity\User;
use ArrayObject;
use AuditStash\Persister\TablePersister;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
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
 * @property \Access\Model\Table\SocialAccountsTable&\Cake\ORM\Association\HasMany $SocialAccounts
 */
class UsersTable extends Table
{
    /**
     * The fields of an account that decide which rules its password is held to.
     *
     * @var list<string>
     */
    private const PASSWORD_CONTEXT = ['username', 'email', 'role', 'is_superuser'];

    /**
     * How long a link for setting a new password works.
     */
    public const PASSWORD_TOKEN_LIFETIME = '+1 hour';

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

        $this->setTable('users');
        $this->setDisplayField('username');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');

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
        $auditLog->setConfig('sensitive', ['password', 'secret', 'api_token', 'token']);

        $this->hasMany('SocialAccounts', [
            'className' => 'Access.SocialAccounts',
            'foreignKey' => 'user_id',
            'dependent' => true,
        ]);
    }

    /**
     * Accounts that may sign in.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Access\Model\Entity\User> $query The query.
     * @return \Cake\ORM\Query\SelectQuery<\Access\Model\Entity\User>
     */
    public function findActive(SelectQuery $query): SelectQuery
    {
        return $query->where([$this->aliasField('active') => true]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    #[Override]
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->requirePresence('username', 'create')
            ->notEmptyString('username');

        $validator
            ->requirePresence('password', 'create')
            ->notEmptyString('password');

        return $validator;
    }

    /**
     * A new password, typed twice.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationPasswordConfirm(Validator $validator): Validator
    {
        $validator
            ->requirePresence('password')
            ->notEmptyString('password')
            ->requirePresence('password_confirm')
            ->notEmptyString('password_confirm')
            ->add('password', 'confirmed', [
                'rule' => ['compareWith', 'password_confirm'],
                'message' => __d('access', 'The two passwords are not the same.'),
            ]);

        return $validator;
    }

    /**
     * A new password, typed twice, with the current one as proof of who is changing it.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationPasswordChange(Validator $validator): Validator
    {
        return $this->validationPasswordConfirm($validator)
            ->requirePresence('current_password')
            ->notEmptyString('current_password');
    }

    /**
     * Returns a rules checker object that will be used for validating application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    #[Override]
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['username']), 'uniqueUsername', [
            'errorField' => 'username',
            'message' => __d('access', 'This username is already taken.'),
        ]);

        return $rules;
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
     * Makes a link for setting a new password, for the active account that a username or an
     * e-mail names alone, where it has an e-mail to send the link to.
     *
     * Only the hash of the token is kept, and a new one replaces the last.
     *
     * @param string $reference A username or an e-mail.
     * @return array{0: \Access\Model\Entity\User, 1: string}|null The account and the token.
     */
    public function newPasswordToken(string $reference): ?array
    {
        $reference = trim($reference);
        if ($reference === '') {
            return null;
        }

        // an e-mail several accounts share names none of them
        $accounts = $this->find('active')
            ->where(['OR' => [$this->aliasField('username') => $reference, $this->aliasField('email') => $reference]])
            ->limit(2)
            ->all();
        $user = $accounts->first();
        if ($accounts->count() !== 1 || !$user instanceof User || (string)$user->email === '') {
            return null;
        }

        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $this->updateQuery()
            ->set([
                'token' => hash('sha256', $token),
                'token_expires' => DateTime::now()->modify(self::PASSWORD_TOKEN_LIFETIME),
            ])
            ->where(['id' => $user->id])
            ->execute();

        return [$user, $token];
    }

    /**
     * The active account a link for setting a new password is for, while it works.
     *
     * @param string $token The token from the link.
     * @return \Access\Model\Entity\User|null
     */
    public function findByPasswordToken(string $token): ?User
    {
        if ($token === '') {
            return null;
        }

        /** @var \Access\Model\Entity\User|null */
        return $this->find('active')
            ->where([
                $this->aliasField('token') => hash('sha256', $token),
                $this->aliasField('token_expires') . ' >' => DateTime::now(),
            ])
            ->first();
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
