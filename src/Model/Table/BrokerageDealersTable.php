<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * BrokerageDealers Model
 *
 * @property \App\Model\Table\DealersTable&\Cake\ORM\Association\BelongsTo $Dealers
 * @property \App\Model\Table\BrokeragesTable&\Cake\ORM\Association\BelongsTo $Brokerages
 *
 * @method \App\Model\Entity\BrokerageDealer newEmptyEntity()
 * @method \App\Model\Entity\BrokerageDealer newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\BrokerageDealer[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\BrokerageDealer get($primaryKey, $options = [])
 * @method \App\Model\Entity\BrokerageDealer findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\BrokerageDealer patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\BrokerageDealer[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\BrokerageDealer|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\BrokerageDealer saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\BrokerageDealer[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\BrokerageDealer[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\BrokerageDealer[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\BrokerageDealer[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class BrokerageDealersTable extends Table
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

        $this->setTable('brokerage_dealers');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');

        $this->belongsTo('Dealers', [
            'className' => 'Customers',
            'foreignKey' => 'dealer_id',
            'conditions' => ['Dealers.dealer' => 1],
        ]);
        $this->belongsTo('Brokerages', [
            'foreignKey' => 'brokerage_id',
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
            ->numeric('fixed')
            ->allowEmptyString('fixed');

        $validator
            ->numeric('percentage')
            ->allowEmptyString('percentage');

        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create')
            ->add('id', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

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
        $rules->add($rules->existsIn(['dealer_id'], 'Dealers'), ['errorField' => 'dealer_id']);
        $rules->add($rules->existsIn(['brokerage_id'], 'Brokerages'), ['errorField' => 'brokerage_id']);

        return $rules;
    }
}
