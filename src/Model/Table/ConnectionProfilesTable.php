<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use Override;

/**
 * ConnectionProfiles Model
 *
 * @property \App\Model\Table\ServicesTable&\Cake\ORM\Association\HasMany $Services
 * @method \App\Model\Entity\ConnectionProfile newEmptyEntity()
 * @method \App\Model\Entity\ConnectionProfile newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\ConnectionProfile[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\ConnectionProfile get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\ConnectionProfile findOrCreate($search, callable|array|null $callback = null, $options = [])
 * @method \App\Model\Entity\ConnectionProfile patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\ConnectionProfile[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\ConnectionProfile|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\ConnectionProfile saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<\App\Model\Entity\ConnectionProfile>|false saveMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\ConnectionProfile> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\ConnectionProfile>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\ConnectionProfile> deleteManyOrFail(iterable $entities, $options = [])
 */
class ConnectionProfilesTable extends AppTable
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    #[Override]
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('connection_profiles');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');

        $this->hasMany('Services', [
            'foreignKey' => 'connection_profile_id',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    #[Override]
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->uuid('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('radius_group')
            ->maxLength('radius_group', 32)
            ->requirePresence('radius_group', 'create')
            ->notEmptyString('radius_group');

        $validator
            ->scalar('name')
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->integer('fup_limit')
            ->allowEmptyString('fup_limit');

        $validator
            ->integer('data_limit')
            ->allowEmptyString('data_limit');

        $validator
            ->integer('overlimit_fragment')
            ->allowEmptyString('overlimit_fragment');

        $validator
            ->integer('overlimit_cost')
            ->allowEmptyString('overlimit_cost');

        $validator
            ->allowEmptyString('speed_down');

        $validator
            ->allowEmptyString('speed_up');

        // Empty is an answer here: the tariff declares no figure of its own and the entity
        // derives one from the advertised speed.
        foreach (['speed_down_common', 'speed_up_common', 'speed_down_minimum', 'speed_up_minimum'] as $field) {
            $validator
                ->integer($field)
                ->allowEmptyString($field);
        }

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    #[Override]
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->addDelete($rules->isNotLinkedTo('Services'));

        return $rules;
    }
}
