<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Billing;
use App\Model\Enum\CustomerDealer;
use App\Model\Enum\CustomerInvoiceDeliveryType;
use Cake\Collection\CollectionInterface;
use Cake\Database\Type\EnumType;
use Cake\I18n\Date;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use Override;

/**
 * Customers Model
 *
 * @property \App\Model\Table\AccountingProfilesTable&\Cake\ORM\Association\BelongsTo $AccountingProfiles
 * @property \App\Model\Table\AddressesTable&\Cake\ORM\Association\HasMany $Addresses
 * @property \App\Model\Table\BillingsTable&\Cake\ORM\Association\HasMany $Billings
 * @property \App\Model\Table\BorrowedEquipmentsTable&\Cake\ORM\Association\HasMany $BorrowedEquipments
 * @property \App\Model\Table\ContractsTable&\Cake\ORM\Association\HasMany $Contracts
 * @property \App\Model\Table\EmailsTable&\Cake\ORM\Association\HasMany $Emails
 * @property \App\Model\Table\CustomerLabelsTable&\Cake\ORM\Association\HasMany $CustomerLabels
 * @property \App\Model\Table\LoginsTable&\Cake\ORM\Association\HasMany $Logins
 * @property \App\Model\Table\PhonesTable&\Cake\ORM\Association\HasMany $Phones
 * @property \App\Model\Table\IpAddressesTable&\Cake\ORM\Association\HasMany $IpAddresses
 * @property \App\Model\Table\RemovedIpAddressesTable&\Cake\ORM\Association\HasMany $RemovedIpAddresses
 * @property \App\Model\Table\IpNetworksTable&\Cake\ORM\Association\HasMany $IpNetworks
 * @property \App\Model\Table\RemovedIpNetworksTable&\Cake\ORM\Association\HasMany $RemovedIpNetworks
 * @property \App\Model\Table\SoldEquipmentsTable&\Cake\ORM\Association\HasMany $SoldEquipments
 * @property \App\Model\Table\TasksTable&\Cake\ORM\Association\HasMany $Tasks
 * @method \App\Model\Entity\Customer newEmptyEntity()
 * @method \App\Model\Entity\Customer newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Customer[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Customer get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Customer findOrCreate($search, callable|array|null $callback = null, $options = [])
 * @method \App\Model\Entity\Customer patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Customer[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Customer|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Customer saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<\App\Model\Entity\Customer>|false saveMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\Customer> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\Customer>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\Customer> deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class CustomersTable extends AppTable
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

        $this->setTable('customers');
        $this->setDisplayField('name_for_lists');
        $this->setPrimaryKey('id');

        $this->getSchema()->setColumnType(
            'dealer',
            EnumType::from(CustomerDealer::class),
        );

        $this->getSchema()->setColumnType(
            'invoice_delivery_type',
            EnumType::from(CustomerInvoiceDeliveryType::class),
        );

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');

        $this->belongsTo('AccountingProfiles', [
            'foreignKey' => 'accounting_profile_id',
            'joinType' => 'INNER',
        ]);
        $this->hasMany('AccessCredentials', [
            'foreignKey' => 'customer_id',
            'sort' => [
                'AccessCredentials.name',
            ],
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
                'BorrowedEquipments.borrowed_from' => 'DESC',
                'BorrowedEquipments.id' => 'DESC',
            ],
        ]);
        $this->hasMany('Contracts', [
            'foreignKey' => 'customer_id',
            'sort' => [
                'Contracts.service_type_id',
                'Contracts.id' => 'DESC',
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
                'SoldEquipments.date_of_sale' => 'DESC',
                'SoldEquipments.id' => 'DESC',
            ],
        ]);
        $this->hasMany('IpAddresses', [
            'foreignKey' => 'customer_id',
            'sort' => [
                'Contracts.service_type_id',
                'IpAddresses.contract_id' => 'DESC',
                'IpAddresses.ip_address',
            ],
        ]);
        $this->hasMany('RemovedIpAddresses', [
            'foreignKey' => 'customer_id',
            'sort' => [
                'Contracts.service_type_id',
                'RemovedIpAddresses.contract_id' => 'DESC',
                'RemovedIpAddresses.ip_address',
            ],
        ]);
        $this->hasMany('IpNetworks', [
            'foreignKey' => 'customer_id',
            'sort' => [
                'Contracts.service_type_id',
                'IpNetworks.contract_id' => 'DESC',
                'IpNetworks.ip_network',
            ],
        ]);
        $this->hasMany('RemovedIpNetworks', [
            'foreignKey' => 'customer_id',
            'sort' => [
                'Contracts.service_type_id',
                'RemovedIpNetworks.contract_id' => 'DESC',
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
    #[Override]
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
            ->scalar('identity_number')
            ->maxLength('identity_number', 12)
            ->allowEmptyString('identity_number');

        $validator
            ->scalar('vat_number')
            ->maxLength('vat_number', 15)
            ->allowEmptyString('vat_number');

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
            ->integer('individual_maturity_period')
            ->allowEmptyString('individual_maturity_period');

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
    #[Override]
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add(
            $rules->existsIn(['accounting_profile_id'], 'AccountingProfiles'),
            ['errorField' => 'accounting_profile_id'],
        );

        $rules->add(
            function ($entity, $_options) {
                // allow empty identity number
                if (is_null($entity->identity_number)) {
                    return true;
                }

                // verify entered identity number
                return $entity->verifyIdentityNumber();
            },
            'isIdentityNumberVerified',
            [
                'errorField' => 'identity_number',
                'message' => __('The specified identification number is not valid.'),
            ],
        );

        $rules->addDelete($rules->isNotLinkedTo('AccessCredentials'));
        $rules->addDelete($rules->isNotLinkedTo('Addresses'));
        $rules->addDelete($rules->isNotLinkedTo('Billings'));
        $rules->addDelete($rules->isNotLinkedTo('BorrowedEquipments'));
        $rules->addDelete($rules->isNotLinkedTo('Contracts'));
        $rules->addDelete($rules->isNotLinkedTo('Emails'));
        $rules->addDelete($rules->isNotLinkedTo('CustomerLabels'));
        $rules->addDelete($rules->isNotLinkedTo('Logins'));
        $rules->addDelete($rules->isNotLinkedTo('Phones'));
        $rules->addDelete($rules->isNotLinkedTo('SoldEquipments'));
        $rules->addDelete($rules->isNotLinkedTo('IpAddresses'));
        $rules->addDelete($rules->isNotLinkedTo('RemovedIpAddresses'));
        $rules->addDelete($rules->isNotLinkedTo('IpNetworks'));
        $rules->addDelete($rules->isNotLinkedTo('RemovedIpNetworks'));
        $rules->addDelete($rules->isNotLinkedTo('Tasks'));
        $rules->addDelete($rules->isNotLinkedTo('DealerCommissions')); // as Dealers

        return $rules;
    }

    /**
     * Finder for customers with billing data for a given month and accounting profile.
     *
     * Returns customers including their addresses, contracts and billings
     * that are active and billable within the given invoiced month.
     *
     * Billings are filtered to those overlapping the invoiced month and
     * enriched with a computed `period_total` value representing the
     * billable amount for the given period.
     *
     * This finder is intended to be used by invoice generation and
     * bookkeeping workflows and does not perform any persistence or
     * side effects.
     *
     * ### Options
     * - `invoicedMonth` (Cake\I18n\Date) Required. Month for which billing data is calculated.
     * - `accountingProfileId` (string) Required. Accounting profile identifier used to filter customers.
     *
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Customer> $query Base query.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Customer>
     */
    public function findBillingDataForMonth(
        SelectQuery $query,
        Date $invoicedMonth,
        string $accountingProfileId,
    ): SelectQuery {
        return $query
            ->contain('AccountingProfiles')
            ->contain('Addresses')
            ->contain('Addresses.Countries')
            ->contain('Emails')
            ->contain('Phones')
            ->contain('Contracts', function (SelectQuery $q) use ($invoicedMonth) {
                return $q
                    ->contain('ContractStates')
                    ->contain('ServiceTypes')
                    ->contain('Billings', function (SelectQuery $q) use ($invoicedMonth) {
                        return $q
                            ->contain('Services')
                            // filter billings active in selected month
                            ->where([
                                'Billings.billing_from <=' => $invoicedMonth->lastOfMonth(),
                            ])
                            ->andWhere([
                                'OR' => [
                                    'Billings.billing_until IS NULL',
                                    'Billings.billing_until >=' => $invoicedMonth->firstOfMonth(),
                                ],
                            ])
                            // order by billing ID
                            ->orderBy([
                                'Billings.id',
                            ])
                            // enrich billings with computed period totals
                            ->formatResults(
                                function (CollectionInterface $billings) use ($invoicedMonth): CollectionInterface {
                                    return $billings->map(function (Billing $billing) use ($invoicedMonth): Billing {
                                        $billing->set('period_total', $billing->periodTotal(
                                            $invoicedMonth->firstOfMonth(),
                                            $invoicedMonth->lastOfMonth(),
                                        ));

                                        return $billing;
                                    });
                                },
                            );
                    })
                    // only contracts with billed states
                    ->where([
                        'ContractStates.billed' => true,
                    ])
                    // order by contract ID
                    ->orderBy([
                        'Contracts.nid',
                    ]);
            })
            // only customers with the selected accounting profile
            ->where([
                'Customers.accounting_profile_id' => $accountingProfileId,
            ])
            // order by customer ID
            ->orderBy([
                'Customers.nid',
            ]);
    }
}
