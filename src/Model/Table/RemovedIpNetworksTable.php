<?php
declare(strict_types=1);

namespace App\Model\Table;

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
 * @method \App\Model\Entity\RemovedIpNetwork get($primaryKey, $options = [])
 * @method \App\Model\Entity\RemovedIpNetwork findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\RemovedIpNetwork patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\RemovedIpNetwork[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\RemovedIpNetwork|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\RemovedIpNetwork saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\RemovedIpNetwork[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\RemovedIpNetwork[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\RemovedIpNetwork[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\RemovedIpNetwork[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class RemovedIpNetworksTable extends AppTable
{
    /**
     * Type of use
     *
     * @var array<string>
     */
    public $types_of_use = [];

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->types_of_use = [
            00 => __('Customer network set via RADIUS'),
            10 => __('Customer network set manually'),
            20 => __('Technology network set manually'),
        ];

        $this->setTable('removed_ip_networks');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

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
            ->integer('customer_id')
            ->notEmptyString('customer_id');

        $validator
            ->integer('contract_id')
            ->notEmptyString('contract_id');

        $validator
            ->scalar('ip_network')
            ->requirePresence('ip_network', 'create')
            ->notEmptyString('ip_network');

        $validator
            ->integer('type_of_use')
            ->notEmptyString('type_of_use');

        $validator
            ->scalar('note')
            ->allowEmptyString('note');

        $validator
            ->dateTime('removed')
            ->requirePresence('removed', 'create')
            ->notEmptyDateTime('removed');

        $validator
            ->integer('removed_by')
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
