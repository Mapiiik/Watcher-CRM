<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\I18n\Date;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use Override;
use Settings\Utility\Settings;

/**
 * ServiceOverrides Model
 *
 * @property \App\Model\Table\ContractsTable&\Cake\ORM\Association\BelongsTo $Contracts
 * @property \App\Model\Table\ServicesTable&\Cake\ORM\Association\BelongsTo $Services
 * @property \App\Model\Table\AppUsersTable&\Cake\ORM\Association\BelongsTo $Revokers
 * @method \App\Model\Entity\ServiceOverride newEmptyEntity()
 * @method \App\Model\Entity\ServiceOverride newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\ServiceOverride> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\ServiceOverride get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\ServiceOverride findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\ServiceOverride patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\ServiceOverride> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\ServiceOverride|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\ServiceOverride saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\ServiceOverride>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\ServiceOverride>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\ServiceOverride>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\ServiceOverride> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\ServiceOverride>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\ServiceOverride>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\ServiceOverride>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\ServiceOverride> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ServiceOverridesTable extends AppTable
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

        $this->setTable('service_overrides');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');

        $this->belongsTo('Contracts', [
            'foreignKey' => 'contract_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Services', [
            'foreignKey' => 'service_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Revokers', [
            'className' => 'AppUsers',
            'foreignKey' => 'revoked_by',
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
            ->uuid('contract_id')
            ->notEmptyString('contract_id');

        $validator
            ->uuid('service_id')
            ->notEmptyString('service_id');

        $validator
            ->date('valid_from')
            ->requirePresence('valid_from', 'create')
            ->notEmptyDate('valid_from');

        $validator
            ->date('valid_until')
            ->requirePresence('valid_until', 'create')
            ->notEmptyDate('valid_until');

        $validator
            ->scalar('reason')
            ->allowEmptyString('reason');

        $validator
            ->uuid('created_by')
            ->allowEmptyString('created_by');

        $validator
            ->uuid('modified_by')
            ->allowEmptyString('modified_by');

        $validator
            ->dateTime('revoked')
            ->allowEmptyDateTime('revoked');

        $validator
            ->uuid('revoked_by')
            ->allowEmptyString('revoked_by');

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
        $rules->add($rules->existsIn(['contract_id'], 'Contracts'), ['errorField' => 'contract_id']);
        $rules->add($rules->existsIn(['service_id'], 'Services'), ['errorField' => 'service_id']);

        // Ensure that valid_until is greater than or equal to valid_from
        $rules->add(
            function ($entity, $_options) {
                return $entity->valid_until >= $entity->valid_from;
            },
            'afterValidFrom',
            [
                'errorField' => 'valid_until',
                'message' => __('This field must be greater than or equal to "Valid From" field.'),
            ],
        );

        // Ensure that the start of the override does not exceed the maximum allowed offset from the current date
        $maxStartOffset = (int)Settings::get('core.contracts.service_overrides.max_start_offset_days', 5);
        $rules->add(
            function ($entity, $_options) use ($maxStartOffset) {
                $now = Date::now();

                return $entity->valid_from >= $now && $entity->valid_from <= $now->addDays($maxStartOffset);
            },
            'maxStartOffset',
            [
                'errorField' => 'valid_from',
                'message' => __n(
                    'The start date must be today or within the next {0} day.',
                    'The start date must be today or within the next {0} days.',
                    $maxStartOffset,
                    $maxStartOffset,
                ),
            ],
        );

        // Ensure that the interval between valid_from and valid_until does not exceed the maximum allowed interval
        $maxInterval = (int)Settings::get('core.contracts.service_overrides.max_duration_days', 5);
        $rules->add(
            function ($entity, $_options) use ($maxInterval) {
                return $entity->valid_from->diffInDays($entity->valid_until) + 1 <= $maxInterval;
            },
            'maxInterval',
            [
                'errorField' => 'valid_until',
                'message' => __n(
                    'The override duration must not exceed {0} day.',
                    'The override duration must not exceed {0} days.',
                    $maxInterval,
                    $maxInterval,
                ),
            ],
        );

        return $rules;
    }
}
