<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * ServiceTypes Model
 *
 * @property \App\Model\Table\ContractsTable&\Cake\ORM\Association\HasMany $Contracts
 * @property \App\Model\Table\QueuesTable&\Cake\ORM\Association\HasMany $Queues
 * @property \App\Model\Table\ServicesTable&\Cake\ORM\Association\HasMany $Services
 * @method \App\Model\Entity\ServiceType newEmptyEntity()
 * @method \App\Model\Entity\ServiceType newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\ServiceType[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\ServiceType get($primaryKey, $options = [])
 * @method \App\Model\Entity\ServiceType findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\ServiceType patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\ServiceType[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\ServiceType|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\ServiceType saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\ServiceType[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\ServiceType[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\ServiceType[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\ServiceType[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ServiceTypesTable extends Table
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

        $this->setTable('service_types');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');

        $this->hasMany('Contracts', [
            'foreignKey' => 'service_type_id',
        ]);
        $this->hasMany('Queues', [
            'foreignKey' => 'service_type_id',
        ]);
        $this->hasMany('Services', [
            'foreignKey' => 'service_type_id',
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
            ->allowEmptyString('name');

        $validator
            ->scalar('contract_number_format')
            ->allowEmptyString('contract_number_format');

        return $validator;
    }
}
