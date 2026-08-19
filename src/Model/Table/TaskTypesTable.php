<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use Override;
use Tasks\Model\Table\TaskTypesTable as TasksTaskTypesTable;

/**
 * TaskTypes Model
 *
 * On top of the shared type: what this application lets a type require of a task, and the contract
 * states that name a type as the one they wait for.
 *
 * @property \App\Model\Table\TasksTable&\Cake\ORM\Association\HasMany $Tasks
 * @property \App\Model\Table\ContractStatesTable&\Cake\ORM\Association\HasMany $ContractStates
 * @method \App\Model\Entity\TaskType newEmptyEntity()
 * @method \App\Model\Entity\TaskType newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\TaskType[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\TaskType get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\TaskType findOrCreate($search, callable|array|null $callback = null, $options = [])
 * @method \App\Model\Entity\TaskType patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\TaskType[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\TaskType|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\TaskType saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<\App\Model\Entity\TaskType>|false saveMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\TaskType> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\TaskType>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\TaskType> deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class TaskTypesTable extends TasksTaskTypesTable
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

        $this->hasMany('ContractStates', [
            'foreignKey' => 'requires_open_task_type_id',
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
        $validator = parent::validationDefault($validator);

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
    #[Override]
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules = parent::buildRules($rules);

        $rules->addDelete($rules->isNotLinkedTo('ContractStates'));

        return $rules;
    }
}
