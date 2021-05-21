<?php
declare(strict_types=1);

namespace RADIUS\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Radgroupreply Model
 *
 * @method \RADIUS\Model\Entity\Radgroupreply newEmptyEntity()
 * @method \RADIUS\Model\Entity\Radgroupreply newEntity(array $data, array $options = [])
 * @method \RADIUS\Model\Entity\Radgroupreply[] newEntities(array $data, array $options = [])
 * @method \RADIUS\Model\Entity\Radgroupreply get($primaryKey, $options = [])
 * @method \RADIUS\Model\Entity\Radgroupreply findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \RADIUS\Model\Entity\Radgroupreply patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \RADIUS\Model\Entity\Radgroupreply[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \RADIUS\Model\Entity\Radgroupreply|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \RADIUS\Model\Entity\Radgroupreply saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \RADIUS\Model\Entity\Radgroupreply[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \RADIUS\Model\Entity\Radgroupreply[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \RADIUS\Model\Entity\Radgroupreply[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \RADIUS\Model\Entity\Radgroupreply[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class RadgroupreplyTable extends Table
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

        $this->setTable('radgroupreply');
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
            ->scalar('groupname')
            ->maxLength('groupname', 64)
            ->notEmptyString('groupname');

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
     * Returns the database connection name to use by default.
     *
     * @return string
     */
    public static function defaultConnectionName(): string
    {
        return 'radius';
    }
}
