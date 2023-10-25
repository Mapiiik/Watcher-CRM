<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

/**
 * TaskTypes Model
 *
 * @property \App\Model\Table\TasksTable&\Cake\ORM\Association\HasMany $Tasks
 * @method \App\Model\Entity\TaskType newEmptyEntity()
 * @method \App\Model\Entity\TaskType newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\TaskType[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\TaskType get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\TaskType findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\TaskType patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\TaskType[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\TaskType|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\TaskType saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\TaskType[]|iterable<\Cake\Datasource\EntityInterface>|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\TaskType[]|iterable<\Cake\Datasource\EntityInterface> saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\TaskType[]|iterable<\Cake\Datasource\EntityInterface>|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\TaskType[]|iterable<\Cake\Datasource\EntityInterface> deleteManyOrFail(iterable $entities, $options = [])
 */
class TaskTypesTable extends AppTable
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('task_types');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');

        $this->hasMany('Tasks', [
            'foreignKey' => 'task_type_id',
            'sort' => [
                'TaskStates.priority' => 'DESC',
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
            ->boolean('customer_required')
            ->notEmptyString('customer_required');

        $validator
            ->boolean('contract_required')
            ->notEmptyString('contract_required');

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
