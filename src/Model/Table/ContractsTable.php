<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Contracts Model
 *
 * @property \App\Model\Table\CustomersTable&\Cake\ORM\Association\BelongsTo $Customers
 * @property \App\Model\Table\InstallationAddressesTable&\Cake\ORM\Association\BelongsTo $InstallationAddresses
 * @property \App\Model\Table\ServiceTypesTable&\Cake\ORM\Association\BelongsTo $ServiceTypes
 * @property \App\Model\Table\InstallationTechniciansTable&\Cake\ORM\Association\BelongsTo $InstallationTechnicians
 * @property \App\Model\Table\BrokeragesTable&\Cake\ORM\Association\BelongsTo $Brokerages
 * @property \App\Model\Table\BillingsTable&\Cake\ORM\Association\HasMany $Billings
 * @property \App\Model\Table\BorrowedEquipmentsTable&\Cake\ORM\Association\HasMany $BorrowedEquipments
 * @property \App\Model\Table\IpsTable&\Cake\ORM\Association\HasMany $Ips
 * @property \App\Model\Table\RemovedIpsTable&\Cake\ORM\Association\HasMany $RemovedIps
 * @property \App\Model\Table\SoldEquipmentsTable&\Cake\ORM\Association\HasMany $SoldEquipments
 *
 * @method \App\Model\Entity\Contract newEmptyEntity()
 * @method \App\Model\Entity\Contract newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Contract[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Contract get($primaryKey, $options = [])
 * @method \App\Model\Entity\Contract findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Contract patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Contract[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Contract|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Contract saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Contract[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Contract[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Contract[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Contract[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ContractsTable extends Table
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

        $this->setTable('contracts');
        $this->setDisplayField('number');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');

        $this->belongsTo('Customers', [
            'foreignKey' => 'customer_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('InstallationAddresses', [
            'className' => 'Addresses',
            'foreignKey' => 'installation_address_id',
//            'propertyName' => 'installation_address',
            'conditions' => ['InstallationAddresses.type' => 0],
        ]);
        $this->belongsTo('ServiceTypes', [
            'foreignKey' => 'service_type_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('InstallationTechnicians', [
            'className' => 'Customers',
            'foreignKey' => 'installation_technician_id',
//            'propertyName' => 'installation_technician',
            'conditions' => ['InstallationTechnicians.dealer' => 1],
        ]);
        $this->belongsTo('Brokerages', [
            'foreignKey' => 'brokerage_id',
        ]);
        $this->hasMany('Billings', [
            'foreignKey' => 'contract_id',
        ]);
        $this->hasMany('BorrowedEquipments', [
            'foreignKey' => 'contract_id',
        ]);
        $this->hasMany('Ips', [
            'foreignKey' => 'contract_id',
        ]);
        $this->hasMany('RemovedIps', [
            'foreignKey' => 'contract_id',
        ]);
        $this->hasMany('SoldEquipments', [
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
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('number')
            ->allowEmptyString('number')
            ->add('number', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->integer('created_by')
            ->notEmptyString('created_by');

        $validator
            ->integer('modified_by')
            ->allowEmptyString('modified_by');

        $validator
            ->scalar('note')
            ->allowEmptyString('note');

        $validator
            ->date('obligation_until')
            ->allowEmptyDate('obligation_until');

        $validator
            ->boolean('vip')
            ->allowEmptyString('vip');

        $validator
            ->date('installation_date')
            ->allowEmptyDate('installation_date');

        $validator
            ->scalar('access_description')
            ->allowEmptyString('access_description');

        $validator
            ->date('valid_from')
            ->allowEmptyDate('valid_from');

        $validator
            ->date('valid_until')
            ->allowEmptyDate('valid_until');

        $validator
            ->date('conclusion_date')
            ->allowEmptyDate('conclusion_date');

        $validator
            ->integer('number_of_amendments')
            ->allowEmptyString('number_of_amendments');

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
        $rules->add($rules->isUnique(['number']), ['errorField' => 'number']);
        $rules->add($rules->existsIn(['customer_id'], 'Customers'), ['errorField' => 'customer_id']);
        $rules->add($rules->existsIn(['installation_address_id'], 'InstallationAddresses'), ['errorField' => 'installation_address_id']);
        $rules->add($rules->existsIn(['service_type_id'], 'ServiceTypes'), ['errorField' => 'service_type_id']);
        $rules->add($rules->existsIn(['installation_technician_id'], 'InstallationTechnicians'), ['errorField' => 'installation_technician_id']);
        $rules->add($rules->existsIn(['brokerage_id'], 'Brokerages'), ['errorField' => 'brokerage_id']);

        return $rules;
    }
}
