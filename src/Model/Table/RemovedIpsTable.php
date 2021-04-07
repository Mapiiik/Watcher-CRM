<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * RemovedIps Model
 *
 * @property \App\Model\Table\CustomersTable&\Cake\ORM\Association\BelongsTo $Customers
 * @property \App\Model\Table\QueuesTable&\Cake\ORM\Association\BelongsTo $Queues
 * @property \App\Model\Table\DevicesTable&\Cake\ORM\Association\BelongsTo $Devices
 * @property \App\Model\Table\DealersTable&\Cake\ORM\Association\BelongsTo $Dealers
 * @property \App\Model\Table\BrokeragesTable&\Cake\ORM\Association\BelongsTo $Brokerages
 * @property \App\Model\Table\ContractsTable&\Cake\ORM\Association\BelongsTo $Contracts
 *
 * @method \App\Model\Entity\RemovedIp newEmptyEntity()
 * @method \App\Model\Entity\RemovedIp newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\RemovedIp[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\RemovedIp get($primaryKey, $options = [])
 * @method \App\Model\Entity\RemovedIp findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\RemovedIp patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\RemovedIp[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\RemovedIp|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\RemovedIp saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\RemovedIp[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\RemovedIp[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\RemovedIp[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\RemovedIp[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class RemovedIpsTable extends Table
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

        $this->setTable('removed_ips');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Customers', [
            'foreignKey' => 'customer_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Queues', [
            'foreignKey' => 'queue_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Devices', [
            'foreignKey' => 'device_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Dealers', [
            'foreignKey' => 'dealer_id',
        ]);
        $this->belongsTo('Brokerages', [
            'foreignKey' => 'brokerage_id',
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
            ->integer('id')
            ->allowEmptyString('id', null, 'create')
            ->add('id', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->integer('removed_by')
            ->notEmptyString('removed_by');

        $validator
            ->dateTime('removed')
            ->requirePresence('removed', 'create')
            ->notEmptyDateTime('removed');

        $validator
            ->scalar('ip')
            ->maxLength('ip', 39)
            ->requirePresence('ip', 'create')
            ->notEmptyString('ip');

        $validator
            ->scalar('mac')
            ->allowEmptyString('mac');

        $validator
            ->scalar('comment')
            ->allowEmptyString('comment');

        $validator
            ->integer('cost')
            ->allowEmptyString('cost');

        $validator
            ->date('installation_date')
            ->allowEmptyDate('installation_date');

        $validator
            ->date('billing_from')
            ->allowEmptyDate('billing_from');

        $validator
            ->scalar('note')
            ->allowEmptyString('note');

        $validator
            ->boolean('vip')
            ->notEmptyString('vip');

        $validator
            ->date('bond')
            ->allowEmptyDate('bond');

        $validator
            ->date('active_until')
            ->allowEmptyDate('active_until');

        $validator
            ->scalar('access_description')
            ->allowEmptyString('access_description');

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
        $rules->add($rules->isUnique(['id']), ['errorField' => 'id']);
        $rules->add($rules->existsIn(['customer_id'], 'Customers'), ['errorField' => 'customer_id']);
        $rules->add($rules->existsIn(['queue_id'], 'Queues'), ['errorField' => 'queue_id']);
        $rules->add($rules->existsIn(['device_id'], 'Devices'), ['errorField' => 'device_id']);
        $rules->add($rules->existsIn(['dealer_id'], 'Dealers'), ['errorField' => 'dealer_id']);
        $rules->add($rules->existsIn(['brokerage_id'], 'Brokerages'), ['errorField' => 'brokerage_id']);
        $rules->add($rules->existsIn(['contract_id'], 'Contracts'), ['errorField' => 'contract_id']);

        return $rules;
    }
}
