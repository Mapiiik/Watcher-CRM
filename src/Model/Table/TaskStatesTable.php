<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

/**
 * TaskStates Model
 *
 * @property \App\Model\Table\TasksTable&\Cake\ORM\Association\HasMany $Tasks
 * @method \App\Model\Entity\TaskState newEmptyEntity()
 * @method \App\Model\Entity\TaskState newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\TaskState[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\TaskState get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\TaskState findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\TaskState patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\TaskState[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\TaskState|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\TaskState saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<\App\Model\Entity\TaskState>|false saveMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\TaskState> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\TaskState>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\TaskState> deleteManyOrFail(iterable $entities, $options = [])
 */
class TaskStatesTable extends AppTable
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('task_states');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');

        $this->hasMany('Tasks', [
            'foreignKey' => 'task_state_id',
            'sort' => [
                'Tasks.priority' => 'DESC',
                'Tasks.nid' => 'DESC',
            ],
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->uuid('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('name')
            ->allowEmptyString('name');

        $validator
            ->integer('priority')
            ->notEmptyString('priority');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->addDelete($rules->isNotLinkedTo('Tasks'));

        return $rules;
    }
}
