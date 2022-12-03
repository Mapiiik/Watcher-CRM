<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

/**
 * Contracts Model
 *
 * @property \App\Model\Table\CustomersTable&\Cake\ORM\Association\BelongsTo $Customers
 * @property \App\Model\Table\AddressesTable&\Cake\ORM\Association\BelongsTo $InstallationAddresses
 * @property \App\Model\Table\ServiceTypesTable&\Cake\ORM\Association\BelongsTo $ServiceTypes
 * @property \App\Model\Table\CustomersTable&\Cake\ORM\Association\BelongsTo $InstallationTechnicians
 * @property \App\Model\Table\CustomersTable&\Cake\ORM\Association\BelongsTo $UninstallationTechnicians
 * @property \App\Model\Table\CommissionsTable&\Cake\ORM\Association\BelongsTo $Commissions
 * @property \App\Model\Table\ContractStatesTable&\Cake\ORM\Association\BelongsTo $ContractStates
 * @property \App\Model\Table\BillingsTable&\Cake\ORM\Association\HasMany $Billings
 * @property \App\Model\Table\BorrowedEquipmentsTable&\Cake\ORM\Association\HasMany $BorrowedEquipments
 * @property \App\Model\Table\ContractVersionsTable&\Cake\ORM\Association\HasMany $ContractVersions
 * @property \App\Model\Table\IpsTable&\Cake\ORM\Association\HasMany $Ips
 * @property \App\Model\Table\RemovedIpsTable&\Cake\ORM\Association\HasMany $RemovedIps
 * @property \App\Model\Table\IpNetworksTable&\Cake\ORM\Association\HasMany $IpNetworks
 * @property \App\Model\Table\RemovedIpNetworksTable&\Cake\ORM\Association\HasMany $RemovedIpNetworks
 * @property \App\Model\Table\SoldEquipmentsTable&\Cake\ORM\Association\HasMany $SoldEquipments
 * @property \App\Model\Table\TasksTable&\Cake\ORM\Association\HasMany $Tasks
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
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ContractsTable extends AppTable
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
        $this->setDisplayField('name');
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
            'conditions' => ['InstallationAddresses.type' => 0],
        ]);
        $this->belongsTo('ServiceTypes', [
            'foreignKey' => 'service_type_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('InstallationTechnicians', [
            'className' => 'Customers',
            'foreignKey' => 'installation_technician_id',
            'conditions' => ['InstallationTechnicians.dealer IN' => [1, 2]],
        ]);
        $this->belongsTo('UninstallationTechnicians', [
            'className' => 'Customers',
            'foreignKey' => 'uninstallation_technician_id',
            'conditions' => ['UninstallationTechnicians.dealer IN' => [1, 2]],
        ]);
        $this->belongsTo('Commissions', [
            'foreignKey' => 'commission_id',
        ]);
        $this->belongsTo('ContractStates', [
            'foreignKey' => 'contract_state_id',
            'joinType' => 'INNER',
        ]);
        $this->hasMany('Billings', [
            'foreignKey' => 'contract_id',
            'sort' => [
                'Billings.billing_from' => 'DESC',
            ],
        ]);
        $this->hasMany('BorrowedEquipments', [
            'foreignKey' => 'contract_id',
        ]);
        $this->hasMany('ContractVersions', [
            'foreignKey' => 'contract_id',
        ]);
        $this->hasMany('Ips', [
            'foreignKey' => 'contract_id',
        ]);
        $this->hasMany('RemovedIps', [
            'foreignKey' => 'contract_id',
        ]);
        $this->hasMany('IpNetworks', [
            'foreignKey' => 'contract_id',
        ]);
        $this->hasMany('RemovedIpNetworks', [
            'foreignKey' => 'contract_id',
        ]);
        $this->hasMany('SoldEquipments', [
            'foreignKey' => 'contract_id',
        ]);
        $this->hasMany('Tasks', [
            'foreignKey' => 'contract_id',
            'sort' => [
                'Tasks.task_state_id',
                'Tasks.id' => 'DESC',
            ],
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
            ->integer('customer_id')
            ->notEmptyString('customer_id');

        $validator
            ->integer('installation_address_id')
            ->allowEmptyString('installation_address_id');

        $validator
            ->scalar('number')
            ->maxLength('number', 255)
            ->allowEmptyString('number')
            ->add('number', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->integer('service_type_id')
            ->notEmptyString('service_type_id');

        $validator
            ->scalar('note')
            ->allowEmptyString('note');

        $validator
            ->boolean('vip')
            ->allowEmptyString('vip');

        $validator
            ->integer('installation_technician_id')
            ->allowEmptyString('installation_technician_id');

        $validator
            ->integer('uninstallation_technician_id')
            ->allowEmptyString('uninstallation_technician_id');

        $validator
            ->integer('commission_id')
            ->allowEmptyString('commission_id');

        $validator
            ->date('installation_date')
            ->allowEmptyDate('installation_date');

        $validator
            ->date('uninstallation_date')
            ->allowEmptyDate('uninstallation_date');

        $validator
            ->scalar('access_description')
            ->allowEmptyString('access_description');

        $validator
            ->integer('activation_fee')
            ->allowEmptyString('activation_fee');

        $validator
            ->integer('activation_fee_with_obligation')
            ->allowEmptyString('activation_fee_with_obligation');

        $validator
            ->uuid('access_point_id')
            ->allowEmptyString('access_point_id');

        $validator
            ->uuid('contract_state_id')
            ->notEmptyString('contract_state_id');

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
        $rules->add(
            $rules->existsIn(['installation_address_id'], 'InstallationAddresses'),
            ['errorField' => 'installation_address_id']
        );
        $rules->add(
            $rules->existsIn(['service_type_id'], 'ServiceTypes'),
            ['errorField' => 'service_type_id']
        );
        $rules->add(
            $rules->existsIn(['installation_technician_id'], 'InstallationTechnicians'),
            ['errorField' => 'installation_technician_id']
        );
        $rules->add(
            $rules->existsIn(['uninstallation_technician_id'], 'UninstallationTechnicians'),
            ['errorField' => 'uninstallation_technician_id']
        );
        $rules->add($rules->existsIn(['commission_id'], 'Commissions'), ['errorField' => 'commission_id']);
        $rules->add($rules->existsIn(['contract_state_id'], 'ContractStates'), ['errorField' => 'contract_state_id']);

        $rules->add(
            function ($entity, $options) {
                // load service type
                $service_type = $this->ServiceTypes->get($entity->service_type_id);
                // check if installation adress required for this service type
                if ($service_type->installation_address_required) {
                    return is_numeric($entity->installation_address_id);
                } else {
                    return true;
                }
            },
            'isRequiredInstallationAdressFilled',
            [
                'errorField' => 'installation_address_id',
                'message' => __('The specified service type requires the assignment of an installation address.'),
            ]
        );

        $rules->add(
            function ($entity, $options) {
                // load service type
                $service_type = $this->ServiceTypes->get($entity->service_type_id);
                // check if access point required for this service type
                if ($service_type->access_point_required) {
                    return !empty($entity->access_point_id);
                } else {
                    return true;
                }
            },
            'isRequiredAccessPointFilled',
            [
                'errorField' => 'access_point_id',
                'message' => __('The specified service type requires the assignment of an access point.'),
            ]
        );

        return $rules;
    }
}
