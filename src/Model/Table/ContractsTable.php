<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Contract;
use App\Model\Entity\ServiceType;
use App\Model\Enum\AddressType;
use App\Model\Validation\ContractStateValidator;
use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use Override;

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
 * @property \App\Model\Table\IpAddressesTable&\Cake\ORM\Association\HasMany $IpAddresses
 * @property \App\Model\Table\RemovedIpAddressesTable&\Cake\ORM\Association\HasMany $RemovedIpAddresses
 * @property \App\Model\Table\IpNetworksTable&\Cake\ORM\Association\HasMany $IpNetworks
 * @property \App\Model\Table\RemovedIpNetworksTable&\Cake\ORM\Association\HasMany $RemovedIpNetworks
 * @property \App\Model\Table\SoldEquipmentsTable&\Cake\ORM\Association\HasMany $SoldEquipments
 * @property \App\Model\Table\TasksTable&\Cake\ORM\Association\HasMany $Tasks
 * @property \App\Model\Table\CustomerLabelsTable&\Cake\ORM\Association\HasMany $CustomerLabels
 * @method \App\Model\Entity\Contract newEmptyEntity()
 * @method \App\Model\Entity\Contract newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Contract[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Contract get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Contract findOrCreate($search, callable|array|null $callback = null, $options = [])
 * @method \App\Model\Entity\Contract patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Contract[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Contract|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Contract saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<\App\Model\Entity\Contract>|false saveMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\Contract> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\Contract>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\Contract> deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ContractsTable extends AppTable
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    #[Override]
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('contracts');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');
        // the customer's search document is built from these records
        $this->addBehavior('FulltextSearchCustomers');

        $this->belongsTo('Customers', [
            'foreignKey' => 'customer_id',
            'joinType' => 'INNER',
        ]);
        // An address that stops being of this type quietly stops being contained here, and
        // the contract reads as having none. `MissingInstallationAddressCheck` reports that.
        $this->belongsTo('InstallationAddresses', [
            'className' => 'Addresses',
            'foreignKey' => 'installation_address_id',
            'conditions' => ['InstallationAddresses.type' => AddressType::Installation],
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
        $this->hasMany('AccessCredentials', [
            'foreignKey' => 'contract_id',
            'sort' => [
                'AccessCredentials.name',
            ],
        ]);
        $this->hasMany('BorrowedEquipments', [
            'foreignKey' => 'contract_id',
            'sort' => [
                'BorrowedEquipments.borrowed_from' => 'DESC',
                'BorrowedEquipments.id' => 'DESC',
            ],
        ]);
        $this->hasMany('ContractVersions', [
            'foreignKey' => 'contract_id',
            'sort' => [
                'ContractVersions.valid_from' => 'DESC',
            ],
        ]);
        $this->hasMany('IpAddresses', [
            'foreignKey' => 'contract_id',
            'sort' => [
                'IpAddresses.ip_address',
            ],
        ]);
        $this->hasMany('RemovedIpAddresses', [
            'foreignKey' => 'contract_id',
            'sort' => [
                'RemovedIpAddresses.ip_address',
            ],
        ]);
        $this->hasMany('IpNetworks', [
            'foreignKey' => 'contract_id',
            'sort' => [
                'IpNetworks.ip_network',
            ],
        ]);
        $this->hasMany('RemovedIpNetworks', [
            'foreignKey' => 'contract_id',
            'sort' => [
                'RemovedIpNetworks.ip_network',
            ],
        ]);
        $this->hasMany('SoldEquipments', [
            'foreignKey' => 'contract_id',
            'sort' => [
                'SoldEquipments.date_of_sale' => 'DESC',
                'SoldEquipments.id' => 'DESC',
            ],
        ]);
        $this->hasMany('Tasks', [
            'foreignKey' => 'contract_id',
            'sort' => [
                'TaskStates.priority' => 'DESC',
                'Tasks.priority' => 'DESC',
                'Tasks.nid' => 'DESC',
            ],
        ]);
        $this->hasMany('CustomerLabels', [
            'foreignKey' => 'customer_id',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    #[Override]
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->uuid('customer_id')
            ->requirePresence('customer_id', 'create')
            ->notEmptyString('customer_id');

        $validator
            ->uuid('installation_address_id')
            ->allowEmptyString('installation_address_id');

        $validator
            ->scalar('number')
            ->maxLength('number', 255)
            ->allowEmptyString('number')
            ->add('number', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('subscriber_verification_code')
            ->maxLength('subscriber_verification_code', 255)
            ->allowEmptyString('subscriber_verification_code')
            ->add('subscriber_verification_code', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->uuid('service_type_id')
            ->requirePresence('service_type_id', 'create')
            ->notEmptyString('service_type_id');

        $validator
            ->scalar('note')
            ->allowEmptyString('note');

        $validator
            ->boolean('vip')
            ->allowEmptyString('vip');

        $validator
            ->uuid('installation_technician_id')
            ->allowEmptyString('installation_technician_id');

        $validator
            ->uuid('uninstallation_technician_id')
            ->allowEmptyString('uninstallation_technician_id');

        $validator
            ->uuid('commission_id')
            ->allowEmptyString('commission_id');

        $validator
            ->date('installation_date')
            ->allowEmptyDate('installation_date');

        $validator
            ->date('uninstallation_date')
            ->allowEmptyDate('uninstallation_date');

        $validator
            ->date('termination_date')
            ->allowEmptyDate('termination_date');

        $validator
            ->scalar('access_description')
            ->allowEmptyString('access_description');

        $validator
            ->decimal('activation_fee')
            ->allowEmptyString('activation_fee');

        $validator
            ->decimal('activation_fee_with_obligation')
            ->allowEmptyString('activation_fee_with_obligation');

        $validator
            ->uuid('access_point_id')
            ->allowEmptyString('access_point_id');

        $validator
            ->uuid('contract_state_id')
            ->requirePresence('contract_state_id', 'create')
            ->notEmptyString('contract_state_id');

        return $validator;
    }

    /**
     * Contracts that are providing services, by the flag on the state they are in.
     *
     * Said here once rather than at each place that asks. Whether a contract counts as live
     * is a decision about the states, and something that reads it wants the same answer as
     * everything else - it is the difference between a customer we serve and one we merely
     * still have on record.
     *
     * The state is joined rather than contained, so a query that wants to show the state as
     * well should contain it and take the ids from here as a subquery instead.
     *
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Contract> $query Query to narrow.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Contract>
     */
    public function findWithActiveServices(SelectQuery $query): SelectQuery
    {
        return $query
            ->innerJoinWith('ContractStates')
            ->where(['ContractStates.active_services' => true]);
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    #[Override]
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['number']), ['errorField' => 'number']);
        $rules->add(
            $rules->isUnique(['subscriber_verification_code']),
            ['errorField' => 'subscriber_verification_code'],
        );
        $rules->add(
            $rules->existsIn(['customer_id'], 'Customers'),
            ['errorField' => 'customer_id'],
        );
        $rules->add(
            $rules->existsIn(['installation_address_id'], 'InstallationAddresses'),
            ['errorField' => 'installation_address_id'],
        );
        $rules->add(
            $rules->existsIn(['service_type_id'], 'ServiceTypes'),
            ['errorField' => 'service_type_id'],
        );
        $rules->add(
            $rules->existsIn(['installation_technician_id'], 'InstallationTechnicians'),
            ['errorField' => 'installation_technician_id'],
        );
        $rules->add(
            $rules->existsIn(['uninstallation_technician_id'], 'UninstallationTechnicians'),
            ['errorField' => 'uninstallation_technician_id'],
        );
        $rules->add($rules->existsIn(['commission_id'], 'Commissions'), ['errorField' => 'commission_id']);
        $rules->add($rules->existsIn(['contract_state_id'], 'ContractStates'), ['errorField' => 'contract_state_id']);

        $rules->add(
            function ($entity, $_options): bool {
                // load service type
                $service_type = $this->findServiceType($entity);
                // a type that is not there is for existsIn above to report, not for this rule
                if ($service_type === null) {
                    return true;
                }

                // check if installation adress required for this service type
                if ($service_type->installation_address_required) {
                    return !empty($entity->installation_address_id);
                }

                return true;
            },
            'isRequiredInstallationAdressFilled',
            [
                'errorField' => 'installation_address_id',
                'message' => __('The specified service type requires the assignment of an installation address.'),
            ],
        );

        $rules->add(
            function ($entity, $_options): bool {
                // load service type
                $service_type = $this->findServiceType($entity);
                // a type that is not there is for existsIn above to report, not for this rule
                if ($service_type === null) {
                    return true;
                }

                // check if access point required for this service type
                if ($service_type->access_point_required) {
                    return !empty($entity->access_point_id);
                }

                return true;
            },
            'isRequiredAccessPointFilled',
            [
                'errorField' => 'access_point_id',
                'message' => __('The specified service type requires the assignment of an access point.'),
            ],
        );

        $rules->addDelete($rules->isNotLinkedTo('AccessCredentials'));
        $rules->addDelete($rules->isNotLinkedTo('Billings'));
        $rules->addDelete($rules->isNotLinkedTo('BorrowedEquipments'));
        $rules->addDelete($rules->isNotLinkedTo('ContractVersions'));
        $rules->addDelete($rules->isNotLinkedTo('IpAddresses'));
        $rules->addDelete($rules->isNotLinkedTo('RemovedIpAddresses'));
        $rules->addDelete($rules->isNotLinkedTo('IpNetworks'));
        $rules->addDelete($rules->isNotLinkedTo('RemovedIpNetworks'));
        $rules->addDelete($rules->isNotLinkedTo('SoldEquipments'));
        $rules->addDelete($rules->isNotLinkedTo('Tasks'));
        $rules->addDelete($rules->isNotLinkedTo('CustomerLabels'));

        return $rules;
    }

    /**
     * The service type a contract names, or null where it names none or one that is not there.
     *
     * The rules asking what a service type requires run whatever the `existsIn` above made of the
     * same field - a checker runs every rule it holds rather than stopping at the first one to
     * fail. Reading the type with `get()` therefore threw out of the rules rather than failing
     * them, and a caller waiting for a `false` got an exception instead.
     *
     * @param \Cake\Datasource\EntityInterface $entity The contract being saved.
     * @return \App\Model\Entity\ServiceType|null
     */
    private function findServiceType(EntityInterface $entity): ?ServiceType
    {
        $service_type_id = $entity->get('service_type_id');
        if ($service_type_id === null) {
            return null;
        }

        /** @var \App\Model\Entity\ServiceType|null $service_type */
        $service_type = $this->ServiceTypes->find()
            ->where(['ServiceTypes.id' => $service_type_id])
            ->first();

        return $service_type;
    }

    /**
     * Validate contract state flags before saving
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Event
     * @param \Cake\Datasource\EntityInterface $entity Entity
     * @param \ArrayObject<string, mixed> $options Options
     * @psalm-suppress PossiblyUnusedParam
     * @return void
     */
    public function beforeSave(
        EventInterface $event,
        EntityInterface $entity,
        ArrayObject $options,
    ): void {
        if (
            $entity instanceof Contract
            && empty($options['skipContractStateValidation'])
        ) {
            $errors = (new ContractStateValidator())->validate($entity);

            if ($errors) {
                $entity->setErrors($errors);

                $event->stopPropagation();
                $event->setResult(false);
            }
        }
    }
}
