<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Labels Model
 *
 * @property \App\Model\Table\LabelCustomersTable&\Cake\ORM\Association\HasMany $LabelCustomers
 *
 * @method \App\Model\Entity\Label newEmptyEntity()
 * @method \App\Model\Entity\Label newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Label[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Label get($primaryKey, $options = [])
 * @method \App\Model\Entity\Label findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Label patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Label[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Label|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Label saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Label[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Label[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Label[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Label[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class LabelsTable extends Table
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

        $this->setTable('labels');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->hasMany('LabelCustomers', [
            'foreignKey' => 'label_id',
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
            ->allowEmptyString('id', null, 'create')
            ->add('id', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('name')
            ->allowEmptyString('name');

        $validator
            ->scalar('caption')
            ->allowEmptyString('caption');

        $validator
            ->scalar('color')
            ->maxLength('color', 6)
            ->allowEmptyString('color');

        $validator
            ->integer('validity')
            ->allowEmptyString('validity');

        $validator
            ->boolean('dynamic')
            ->notEmptyString('dynamic');

        $validator
            ->scalar('dynamic_sql')
            ->allowEmptyString('dynamic_sql');

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
        $rules->add($rules->isUnique(['id']), ['errorField' => 'id']);

        return $rules;
    }
}
