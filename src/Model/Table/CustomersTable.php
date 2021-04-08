<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Customers Model
 *
 * @property \App\Model\Table\TaxesTable&\Cake\ORM\Association\BelongsTo $Taxes
 * @property \App\Model\Table\AddressesTable&\Cake\ORM\Association\HasMany $Addresses
 * @property \App\Model\Table\BillingsTable&\Cake\ORM\Association\HasMany $Billings
 * @property \App\Model\Table\BorrowedEquipmentsTable&\Cake\ORM\Association\HasMany $BorrowedEquipments
 * @property \App\Model\Table\ContractsTable&\Cake\ORM\Association\HasMany $Contracts
 * @property \App\Model\Table\EmailsTable&\Cake\ORM\Association\HasMany $Emails
 * @property \App\Model\Table\IpsTable&\Cake\ORM\Association\HasMany $Ips
 * @property \App\Model\Table\LabelCustomersTable&\Cake\ORM\Association\HasMany $LabelCustomers
 * @property \App\Model\Table\LoginsTable&\Cake\ORM\Association\HasMany $Logins
 * @property \App\Model\Table\PhonesTable&\Cake\ORM\Association\HasMany $Phones
 * @property \App\Model\Table\RemovedIpsTable&\Cake\ORM\Association\HasMany $RemovedIps
 * @property \App\Model\Table\SoldEquipmentsTable&\Cake\ORM\Association\HasMany $SoldEquipments
 * @property \App\Model\Table\TasksTable&\Cake\ORM\Association\HasMany $Tasks
 *
 * @method \App\Model\Entity\Customer newEmptyEntity()
 * @method \App\Model\Entity\Customer newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Customer[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Customer get($primaryKey, $options = [])
 * @method \App\Model\Entity\Customer findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Customer patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Customer[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Customer|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Customer saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Customer[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Customer[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Customer[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Customer[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class CustomersTable extends Table
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

        $this->setTable('customers');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Taxes', [
            'foreignKey' => 'taxe_id',
            'joinType' => 'INNER',
        ]);
        $this->hasMany('Addresses', [
            'foreignKey' => 'customer_id',
        ]);
        $this->hasMany('Billings', [
            'foreignKey' => 'customer_id',
        ]);
        $this->hasMany('BorrowedEquipments', [
            'foreignKey' => 'customer_id',
        ]);
        $this->hasMany('Contracts', [
            'foreignKey' => 'customer_id',
        ]);
        $this->hasMany('Emails', [
            'foreignKey' => 'customer_id',
        ]);
        $this->hasMany('Ips', [
            'foreignKey' => 'customer_id',
        ]);
        $this->hasMany('LabelCustomers', [
            'foreignKey' => 'customer_id',
        ]);
        $this->hasMany('Logins', [
            'foreignKey' => 'customer_id',
        ]);
        $this->hasMany('Phones', [
            'foreignKey' => 'customer_id',
        ]);
        $this->hasMany('RemovedIps', [
            'foreignKey' => 'customer_id',
        ]);
        $this->hasMany('SoldEquipments', [
            'foreignKey' => 'customer_id',
        ]);
        $this->hasMany('Tasks', [
            'foreignKey' => 'customer_id',
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
            ->notEmptyString('dealer');

        $validator
            ->scalar('title')
            ->allowEmptyString('title');

        $validator
            ->scalar('first_name')
            ->allowEmptyString('first_name');

        $validator
            ->scalar('last_name')
            ->allowEmptyString('last_name');

        $validator
            ->scalar('suffix')
            ->allowEmptyString('suffix');

        $validator
            ->scalar('company')
            ->allowEmptyString('company');

        $validator
            ->scalar('bank_name')
            ->allowEmptyString('bank_name');

        $validator
            ->scalar('bank_account')
            ->allowEmptyString('bank_account');

        $validator
            ->scalar('bank_code')
            ->maxLength('bank_code', 4)
            ->allowEmptyString('bank_code');

        $validator
            ->integer('modified_by')
            ->notEmptyString('modified_by');

        $validator
            ->integer('created_by')
            ->notEmptyString('created_by');

        $validator
            ->scalar('ic')
            ->maxLength('ic', 12)
            ->allowEmptyString('ic');

        $validator
            ->scalar('dic')
            ->maxLength('dic', 15)
            ->allowEmptyString('dic');

        $validator
            ->scalar('www')
            ->allowEmptyString('www');

        $validator
            ->scalar('internal_note')
            ->allowEmptyString('internal_note');

        $validator
            ->notEmptyString('invoice_delivery');

        $validator
            ->scalar('note')
            ->allowEmptyString('note');

        $validator
            ->scalar('identity_card_number')
            ->maxLength('identity_card_number', 12)
            ->allowEmptyString('identity_card_number');

        $validator
            ->date('date_of_birth')
            ->allowEmptyDate('date_of_birth');

        $validator
            ->date('termination_date')
            ->allowEmptyDate('termination_date');

        $validator
            ->boolean('agree_gdpr')
            ->allowEmptyString('agree_gdpr');

        $validator
            ->boolean('agree_mailing_outages')
            ->allowEmptyString('agree_mailing_outages');

        $validator
            ->boolean('agree_mailing_commercial')
            ->allowEmptyString('agree_mailing_commercial');

        $validator
            ->boolean('agree_mailing_billing')
            ->allowEmptyString('agree_mailing_billing');

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
        $rules->add($rules->existsIn(['taxe_id'], 'Taxes'), ['errorField' => 'taxe_id']);

        return $rules;
    }
}
