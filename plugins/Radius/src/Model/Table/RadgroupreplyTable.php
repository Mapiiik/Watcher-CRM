<?php
declare(strict_types=1);

namespace Radius\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

/**
 * Radgroupreply Model
 *
 * @method \Radius\Model\Entity\Radgroupreply newEmptyEntity()
 * @method \Radius\Model\Entity\Radgroupreply newEntity(array $data, array $options = [])
 * @method \Radius\Model\Entity\Radgroupreply[] newEntities(array $data, array $options = [])
 * @method \Radius\Model\Entity\Radgroupreply get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \Radius\Model\Entity\Radgroupreply findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Radius\Model\Entity\Radgroupreply patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Radius\Model\Entity\Radgroupreply[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Radius\Model\Entity\Radgroupreply|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Radius\Model\Entity\Radgroupreply saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<\Radius\Model\Entity\Radgroupreply>|false saveMany(iterable $entities, $options = [])
 * @method iterable<\Radius\Model\Entity\Radgroupreply> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<\Radius\Model\Entity\Radgroupreply>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<\Radius\Model\Entity\Radgroupreply> deleteManyOrFail(iterable $entities, $options = [])
 */
class RadgroupreplyTable extends AppTable
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

        $this->setTable('radgroupreply');
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
