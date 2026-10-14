<?php
declare(strict_types=1);

namespace App\Model\Table;

use Access\Model\Table\UsersTable as AccessUsersTable;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Override;

/**
 * Users database table
 *
 * @property \App\Model\Table\TasksTable&\Cake\ORM\Association\HasMany $Tasks
 * @property \App\Model\Table\TaskCollaboratorsTable&\Cake\ORM\Association\HasMany $TaskCollaborators
 * @method \App\Model\Entity\User get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\User newEmptyEntity()
 * @method \App\Model\Entity\User newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\User[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\User get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\User findOrCreate($search, callable|array|null $callback = null, $options = [])
 * @method \App\Model\Entity\User patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\User[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\User|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\User saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<\App\Model\Entity\User>|false saveMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\User> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\User>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\User> deleteManyOrFail(iterable $entities, $options = [])
 * @psalm-suppress MethodSignatureMismatch
 */
class UsersTable extends AccessUsersTable
{
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

        $this->hasMany('Tasks', [
            'foreignKey' => 'user_id',
        ]);
        $this->hasMany('TaskCollaborators', [
            'foreignKey' => 'user_id',
        ]);
    }

    /**
     * Accounts a task can be named on.
     *
     * Signing in and holding tasks are different questions, so this is asked separately from
     * `active` - and chained with it where both have to be true, as when a task is being handed
     * to somebody for the first time.
     *
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\User> $query The query to scope.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\User>
     */
    public function findHoldingTasks(SelectQuery $query): SelectQuery
    {
        return $query->where([$this->aliasField('holds_tasks') => true]);
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    #[Override]
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules = parent::buildRules($rules);

        $rules->add(
            function (EntityInterface $entity): bool {
                // Only the moment it is turned off is worth asking about. An account that is
                // already off stays off, and a new one has nothing behind it yet - checking either
                // would fail every sign-in, which saves the account to record the time.
                if ($entity->isNew() || !$entity->isDirty('holds_tasks') || $entity->get('holds_tasks')) {
                    return true;
                }

                // Finished work counts as much as unfinished: a task says whose it was, and an
                // account that stops being one that holds tasks while tasks still name it leaves
                // that answer half told. Standing beside whoever holds one counts the same: it
                // is the account being named that matters, not which of the two names it is.
                $id = $entity->get('id');

                return !$this->Tasks->exists(['Tasks.user_id' => $id])
                    && !$this->TaskCollaborators->exists(['TaskCollaborators.user_id' => $id]);
            },
            'holdsNoTasks',
            [
                'errorField' => 'holds_tasks',
                'message' => __('This account is still named on tasks. Move them to somebody else first.'),
            ],
        );

        // The database refuses this too - the foreign key is `NO ACTION` - but it refuses it by
        // raising, which reaches the operator as an error page rather than as an answer. Asked
        // here, it is an answer.
        $rules->addDelete($rules->isNotLinkedTo('Tasks'));
        $rules->addDelete($rules->isNotLinkedTo('TaskCollaborators'));

        return $rules;
    }
}
