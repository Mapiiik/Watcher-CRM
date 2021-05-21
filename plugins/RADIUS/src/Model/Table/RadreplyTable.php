<?php
declare(strict_types=1);

namespace RADIUS\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Radreply Model
 *
 * @method \RADIUS\Model\Entity\Radreply newEmptyEntity()
 * @method \RADIUS\Model\Entity\Radreply newEntity(array $data, array $options = [])
 * @method \RADIUS\Model\Entity\Radreply[] newEntities(array $data, array $options = [])
 * @method \RADIUS\Model\Entity\Radreply get($primaryKey, $options = [])
 * @method \RADIUS\Model\Entity\Radreply findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \RADIUS\Model\Entity\Radreply patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \RADIUS\Model\Entity\Radreply[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \RADIUS\Model\Entity\Radreply|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \RADIUS\Model\Entity\Radreply saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \RADIUS\Model\Entity\Radreply[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \RADIUS\Model\Entity\Radreply[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \RADIUS\Model\Entity\Radreply[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \RADIUS\Model\Entity\Radreply[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class RadreplyTable extends Table
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

        $this->setTable('radreply');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');
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
            ->scalar('username')
            ->maxLength('username', 64)
            ->notEmptyString('username');

        $validator
            ->scalar('attribute')
            ->maxLength('attribute', 64)
            ->notEmptyString('attribute');

        $validator
            ->scalar('op')
            ->maxLength('op', 2)
            ->notEmptyString('op');

        $validator
            ->scalar('value')
            ->maxLength('value', 253)
            ->notEmptyString('value');

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
        $rules->add($rules->isUnique(['username']), ['errorField' => 'username']);

        return $rules;
    }

    /**
     * Returns the database connection name to use by default.
     *
     * @return string
     */
    public static function defaultConnectionName(): string
    {
        return 'radius';
    }
}