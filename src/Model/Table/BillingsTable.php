<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

/**
 * Billings Model
 *
 * @property \App\Model\Table\CustomersTable&\Cake\ORM\Association\BelongsTo $Customers
 * @property \App\Model\Table\ServicesTable&\Cake\ORM\Association\BelongsTo $Services
 * @property \App\Model\Table\ContractsTable&\Cake\ORM\Association\BelongsTo $Contracts
 * @method \App\Model\Entity\Billing newEmptyEntity()
 * @method \App\Model\Entity\Billing newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Billing[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Billing get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Billing findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Billing patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Billing[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Billing|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Billing saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<\App\Model\Entity\Billing>|false saveMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\Billing> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\Billing>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\Billing> deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class BillingsTable extends AppTable
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

        $this->setTable('billings');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');

        $this->belongsTo('Customers', [
            'foreignKey' => 'customer_id',
        ]);
        $this->belongsTo('Services', [
            'foreignKey' => 'service_id',
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
            ->scalar('text')
            ->allowEmptyString('text');

        $validator
            ->decimal('price')
            ->allowEmptyString('price');

        $validator
            ->decimal('fixed_discount')
            ->allowEmptyString('fixed_discount');

        $validator
            ->integer('percentage_discount')
            ->allowEmptyString('percentage_discount');

        $validator
            ->date('billing_from');

        $validator
            ->scalar('note')
            ->allowEmptyString('note');

        $validator
            ->date('billing_until')
            ->allowEmptyDate('billing_until');

        $validator
            ->boolean('separate_invoice')
            ->notEmptyString('separate_invoice');

        $validator
            ->integer('quantity')
            ->notEmptyString('quantity');

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
        $rules->add($rules->existsIn(['service_id'], 'Services'), ['errorField' => 'service_id']);
        $rules->add($rules->existsIn(['contract_id'], 'Contracts'), ['errorField' => 'contract_id']);

        return $rules;
    }
}
