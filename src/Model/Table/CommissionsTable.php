<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\Validation\Validator;

/**
 * Commissions Model
 *
 * @property \App\Model\Table\DealerCommissionsTable&\Cake\ORM\Association\HasMany $DealerCommissions
 * @property \App\Model\Table\ContractsTable&\Cake\ORM\Association\HasMany $Contracts
 * @method \App\Model\Entity\Commission newEmptyEntity()
 * @method \App\Model\Entity\Commission newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Commission[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Commission get($primaryKey, $options = [])
 * @method \App\Model\Entity\Commission findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Commission patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Commission[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Commission|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Commission saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Commission[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Commission[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Commission[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Commission[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class CommissionsTable extends AppTable
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

        $this->setTable('commissions');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');

        $this->hasMany('DealerCommissions', [
            'foreignKey' => 'commission_id',
        ]);
        $this->hasMany('Contracts', [
            'foreignKey' => 'commission_id',
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

        return $validator;
    }
}
