<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

/**
 * Customers Model
 *
 * @property \App\Model\Table\TaxRatesTable&\Cake\ORM\Association\BelongsTo $TaxRates
 * @property \App\Model\Table\AddressesTable&\Cake\ORM\Association\HasMany $Addresses
 * @property \App\Model\Table\BillingsTable&\Cake\ORM\Association\HasMany $Billings
 * @property \App\Model\Table\BorrowedEquipmentsTable&\Cake\ORM\Association\HasMany $BorrowedEquipments
 * @property \App\Model\Table\ContractsTable&\Cake\ORM\Association\HasMany $Contracts
 * @property \App\Model\Table\EmailsTable&\Cake\ORM\Association\HasMany $Emails
 * @property \App\Model\Table\CustomerLabelsTable&\Cake\ORM\Association\HasMany $CustomerLabels
 * @property \App\Model\Table\LoginsTable&\Cake\ORM\Association\HasMany $Logins
 * @property \App\Model\Table\PhonesTable&\Cake\ORM\Association\HasMany $Phones
 * @property \App\Model\Table\IpsTable&\Cake\ORM\Association\HasMany $Ips
 * @property \App\Model\Table\RemovedIpsTable&\Cake\ORM\Association\HasMany $RemovedIps
 * @property \App\Model\Table\IpNetworksTable&\Cake\ORM\Association\HasMany $IpNetworks
 * @property \App\Model\Table\RemovedIpNetworksTable&\Cake\ORM\Association\HasMany $RemovedIpNetworks
 * @property \App\Model\Table\SoldEquipmentsTable&\Cake\ORM\Association\HasMany $SoldEquipments
 * @property \App\Model\Table\TasksTable&\Cake\ORM\Association\HasMany $Tasks
 * @method \App\Model\Entity\Customer newEmptyEntity()
 * @method \App\Model\Entity\Customer newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Customer[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Customer get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Customer findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Customer patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Customer[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Customer|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Customer saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Customer[]|iterable<\Cake\Datasource\EntityInterface>|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Customer[]|iterable<\Cake\Datasource\EntityInterface> saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Customer[]|iterable<\Cake\Datasource\EntityInterface>|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Customer[]|iterable<\Cake\Datasource\EntityInterface> deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class CustomersTable extends AppTable
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
        $this->setDisplayField('name_for_lists');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');

        $this->belongsTo('TaxRates', [
            'foreignKey' => 'tax_rate_id',
            'joinType' => 'INNER',
        ]);
        $this->hasMany('Addresses', [
            'foreignKey' => 'customer_id',
            'sort' => [
                'Addresses.type',
                'Addresses.id' => 'DESC',
            ],
        ]);
        $this->hasMany('Billings', [
            'foreignKey' => 'customer_id',
            'sort' => [
                'Contracts.service_type_id',
                'Billings.contract_id' => 'DESC',
                'Billings.billing_from' => 'DESC',
            ],
        ]);
        $this->hasMany('BorrowedEquipments', [
            'foreignKey' => 'customer_id',
            'sort' => [
                'Contracts.service_type_id',
                'BorrowedEquipments.contract_id' => 'DESC',
                'BorrowedEquipments.id' => 'DESC',
                ],
        ]);
        $this->hasMany('Contracts', [
            'foreignKey' => 'customer_id',
            'sort' => [
                'Contracts.service_type_id',
                'Contracts.nid' => 'DESC',
            ],
        ]);
        $this->hasMany('Emails', [
            'foreignKey' => 'customer_id',
            'sort' => [
                'Emails.email',
            ],
        ]);
        $this->hasMany('CustomerLabels', [
            'foreignKey' => 'customer_id',
        ]);
        $this->hasMany('Logins', [
            'foreignKey' => 'customer_id',
            'sort' => [
                'Logins.login',
            ],
        ]);
        $this->hasMany('Phones', [
            'foreignKey' => 'customer_id',
            'sort' => [
                'Phones.phone',
            ],
        ]);
        $this->hasMany('SoldEquipments', [
            'foreignKey' => 'customer_id',
            'sort' => [
                'Contracts.service_type_id',
                'SoldEquipments.contract_id' => 'DESC',
                'SoldEquipments.id' => 'DESC',
                ],
        ]);
        $this->hasMany('Ips', [
            'foreignKey' => 'customer_id',
            'sort' => [
                'Contracts.service_type_id',
                'Contracts.nid' => 'DESC',
                'Ips.ip',
            ],
        ]);
        $this->hasMany('RemovedIps', [
            'foreignKey' => 'customer_id',
            'sort' => [
                'Contracts.service_type_id',
                'Contracts.nid' => 'DESC',
                'RemovedIps.ip',
            ],
        ]);
        $this->hasMany('IpNetworks', [
            'foreignKey' => 'customer_id',
            'sort' => [
                'Contracts.service_type_id',
                'Contracts.nid' => 'DESC',
                'IpNetworks.ip_network',
            ],
        ]);
        $this->hasMany('RemovedIpNetworks', [
            'foreignKey' => 'customer_id',
            'sort' => [
                'Contracts.service_type_id',
                'Contracts.nid' => 'DESC',
                'RemovedIpNetworks.ip_network',
            ],
        ]);
        $this->hasMany('Tasks', [
            'foreignKey' => 'customer_id',
            'sort' => [
                'TaskStates.priority' => 'DESC',
                'Tasks.priority' => 'DESC',
                'Tasks.nid' => 'DESC',
            ],
        ]);
        // as Dealers
        $this->hasMany('DealerCommissions', [
            'foreignKey' => 'dealer_id',
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
            ->notEmptyString('invoice_delivery_type');

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
        $rules->add($rules->existsIn(['tax_rate_id'], 'TaxRates'), ['errorField' => 'tax_rate_id']);

        $rules->add(
            function ($entity, $options) {
                // allow empty IC
                if (is_null($entity->ic)) {
                    return true;
                }

                // verify entered IC
                return $entity->ic_verified;
            },
            'isIcVerified',
            [
                'errorField' => 'ic',
                'message' => __('The specified identification number is not valid.'),
            ]
        );

        $rules->addDelete($rules->isNotLinkedTo('Addresses'));
        $rules->addDelete($rules->isNotLinkedTo('Billings'));
        $rules->addDelete($rules->isNotLinkedTo('BorrowedEquipments'));
        $rules->addDelete($rules->isNotLinkedTo('Contracts'));
        $rules->addDelete($rules->isNotLinkedTo('Emails'));
        $rules->addDelete($rules->isNotLinkedTo('CustomerLabels'));
        $rules->addDelete($rules->isNotLinkedTo('Logins'));
        $rules->addDelete($rules->isNotLinkedTo('Phones'));
        $rules->addDelete($rules->isNotLinkedTo('SoldEquipments'));
        $rules->addDelete($rules->isNotLinkedTo('Ips'));
        $rules->addDelete($rules->isNotLinkedTo('RemovedIps'));
        $rules->addDelete($rules->isNotLinkedTo('IpNetworks'));
        $rules->addDelete($rules->isNotLinkedTo('RemovedIpNetworks'));
        $rules->addDelete($rules->isNotLinkedTo('Tasks'));
        $rules->addDelete($rules->isNotLinkedTo('DealerCommissions')); // as Dealers

        return $rules;
    }
}
