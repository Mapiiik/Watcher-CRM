<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Ips Model
 *
 * @property \App\Model\Table\CustomersTable&\Cake\ORM\Association\BelongsTo $Customers
 * @property \App\Model\Table\ContractsTable&\Cake\ORM\Association\BelongsTo $Contracts
 *
 * @method \App\Model\Entity\Ip newEmptyEntity()
 * @method \App\Model\Entity\Ip newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Ip[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Ip get($primaryKey, $options = [])
 * @method \App\Model\Entity\Ip findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Ip patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Ip[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Ip|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Ip saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Ip[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Ip[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Ip[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Ip[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class IpsTable extends Table
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

        $this->setTable('ips');
        $this->setDisplayField('ip');
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
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->integer('customer_id')
            ->notEmptyString('customer_id');

        $validator
            ->integer('contract_id')
            ->notEmptyString('contract_id');

        $validator
            ->scalar('ip')
            ->maxLength('ip', 39)
            ->requirePresence('ip', 'create')
            ->notEmptyString('ip')
            ->add('ip', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('note')
            ->allowEmptyString('note');

        $validator
            ->integer('created_by')
            ->allowEmptyString('created_by');

        $validator
            ->integer('modified_by')
            ->allowEmptyString('modified_by');

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
        $rules->add($rules->isUnique(['ip']), ['errorField' => 'ip']);
        $rules->add($rules->existsIn(['customer_id'], 'Customers'), ['errorField' => 'customer_id']);
        $rules->add($rules->existsIn(['contract_id'], 'Contracts'), ['errorField' => 'contract_id']);

        return $rules;
    }
}
