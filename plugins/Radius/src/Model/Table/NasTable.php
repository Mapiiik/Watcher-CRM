<?php
declare(strict_types=1);

namespace Radius\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

/**
 * Nas Model
 *
 * @method \Radius\Model\Entity\Nas newEmptyEntity()
 * @method \Radius\Model\Entity\Nas newEntity(array $data, array $options = [])
 * @method \Radius\Model\Entity\Nas[] newEntities(array $data, array $options = [])
 * @method \Radius\Model\Entity\Nas get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \Radius\Model\Entity\Nas findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Radius\Model\Entity\Nas patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Radius\Model\Entity\Nas[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Radius\Model\Entity\Nas|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Radius\Model\Entity\Nas saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Radius\Model\Entity\Nas[]|iterable<\Cake\Datasource\EntityInterface>|false saveMany(iterable $entities, $options = [])
 * @method \Radius\Model\Entity\Nas[]|iterable<\Cake\Datasource\EntityInterface> saveManyOrFail(iterable $entities, $options = [])
 * @method \Radius\Model\Entity\Nas[]|iterable<\Cake\Datasource\EntityInterface>|false deleteMany(iterable $entities, $options = [])
 * @method \Radius\Model\Entity\Nas[]|iterable<\Cake\Datasource\EntityInterface> deleteManyOrFail(iterable $entities, $options = [])
 */
class NasTable extends AppTable
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

        $this->setTable('nas');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');
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
            ->scalar('nasname')
            ->requirePresence('nasname', 'create')
            ->notEmptyString('nasname');

        $validator
            ->scalar('shortname')
            ->requirePresence('shortname', 'create')
            ->notEmptyString('shortname');

        $validator
            ->scalar('type')
            ->notEmptyString('type');

        $validator
            ->integer('ports')
            ->allowEmptyString('ports');

        $validator
            ->scalar('secret')
            ->requirePresence('secret', 'create')
            ->notEmptyString('secret');

        $validator
            ->scalar('server')
            ->allowEmptyString('server');

        $validator
            ->scalar('community')
            ->allowEmptyString('community');

        $validator
            ->scalar('description')
            ->allowEmptyString('description');

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
