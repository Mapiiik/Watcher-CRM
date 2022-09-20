<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

/**
 * DealerCommissions Model
 *
 * @property \App\Model\Table\CustomersTable&\Cake\ORM\Association\BelongsTo $Dealers
 * @property \App\Model\Table\CommissionsTable&\Cake\ORM\Association\BelongsTo $Commissions
 * @method \App\Model\Entity\DealerCommission newEmptyEntity()
 * @method \App\Model\Entity\DealerCommission newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\DealerCommission[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\DealerCommission get($primaryKey, $options = [])
 * @method \App\Model\Entity\DealerCommission findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\DealerCommission patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\DealerCommission[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\DealerCommission|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\DealerCommission saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\DealerCommission[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\DealerCommission[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\DealerCommission[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\DealerCommission[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class DealerCommissionsTable extends AppTable
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

        $this->setTable('dealer_commissions');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');

        $this->belongsTo('Dealers', [
            'className' => 'Customers',
            'foreignKey' => 'dealer_id',
            'conditions' => ['Dealers.dealer IN' => [1, 2]],
        ]);
        $this->belongsTo('Commissions', [
            'foreignKey' => 'commission_id',
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
        $rules->add($rules->existsIn(['commission_id'], 'Commissions'), ['errorField' => 'commission_id']);

        return $rules;
    }
}
