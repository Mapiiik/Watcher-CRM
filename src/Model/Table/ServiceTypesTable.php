<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

/**
 * ServiceTypes Model
 *
 * @property \App\Model\Table\ContractsTable&\Cake\ORM\Association\HasMany $Contracts
 * @property \App\Model\Table\ServicesTable&\Cake\ORM\Association\HasMany $Services
 * @method \App\Model\Entity\ServiceType newEmptyEntity()
 * @method \App\Model\Entity\ServiceType newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\ServiceType[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\ServiceType get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\ServiceType findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\ServiceType patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\ServiceType[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\ServiceType|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\ServiceType saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<\App\Model\Entity\ServiceType>|false saveMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\ServiceType> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\ServiceType>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\ServiceType> deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ServiceTypesTable extends AppTable
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

        $this->setTable('service_types');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');

        $this->hasMany('Contracts', [
            'foreignKey' => 'service_type_id',
        ]);
        $this->hasMany('Services', [
            'foreignKey' => 'service_type_id',
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
            ->allowEmptyString('name');

        $validator
            ->scalar('contract_number_format')
            ->maxLength('contract_number_format', 255)
            ->allowEmptyString('contract_number_format');

        $validator
            ->scalar('subscriber_verification_code_format')
            ->maxLength('subscriber_verification_code_format', 255)
            ->allowEmptyString('subscriber_verification_code_format');

        $validator
            ->integer('activation_fee')
            ->allowEmptyString('activation_fee');

        $validator
            ->integer('activation_fee_with_obligation')
            ->allowEmptyString('activation_fee_with_obligation');

        $validator
            ->boolean('separate_invoice')
            ->notEmptyString('separate_invoice');

        $validator
            ->boolean('invoice_with_items')
            ->notEmptyString('invoice_with_items');

        $validator
            ->scalar('invoice_text')
            ->maxLength('invoice_text', 255)
            ->allowEmptyString('invoice_text');

        $validator
            ->boolean('installation_address_required')
            ->notEmptyString('installation_address_required');

        $validator
            ->boolean('access_point_required')
            ->notEmptyString('access_point_required');

        $validator
            ->boolean('normally_with_borrowed_equipment')
            ->notEmptyString('normally_with_borrowed_equipment');

        $validator
            ->boolean('have_contract_versions')
            ->notEmptyString('have_contract_versions');

        $validator
            ->boolean('have_equipments')
            ->notEmptyString('have_equipments');

        $validator
            ->boolean('have_ip_addresses')
            ->notEmptyString('have_ip_addresses');

        $validator
            ->boolean('have_radius_accounts')
            ->notEmptyString('have_radius_accounts');

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
        $rules->addDelete($rules->isNotLinkedTo('Services'));

        return $rules;
    }
}
