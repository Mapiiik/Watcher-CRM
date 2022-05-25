<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * TaxRates Model
 *
 * @property \App\Model\Table\CustomersTable&\Cake\ORM\Association\HasMany $Customers
 * @method \App\Model\Entity\TaxRate newEmptyEntity()
 * @method \App\Model\Entity\TaxRate newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\TaxRate[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\TaxRate get($primaryKey, $options = [])
 * @method \App\Model\Entity\TaxRate findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\TaxRate patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\TaxRate[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\TaxRate|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\TaxRate saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\TaxRate[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\TaxRate[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\TaxRate[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\TaxRate[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class TaxRatesTable extends Table
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

        return $validator;
    }
}
