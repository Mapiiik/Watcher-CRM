<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

/**
 * Queues Model
 *
 * @property \App\Model\Table\ServicesTable&\Cake\ORM\Association\HasMany $Services
 * @method \App\Model\Entity\Queue newEmptyEntity()
 * @method \App\Model\Entity\Queue newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Queue[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Queue get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Queue findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Queue patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Queue[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Queue|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Queue saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<\App\Model\Entity\Queue>|false saveMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\Queue> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\Queue>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\Queue> deleteManyOrFail(iterable $entities, $options = [])
 */
class QueuesTable extends AppTable
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

        $this->setTable('queues');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');

        $this->hasMany('Services', [
            'foreignKey' => 'queue_id',
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
            ->uuid('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('name')
            ->maxLength('name', 32)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('caption')
            ->allowEmptyString('caption');

        $validator
            ->allowEmptyString('fup');

        $validator
            ->allowEmptyString('limit');

        $validator
            ->integer('overlimit_fragment')
            ->allowEmptyString('overlimit_fragment');

        $validator
            ->integer('overlimit_cost')
            ->allowEmptyString('overlimit_cost');

        $validator
            ->allowEmptyString('speed_up');

        $validator
            ->allowEmptyString('speed_down');

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
        $rules->addDelete($rules->isNotLinkedTo('Services'));

        return $rules;
    }
}
