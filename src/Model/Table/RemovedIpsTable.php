<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

/**
 * RemovedIps Model
 *
 * @property \App\Model\Table\CustomersTable&\Cake\ORM\Association\BelongsTo $Customers
 * @property \App\Model\Table\ContractsTable&\Cake\ORM\Association\BelongsTo $Contracts
 * @method \App\Model\Entity\RemovedIp newEmptyEntity()
 * @method \App\Model\Entity\RemovedIp newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\RemovedIp[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\RemovedIp get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\RemovedIp findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\RemovedIp patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\RemovedIp[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\RemovedIp|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\RemovedIp saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<\App\Model\Entity\RemovedIp>|false saveMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\RemovedIp> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\RemovedIp>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\RemovedIp> deleteManyOrFail(iterable $entities, $options = [])
 */
class RemovedIpsTable extends AppTable
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

        $this->setTable('removed_ips');
        $this->setDisplayField('ip');
        $this->setPrimaryKey('id');

        $this->addBehavior('StringModifications');

        $this->belongsTo('Customers', [
            'foreignKey' => 'customer_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Contracts', [
            'foreignKey' => 'contract_id',
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
            ->uuid('removed_by')
            ->notEmptyString('removed_by');

        $validator
            ->dateTime('removed')
            ->requirePresence('removed', 'create')
            ->notEmptyDateTime('removed');

        $validator
            ->ip('ip')
            ->requirePresence('ip', 'create')
            ->notEmptyString('ip');

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
        $rules->add($rules->existsIn(['customer_id'], 'Customers'), ['errorField' => 'customer_id']);
        $rules->add($rules->existsIn(['contract_id'], 'Contracts'), ['errorField' => 'contract_id']);

        return $rules;
    }
}
