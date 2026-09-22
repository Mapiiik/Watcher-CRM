<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Enum\AccessTechnology;
use App\Model\Enum\AvailableConnectionOrigin;
use Cake\Database\Type\EnumType;
use Cake\I18n\Date;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use Override;

/**
 * AvailableConnections Model
 *
 * @property \App\Model\Table\ContractsTable&\Cake\ORM\Association\BelongsTo $Contracts
 * @method \App\Model\Entity\AvailableConnection newEmptyEntity()
 * @method \App\Model\Entity\AvailableConnection newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\AvailableConnection[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\AvailableConnection get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\AvailableConnection findOrCreate($search, callable|array|null $callback = null, $options = [])
 * @method \App\Model\Entity\AvailableConnection patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\AvailableConnection[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\AvailableConnection|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\AvailableConnection saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<\App\Model\Entity\AvailableConnection>|false saveMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\AvailableConnection> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\AvailableConnection>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\AvailableConnection> deleteManyOrFail(iterable $entities, $options = [])
 */
class AvailableConnectionsTable extends AppTable
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

        $this->setTable('available_connections');
        $this->setDisplayField('address_label');
        $this->setPrimaryKey('id');

        $this->getSchema()->setColumnType('access_technology', EnumType::from(AccessTechnology::class));
        $this->getSchema()->setColumnType('origin', EnumType::from(AvailableConnectionOrigin::class));

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');

        $this->belongsTo('Contracts', [
            'foreignKey' => 'contract_id',
        ]);
    }

    /**
     * Default validation rules.
     *
     * The address has to come from the registry, or the point could not be reported, and the
     * speeds are what the report is about.
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
            ->scalar('address_registry_source')
            ->maxLength('address_registry_source', 2)
            ->requirePresence('address_registry_source', 'create')
            ->notEmptyString('address_registry_source', __('Pick the address from the national address registry.'));

        $validator
            ->scalar('address_registry_reference')
            ->requirePresence('address_registry_reference', 'create')
            ->notEmptyString('address_registry_reference', __('Pick the address from the national address registry.'));

        $validator
            ->scalar('address_label')
            ->allowEmptyString('address_label');

        $validator
            ->numeric('gps_x')
            ->allowEmptyString('gps_x');

        $validator
            ->numeric('gps_y')
            ->allowEmptyString('gps_y');

        $validator
            ->enum('access_technology', AccessTechnology::class)
            ->requirePresence('access_technology', 'create')
            ->notEmptyString('access_technology');

        foreach (['speed_down_max', 'speed_up_max'] as $field) {
            $validator
                ->integer($field)
                ->greaterThan($field, 0)
                ->requirePresence($field, 'create')
                ->notEmptyString($field);
        }

        $validator
            ->uuid('access_point_id')
            ->allowEmptyString('access_point_id');

        $validator
            ->uuid('contract_id')
            ->allowEmptyString('contract_id');

        $validator
            ->date('retired')
            ->allowEmptyDate('retired');

        $validator
            ->scalar('note')
            ->allowEmptyString('note');

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
        $rules->add(
            $rules->isUnique(
                ['address_registry_source', 'address_registry_reference', 'access_technology'],
                __('This address point already has a record of this technology.'),
            ),
            ['errorField' => 'address_registry_reference'],
        );
        $rules->add($rules->existsIn(['contract_id'], 'Contracts'), ['errorField' => 'contract_id']);

        return $rules;
    }

    /**
     * The connections still there to be had on a day.
     *
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\AvailableConnection> $query The query.
     * @param \Cake\I18n\Date|null $on The day, today when not given.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\AvailableConnection>
     */
    public function findInService(SelectQuery $query, ?Date $on = null): SelectQuery
    {
        return $query->where([
            'OR' => [
                'AvailableConnections.retired IS NULL',
                'AvailableConnections.retired >' => $on ?? Date::now(),
            ],
        ]);
    }
}
