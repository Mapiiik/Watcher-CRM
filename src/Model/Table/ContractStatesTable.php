<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\AppUser;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use Override;

/**
 * ContractStates Model
 *
 * @property \App\Model\Table\ContractsTable&\Cake\ORM\Association\HasMany $Contracts
 * @property \App\Model\Table\TaskTypesTable&\Cake\ORM\Association\BelongsTo $RequiresOpenTaskTypes
 * @method \App\Model\Entity\ContractState newEmptyEntity()
 * @method \App\Model\Entity\ContractState newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\ContractState[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\ContractState get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\ContractState findOrCreate($search, callable|array|null $callback = null, $options = [])
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
    #[Override]
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

        $this->belongsTo('RequiresOpenTaskTypes', [
            'className' => 'TaskTypes',
            'foreignKey' => 'requires_open_task_type_id',
//            'propertyName' => 'requires_open_task_type',
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
            ->boolean('show_on_dashboard')
            ->notEmptyString('show_on_dashboard');

        $validator
            ->allowEmptyArray('dashboard_roles')
            ->add('dashboard_roles', 'validRoles', [
                'rule' => function ($value): bool {
                    if (!is_array($value)) {
                        return false;
                    }
                    $roles = array_keys((new AppUser())->getRoleOptions());

                    return array_diff($value, $roles) === [];
                },
                'message' => __('One of the roles named is not a role.'),
            ]);

        $validator
            ->scalar('note')
            ->allowEmptyString('note');

        $validator
            ->boolean('usable_for_new_contract')
            ->notEmptyString('usable_for_new_contract');

        $validator
            ->uuid('requires_open_task_type_id')
            ->allowEmptyString('requires_open_task_type_id');

        $validator
            ->boolean('requires_no_open_tasks')
            ->notEmptyString('requires_no_open_tasks');

        $validator
            ->boolean('requires_no_active_billings')
            ->notEmptyString('requires_no_active_billings');

        $validator
            ->boolean('requires_no_future_billings')
            ->notEmptyString('requires_no_future_billings');

        $validator
            ->boolean('requires_no_assigned_ip_addresses_or_networks')
            ->notEmptyString('requires_no_assigned_ip_addresses_or_networks');

        $validator
            ->boolean('requires_no_active_radius_accounts')
            ->notEmptyString('requires_no_active_radius_accounts');

        $validator
            ->boolean('requires_no_borrowed_equipments')
            ->notEmptyString('requires_no_borrowed_equipments');

        $validator
            ->boolean('requires_installation_date')
            ->notEmptyString('requires_installation_date');

        $validator
            ->boolean('requires_uninstallation_date')
            ->notEmptyString('requires_uninstallation_date');

        $validator
            ->boolean('requires_termination_date')
            ->notEmptyString('requires_termination_date');

        $validator
            ->boolean('requires_contract_version')
            ->notEmptyString('requires_contract_version');

        $validator
            ->boolean('requires_active_contract_version')
            ->notEmptyString('requires_active_contract_version');

        $validator
            ->boolean('requires_active_or_future_contract_version')
            ->notEmptyString('requires_active_or_future_contract_version');

        $validator
            ->boolean('requires_no_active_or_future_contract_versions')
            ->notEmptyString('requires_no_active_or_future_contract_versions');

        $validator
            ->boolean('requires_no_active_obligations')
            ->notEmptyString('requires_no_active_obligations');

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
        $rules->add(
            $rules->existsIn(['requires_open_task_type_id'], 'RequiresOpenTaskTypes'),
            ['errorField' => 'requires_open_task_type_id'],
        );

        $rules->addDelete($rules->isNotLinkedTo('Contracts'));

        return $rules;
    }
}
