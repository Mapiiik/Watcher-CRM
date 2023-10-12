<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

/**
 * EquipmentTypes Model
 *
 * @property \App\Model\Table\BorrowedEquipmentsTable&\Cake\ORM\Association\HasMany $BorrowedEquipments
 * @property \App\Model\Table\SoldEquipmentsTable&\Cake\ORM\Association\HasMany $SoldEquipments
 * @method \App\Model\Entity\EquipmentType newEmptyEntity()
 * @method \App\Model\Entity\EquipmentType newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\EquipmentType[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\EquipmentType get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\EquipmentType findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\EquipmentType patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\EquipmentType[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\EquipmentType|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\EquipmentType saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\EquipmentType[]|iterable<\Cake\Datasource\EntityInterface>|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\EquipmentType[]|iterable<\Cake\Datasource\EntityInterface> saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\EquipmentType[]|iterable<\Cake\Datasource\EntityInterface>|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\EquipmentType[]|iterable<\Cake\Datasource\EntityInterface> deleteManyOrFail(iterable $entities, $options = [])
 */
class EquipmentTypesTable extends AppTable
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

        $this->setTable('equipment_types');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');

        $this->hasMany('BorrowedEquipments', [
            'foreignKey' => 'equipment_type_id',
        ]);
        $this->hasMany('SoldEquipments', [
            'foreignKey' => 'equipment_type_id',
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
            ->scalar('name')
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->integer('price')
            ->allowEmptyString('price');

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
        $rules->addDelete($rules->isNotLinkedTo('BorrowedEquipments'));
        $rules->addDelete($rules->isNotLinkedTo('SoldEquipments'));

        return $rules;
    }
}
