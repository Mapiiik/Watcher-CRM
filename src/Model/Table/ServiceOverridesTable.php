<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\ServiceOverride;
use Cake\I18n\Date;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use Override;
use Settings\Utility\Settings;

/**
 * ServiceOverrides Model
 *
 * @property \App\Model\Table\ContractsTable&\Cake\ORM\Association\BelongsTo $Contracts
 * @property \App\Model\Table\ServicesTable&\Cake\ORM\Association\BelongsTo $Services
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
            ->requirePresence('contract_id', 'create')
            ->notEmptyString('contract_id');

        $validator
            ->uuid('service_id')
            ->requirePresence('service_id', 'create')
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

        // Ensure that the start of the override does not exceed the maximum allowed offset from the current date
        $maxStartOffset = (int)Settings::get('core.contracts.service_overrides.max_start_offset_days', 5);
        $rules->add(
            function (ServiceOverride $entity, $_options) use ($maxStartOffset): bool {
                // Do not check for historical records
                if (!$entity->isNew() && !$entity->isDirty('valid_from')) {
                    return true;
                }

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

        // Ensure that the end of the override is not before today when changing
        $rules->add(
            function (ServiceOverride $entity, $_options): bool {
                // Do not check for historical records
                if (!$entity->isNew() && !$entity->isDirty('valid_until')) {
                    return true;
                }

                $now = Date::now();

                return $entity->valid_until >= $now;
            },
            'minEndDate',
            [
                'errorField' => 'valid_until',
                'message' => __(
                    'The end date must be today or in the future.',
                ),
            ],
        );

        // Ensure that valid_until is greater than or equal to valid_from
        $rules->add(
            function (ServiceOverride $entity, $_options): bool {
                // skip if no modification in valid_from and valid_until
                if (!$entity->isDirty('valid_from') && !$entity->isDirty('valid_until')) {
                    return true;
                }

                return $entity->valid_until >= $entity->valid_from;
            },
            'afterValidFrom',
            [
                'errorField' => 'valid_until',
                'message' => __('The end date must be the same as or later than the "Valid From" date.'),
            ],
        );

        // Ensure that the interval between valid_from and valid_until does not exceed the maximum allowed interval
        $maxInterval = (int)Settings::get('core.contracts.service_overrides.max_duration_days', 5);
        $rules->add(
            function (ServiceOverride $entity, $_options) use ($maxInterval): bool {
                // skip if no modification in valid_from and valid_until
                if (!$entity->isDirty('valid_from') && !$entity->isDirty('valid_until')) {
                    return true;
                }

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

        // Ensure that there is no overlapping active override for the same contract
        $rules->add(
            function (ServiceOverride $entity, $_options): bool {
                // skip if no modification in contract_id, valid_from and valid_until
                if (
                    !$entity->isDirty('contract_id')
                    && !$entity->isDirty('valid_from')
                    && !$entity->isDirty('valid_until')
                ) {
                    return true;
                }

                $query = $this->find()
                    ->where([
                        'contract_id' => $entity->contract_id,
                        'revoked IS' => null,
                        'valid_from <=' => $entity->valid_until,
                        'valid_until >=' => $entity->valid_from,
                    ]);

                // Exclude current entity when editing
                if (!$entity->isNew()) {
                    $query->where(['id !=' => $entity->id]);
                }

                return $query->count() === 0;
            },
            'noOverlappingOverride',
            [
                'errorField' => 'valid_from',
                'message' => __(
                    'An active service override already exists for this contract in the selected date range.',
                ),
            ],
        );

        return $rules;
    }

    /**
     * Find only active service overrides (for today)
     *
     * Options to include future, past or revoked records in addition to those active today.
     *
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\ServiceOverride> $query Base query.
     * @param bool $includeRevoked Include revoked records.
     * @param bool $includeFuture Include future records.
     * @param bool $includePast Include past records.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\ServiceOverride>
     */
    public function findActive(
        SelectQuery $query,
        bool $includeRevoked = false,
        bool $includeFuture = false,
        bool $includePast = false,
    ): SelectQuery {
        $today = Date::today();

        if (!$includeRevoked) {
            $query->where(['revoked IS' => null]);
        }
        if (!$includeFuture) {
            $query->where(['valid_from <=' => $today]);
        }
        if (!$includePast) {
            $query->where(['valid_until >=' => $today]);
        }

        return $query;
    }
}
