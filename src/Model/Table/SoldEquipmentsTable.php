<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

/**
 * SoldEquipments Model
 *
 * @property \App\Model\Table\CustomersTable&\Cake\ORM\Association\BelongsTo $Customers
 * @property \App\Model\Table\ContractsTable&\Cake\ORM\Association\BelongsTo $Contracts
 * @property \App\Model\Table\EquipmentTypesTable&\Cake\ORM\Association\BelongsTo $EquipmentTypes
 * @method \App\Model\Entity\SoldEquipment newEmptyEntity()
 * @method \App\Model\Entity\SoldEquipment newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\SoldEquipment[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\SoldEquipment get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\SoldEquipment findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\SoldEquipment patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\SoldEquipment[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\SoldEquipment|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\SoldEquipment saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<\App\Model\Entity\SoldEquipment>|false saveMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\SoldEquipment> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\SoldEquipment>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\SoldEquipment> deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class SoldEquipmentsTable extends AppTable
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

        $this->setTable('sold_equipments');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');

        $this->belongsTo('Customers', [
            'foreignKey' => 'customer_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Contracts', [
            'foreignKey' => 'contract_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('EquipmentTypes', [
            'foreignKey' => 'equipment_type_id',
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
            ->uuid('equipment_type_id')
            ->notEmptyString('equipment_type_id');

        $validator
            ->scalar('serial_number')
            ->allowEmptyString('serial_number');

        $validator
            ->date('date_of_sale')
            ->allowEmptyDate('date_of_sale');

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
        $rules->add($rules->existsIn(['contract_id'], 'Contracts'), ['errorField' => 'contract_id']);
        $rules->add($rules->existsIn(['equipment_type_id'], 'EquipmentTypes'), ['errorField' => 'equipment_type_id']);

        return $rules;
    }
}
