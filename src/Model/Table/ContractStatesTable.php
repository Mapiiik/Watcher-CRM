<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

/**
 * ContractStates Model
 *
 * @property \App\Model\Table\ContractsTable&\Cake\ORM\Association\HasMany $Contracts
 * @method \App\Model\Entity\ContractState newEmptyEntity()
 * @method \App\Model\Entity\ContractState newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\ContractState[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\ContractState get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\ContractState findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\ContractState patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\ContractState[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\ContractState|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\ContractState saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<\App\Model\Entity\ContractState>|false saveMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\ContractState> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\ContractState>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\ContractState> deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ContractStatesTable extends AppTable
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

        $this->setTable('contract_states');
        $this->setDisplayField('name_for_lists');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');

        $this->hasMany('Contracts', [
            'foreignKey' => 'contract_state_id',
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
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('color')
            ->maxLength('color', 7)
            ->notEmptyString('color');

        $validator
            ->boolean('active_services')
            ->requirePresence('active_services', 'create')
            ->notEmptyString('active_services');

        $validator
            ->boolean('billed')
            ->requirePresence('billed', 'create')
            ->notEmptyString('billed');

        $validator
            ->boolean('blocked')
            ->requirePresence('blocked', 'create')
            ->notEmptyString('blocked');

        $validator
            ->scalar('note')
            ->allowEmptyString('note');

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
        $rules->addDelete($rules->isNotLinkedTo('Contracts'));

        return $rules;
    }
}
