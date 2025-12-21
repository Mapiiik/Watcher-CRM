<?php
declare(strict_types=1);

namespace Settings\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

/**
 * Settings Model
 *
 * @method \Settings\Model\Entity\Setting newEmptyEntity()
 * @method \Settings\Model\Entity\Setting newEntity(array $data, array $options = [])
 * @method array<\Settings\Model\Entity\Setting> newEntities(array $data, array $options = [])
 * @method \Settings\Model\Entity\Setting get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Settings\Model\Entity\Setting findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \Settings\Model\Entity\Setting findOrNewEntity($search, ?callable $callback = null, array $options = [])
 * @method \Settings\Model\Entity\Setting patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\Settings\Model\Entity\Setting> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Settings\Model\Entity\Setting|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Settings\Model\Entity\Setting saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\Settings\Model\Entity\Setting>|\Cake\Datasource\ResultSetInterface<\Settings\Model\Entity\Setting>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\Settings\Model\Entity\Setting>|\Cake\Datasource\ResultSetInterface<\Settings\Model\Entity\Setting> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\Settings\Model\Entity\Setting>|\Cake\Datasource\ResultSetInterface<\Settings\Model\Entity\Setting>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\Settings\Model\Entity\Setting>|\Cake\Datasource\ResultSetInterface<\Settings\Model\Entity\Setting> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class SettingsTable extends AppTable
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

        $this->setTable('settings');
        $this->setDisplayField('key');
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
            ->scalar('plugin')
            ->requirePresence('plugin', 'create')
            ->notEmptyString('plugin');

        $validator
            ->scalar('key')
            ->requirePresence('key', 'create')
            ->notEmptyString('key');

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
        $rules->add($rules->isUnique(['plugin', 'key']), ['errorField' => 'key']);

        return $rules;
    }
}
