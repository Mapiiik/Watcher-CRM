<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Enum\AddressNumberType;
use App\Model\Enum\AddressType;
use Cake\Database\Type\EnumType;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

/**
 * Addresses Model
 *
 * @property \App\Model\Table\CustomersTable&\Cake\ORM\Association\BelongsTo $Customers
 * @property \App\Model\Table\CountriesTable&\Cake\ORM\Association\BelongsTo $Countries
 * @method \App\Model\Entity\Address newEmptyEntity()
 * @method \App\Model\Entity\Address newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Address[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Address get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Address findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Address patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Address[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Address|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Address saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<\App\Model\Entity\Address>|false saveMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\Address> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\Address>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\Address> deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class AddressesTable extends AppTable
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

        $this->setTable('addresses');
        $this->setDisplayField('address');
        $this->setPrimaryKey('id');

        $this->getSchema()->setColumnType(
            'type',
            EnumType::from(AddressType::class)
        );

        $this->getSchema()->setColumnType(
            'number_type',
            EnumType::from(AddressNumberType::class)
        );

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');

        $this->belongsTo('Customers', [
            'foreignKey' => 'customer_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Countries', [
            'foreignKey' => 'country_id',
            'joinType' => 'INNER',
        ]);
        // as InstallationAddresses
        $this->hasMany('Contracts', [
            'foreignKey' => 'installation_address_id',
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
            ->integer('type')
            ->requirePresence('type', 'create')
            ->notEmptyString('type');

        $validator
            ->integer('number_type')
            ->requirePresence('number_type')
            ->notEmptyString('number_type');

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
            ->scalar('street')
            ->allowEmptyString('street');

        $validator
            ->scalar('number')
            ->allowEmptyString('number');

        $validator
            ->scalar('city')
            ->allowEmptyString('city');

        $validator
            ->integer('zip')
            ->allowEmptyString('zip');

        $validator
            ->integer('ruian_gid')
            ->allowEmptyString('ruian_gid');

        $validator
            ->numeric('gps_x')
            ->allowEmptyString('gps_x');

        $validator
            ->numeric('gps_y')
            ->allowEmptyString('gps_y');

        $validator
            ->boolean('manual_coordinate_setting')
            ->notEmptyString('manual_coordinate_setting');

        $validator
            ->scalar('note')
            ->allowEmptyString('note');

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
        $rules->add($rules->existsIn(['country_id'], 'Countries'), ['errorField' => 'country_id']);

        $rules->addDelete($rules->isNotLinkedTo('Contracts')); // as InstallationAddresses

        return $rules;
    }
}
