<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Logins Model
 *
 * @property \App\Model\Table\CustomersTable&\Cake\ORM\Association\BelongsTo $Customers
 * @property array $rights
 * @method \App\Model\Entity\Login newEmptyEntity()
 * @method \App\Model\Entity\Login newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Login[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Login get($primaryKey, $options = [])
 * @method \App\Model\Entity\Login findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Login patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Login[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Login|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Login saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Login[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Login[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Login[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Login[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class LoginsTable extends Table
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

        $this->rights = [
            '0' => __('Guest'),
            '1' => __('User'),
            '2' => __('Technician'),
            '3' => __('Administrator'),
            '4' => __('Specialist'),
        ];

        $this->setTable('logins');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');

        $this->belongsTo('Customers', [
            'foreignKey' => 'customer_id',
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
            ->scalar('login')
            ->requirePresence('login', 'create')
            ->notEmptyString('login')
            ->add('login', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('password')
            ->requirePresence('password', 'create')
            ->notEmptyString('password');

        $validator
            ->notEmptyString('rights');

        $validator
            ->notEmptyString('locked');

        $validator
            ->dateTime('last_granted')
            ->allowEmptyDateTime('last_granted');

        $validator
            ->scalar('last_granted_ip')
            ->maxLength('last_granted_ip', 39)
            ->allowEmptyString('last_granted_ip');

        $validator
            ->dateTime('last_denied')
            ->allowEmptyDateTime('last_denied');

        $validator
            ->scalar('last_denied_ip')
            ->maxLength('last_denied_ip', 39)
            ->allowEmptyString('last_denied_ip');

        $validator
            ->integer('modified_by')
            ->notEmptyString('modified_by');

        $validator
            ->integer('created_by')
            ->notEmptyString('created_by');

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
        $rules->add($rules->isUnique(['login']), ['errorField' => 'login']);
        $rules->add($rules->existsIn(['customer_id'], 'Customers'), ['errorField' => 'customer_id']);

        return $rules;
    }
}
