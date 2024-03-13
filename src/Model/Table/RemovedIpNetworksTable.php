<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Enum\IpNetworkTypeOfUse;
use Cake\Database\Type\EnumType;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

/**
 * RemovedIpNetworks Model
 *
 * @property \App\Model\Table\CustomersTable&\Cake\ORM\Association\BelongsTo $Customers
 * @property \App\Model\Table\ContractsTable&\Cake\ORM\Association\BelongsTo $Contracts
 * @method \App\Model\Entity\RemovedIpNetwork newEmptyEntity()
 * @method \App\Model\Entity\RemovedIpNetwork newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\RemovedIpNetwork[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\RemovedIpNetwork get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\RemovedIpNetwork findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\RemovedIpNetwork patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\RemovedIpNetwork[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\RemovedIpNetwork|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\RemovedIpNetwork saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<\App\Model\Entity\RemovedIpNetwork>|false saveMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\RemovedIpNetwork> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\RemovedIpNetwork>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\RemovedIpNetwork> deleteManyOrFail(iterable $entities, $options = [])
 */
class RemovedIpNetworksTable extends AppTable
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

        $this->setTable('removed_ip_networks');
        $this->setDisplayField('ip_network');
        $this->setPrimaryKey('id');

        $this->getSchema()->setColumnType(
            'type_of_use',
            EnumType::from(IpNetworkTypeOfUse::class)
        );

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
            ->uuid('customer_id')
            ->notEmptyString('customer_id');

        $validator
            ->uuid('contract_id')
            ->notEmptyString('contract_id');

        $validator
            ->scalar('ip_network')
            ->requirePresence('ip_network', 'create')
            ->notEmptyString('ip_network');

        $validator
            ->requirePresence('type_of_use', 'create')
            ->notEmptyString('type_of_use');

        $validator
            ->scalar('note')
            ->allowEmptyString('note');

        $validator
            ->dateTime('removed')
            ->requirePresence('removed', 'create')
            ->notEmptyDateTime('removed');

        $validator
            ->uuid('removed_by')
            ->requirePresence('removed_by', 'create')
            ->notEmptyString('removed_by');

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
        $rules->add($rules->existsIn('customer_id', 'Customers'), ['errorField' => 'customer_id']);
        $rules->add($rules->existsIn('contract_id', 'Contracts'), ['errorField' => 'contract_id']);

        return $rules;
    }
}
