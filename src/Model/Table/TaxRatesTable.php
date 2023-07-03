<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\Validation\Validator;

/**
 * TaxRates Model
 *
 * @property \App\Model\Table\CustomersTable&\Cake\ORM\Association\HasMany $Customers
 * @method \App\Model\Entity\TaxRate newEmptyEntity()
 * @method \App\Model\Entity\TaxRate newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\TaxRate[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\TaxRate get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\TaxRate findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\TaxRate patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\TaxRate[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\TaxRate|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\TaxRate saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\TaxRate[]|iterable<\Cake\Datasource\EntityInterface>|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\TaxRate[]|iterable<\Cake\Datasource\EntityInterface> saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\TaxRate[]|iterable<\Cake\Datasource\EntityInterface>|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\TaxRate[]|iterable<\Cake\Datasource\EntityInterface> deleteManyOrFail(iterable $entities, $options = [])
 */
class TaxRatesTable extends AppTable
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

        $this->setTable('tax_rates');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');

        $this->hasMany('Customers', [
            'foreignKey' => 'tax_rate_id',
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
            ->scalar('name')
            ->maxLength('name', 32)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->numeric('vat_rate')
            ->notEmptyString('vat_rate');

        $validator
            ->boolean('reverse_charge')
            ->notEmptyString('reverse_charge');

        $validator
            ->scalar('accounting_assignment_code')
            ->maxLength('accounting_assignment_code', 255)
            ->allowEmptyString('accounting_assignment_code');

        $validator
            ->scalar('bank_account_code')
            ->maxLength('bank_account_code', 255)
            ->allowEmptyString('bank_account_code');

        $validator
            ->scalar('activity_code')
            ->maxLength('activity_code', 255)
            ->allowEmptyString('activity_code');

        return $validator;
    }
}
