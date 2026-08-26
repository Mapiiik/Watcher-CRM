<?php
declare(strict_types=1);

namespace Tasks\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use Override;

/**
 * TaskCollaborators Model
 *
 * The table behind {@see \Tasks\Model\Table\TasksTable}'s `Collaborators`. It is spelled out as
 * a model of its own rather than left to the framework so that putting somebody on a task, and
 * taking them off it again, is written into the history the same way every other change is.
 *
 * @property \Tasks\Model\Table\TasksTable&\Cake\ORM\Association\BelongsTo $Tasks
 * @property \App\Model\Table\AppUsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @method \Tasks\Model\Entity\TaskCollaborator newEmptyEntity()
 * @method \Tasks\Model\Entity\TaskCollaborator newEntity(array $data, array $options = [])
 * @method \Tasks\Model\Entity\TaskCollaborator[] newEntities(array $data, array $options = [])
 * @method \Tasks\Model\Entity\TaskCollaborator get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \Tasks\Model\Entity\TaskCollaborator findOrCreate($search, callable|array|null $callback = null, $options = [])
 * @method \Tasks\Model\Entity\TaskCollaborator patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Tasks\Model\Entity\TaskCollaborator[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Tasks\Model\Entity\TaskCollaborator|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Tasks\Model\Entity\TaskCollaborator saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<\Tasks\Model\Entity\TaskCollaborator>|false saveMany(iterable $entities, $options = [])
 * @method iterable<\Tasks\Model\Entity\TaskCollaborator> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<\Tasks\Model\Entity\TaskCollaborator>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<\Tasks\Model\Entity\TaskCollaborator> deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class TaskCollaboratorsTable extends AppTable
{
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

        $this->setTable('task_collaborators');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');

        $this->belongsTo('Tasks', [
            'foreignKey' => 'task_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Users', [
            'className' => 'AppUsers',
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);
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
            ->uuid('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->uuid('task_id')
            ->requirePresence('task_id', 'create')
            ->notEmptyString('task_id');

        $validator
            ->uuid('user_id')
            ->requirePresence('user_id', 'create')
            ->notEmptyString('user_id');

        return $validator;
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
        $rules->add($rules->isUnique(['task_id', 'user_id']), ['errorField' => 'user_id']);
        $rules->add($rules->existsIn(['task_id'], 'Tasks'), ['errorField' => 'task_id']);
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);

        return $rules;
    }
}
