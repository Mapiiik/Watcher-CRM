<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

/**
 * IpAddresses Model
 *
 * @property \App\Model\Table\CustomersTable&\Cake\ORM\Association\BelongsTo $Customers
 * @property \App\Model\Table\ContractsTable&\Cake\ORM\Association\BelongsTo $Contracts
 * @method \App\Model\Entity\IpAddress newEmptyEntity()
 * @method \App\Model\Entity\IpAddress newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\IpAddress[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\IpAddress get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\IpAddress findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\IpAddress patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\IpAddress[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\IpAddress|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\IpAddress saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<\App\Model\Entity\IpAddress>|false saveMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\IpAddress> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\IpAddress>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\IpAddress> deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class IpAddressesTable extends AppTable
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

        $this->setTable('ip_addresses');
        $this->setDisplayField('ip_address');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');

        $this->belongsTo('Customers', [
            'foreignKey' => 'customer_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Contracts', [
            'foreignKey' => 'contract_id',
            'joinType' => 'INNER',
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
            ->uuid('customer_id')
            ->notEmptyString('customer_id');

        $validator
            ->uuid('contract_id')
            ->notEmptyString('contract_id');

        $validator
            ->ip('ip_address')
            ->requirePresence('ip_address', 'create')
            ->notEmptyString('ip_address')
            ->add('ip_address', 'unique', [
                'rule' => 'validateUnique',
                'provider' => 'table',
                'message' => __('This IP address is already in use.'),
            ]);

        $validator
            ->scalar('note')
            ->allowEmptyString('note');

        $validator
            ->integer('type_of_use')
            ->requirePresence('type_of_use')
            ->notEmptyString('type_of_use');

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
        $rules->add($rules->isUnique(['ip_address']), ['errorField' => 'ip_address']);
        $rules->add($rules->existsIn(['customer_id'], 'Customers'), ['errorField' => 'customer_id']);
        $rules->add($rules->existsIn(['contract_id'], 'Contracts'), ['errorField' => 'contract_id']);

        return $rules;
    }
}
