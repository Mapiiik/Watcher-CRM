<?php
declare(strict_types=1);

namespace Radius\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

/**
 * Radgroupcheck Model
 *
 * @method \Radius\Model\Entity\Radgroupcheck newEmptyEntity()
 * @method \Radius\Model\Entity\Radgroupcheck newEntity(array $data, array $options = [])
 * @method \Radius\Model\Entity\Radgroupcheck[] newEntities(array $data, array $options = [])
 * @method \Radius\Model\Entity\Radgroupcheck get($primaryKey, $options = [])
 * @method \Radius\Model\Entity\Radgroupcheck findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Radius\Model\Entity\Radgroupcheck patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Radius\Model\Entity\Radgroupcheck[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Radius\Model\Entity\Radgroupcheck|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Radius\Model\Entity\Radgroupcheck saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Radius\Model\Entity\Radgroupcheck[]|iterable<\Cake\Datasource\EntityInterface>|false saveMany(iterable $entities, $options = [])
 * @method \Radius\Model\Entity\Radgroupcheck[]|iterable<\Cake\Datasource\EntityInterface> saveManyOrFail(iterable $entities, $options = [])
 * @method \Radius\Model\Entity\Radgroupcheck[]|iterable<\Cake\Datasource\EntityInterface>|false deleteMany(iterable $entities, $options = [])
 * @method \Radius\Model\Entity\Radgroupcheck[]|iterable<\Cake\Datasource\EntityInterface> deleteManyOrFail(iterable $entities, $options = [])
 */
class RadgroupcheckTable extends AppTable
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

        $this->setTable('radgroupcheck');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');

        $this->hasMany('Radius.Radusergroup', [
            'foreignKey' => 'groupname',
            'bindingKey' => 'groupname',
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
            ->scalar('groupname')
            ->notEmptyString('groupname');

        $validator
            ->scalar('attribute')
            ->notEmptyString('attribute');

        $validator
            ->scalar('op')
            ->maxLength('op', 2)
            ->notEmptyString('op');

        $validator
            ->scalar('value')
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
