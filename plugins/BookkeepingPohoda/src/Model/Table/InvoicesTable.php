<?php
declare(strict_types=1);

namespace BookkeepingPohoda\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Invoices Model
 *
 * @property \BookkeepingPohoda\Model\Table\CustomersTable&\Cake\ORM\Association\BelongsTo $Customers
 *
 * @method \BookkeepingPohoda\Model\Entity\Invoice newEmptyEntity()
 * @method \BookkeepingPohoda\Model\Entity\Invoice newEntity(array $data, array $options = [])
 * @method \BookkeepingPohoda\Model\Entity\Invoice[] newEntities(array $data, array $options = [])
 * @method \BookkeepingPohoda\Model\Entity\Invoice get($primaryKey, $options = [])
 * @method \BookkeepingPohoda\Model\Entity\Invoice findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \BookkeepingPohoda\Model\Entity\Invoice patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \BookkeepingPohoda\Model\Entity\Invoice[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \BookkeepingPohoda\Model\Entity\Invoice|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \BookkeepingPohoda\Model\Entity\Invoice saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \BookkeepingPohoda\Model\Entity\Invoice[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \BookkeepingPohoda\Model\Entity\Invoice[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \BookkeepingPohoda\Model\Entity\Invoice[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \BookkeepingPohoda\Model\Entity\Invoice[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class InvoicesTable extends Table
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

        $this->setTable('invoices');
        $this->setDisplayField('number');
        $this->setPrimaryKey('id');

        $this->belongsTo('Customers', [
            'foreignKey' => 'customer_id',
            'className' => 'Customers',
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
            ->allowEmptyString('id', null, 'create');

        $validator
            ->requirePresence('number', 'create')
            ->notEmptyString('number');

        $validator
            ->integer('varsym')
            ->allowEmptyString('varsym');

        $validator
            ->date('date')
            ->allowEmptyDate('date');

        $validator
            ->date('maturity')
            ->allowEmptyDate('maturity');

        $validator
            ->scalar('text')
            ->allowEmptyString('text');

        $validator
            ->decimal('sum')
            ->allowEmptyString('sum');

        $validator
            ->decimal('debt')
            ->allowEmptyString('debt');

        $validator
            ->date('payment_date')
            ->allowEmptyDate('payment_date');

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

        return $rules;
    }
}