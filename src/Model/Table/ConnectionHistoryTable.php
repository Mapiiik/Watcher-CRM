<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\ConnectionHistory;
use App\Model\Enum\ConnectionHistorySource;
use App\Model\Enum\FirstSeenSource;
use Cake\Database\Type\EnumType;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use Override;

/**
 * ConnectionHistory Model
 *
 * @property \App\Model\Table\CustomersTable&\Cake\ORM\Association\BelongsTo $Customers
 * @property \App\Model\Table\ContractsTable&\Cake\ORM\Association\BelongsTo $Contracts
 * @method \App\Model\Entity\ConnectionHistory newEmptyEntity()
 * @method \App\Model\Entity\ConnectionHistory newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\ConnectionHistory> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\ConnectionHistory get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\ConnectionHistory findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\ConnectionHistory patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\ConnectionHistory> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\ConnectionHistory|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\ConnectionHistory saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\ConnectionHistory>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\ConnectionHistory>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\ConnectionHistory>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\ConnectionHistory> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\ConnectionHistory>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\ConnectionHistory>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\ConnectionHistory>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\ConnectionHistory> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ConnectionHistoryTable extends AppTable
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

        // singular on purpose, the table holds a history rather than histories
        $this->setTable('connection_history');
        $this->setDisplayField('source_reference');
        $this->setPrimaryKey('id');

        $this->getSchema()->setColumnType(
            'source',
            EnumType::from(ConnectionHistorySource::class),
        );
        $this->getSchema()->setColumnType(
            'first_seen_source',
            EnumType::from(FirstSeenSource::class),
        );

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');

        $this->belongsTo('Customers', [
            'foreignKey' => 'customer_id',
        ]);
        $this->belongsTo('Contracts', [
            'foreignKey' => 'contract_id',
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
            ->notEmptyString('source');

        $validator
            ->scalar('source_reference')
            ->maxLength('source_reference', 255)
            ->requirePresence('source_reference', 'create')
            ->notEmptyString('source_reference');

        $validator
            ->uuid('account_id')
            ->allowEmptyString('account_id');

        $validator
            ->uuid('customer_id')
            ->allowEmptyString('customer_id');

        $validator
            ->uuid('contract_id')
            ->allowEmptyString('contract_id');

        $validator
            ->scalar('station_id')
            ->maxLength('station_id', 255)
            ->allowEmptyString('station_id');

        $validator
            ->scalar('called_station_id')
            ->maxLength('called_station_id', 255)
            ->allowEmptyString('called_station_id');

        $validator
            ->scalar('nas_ip_address')
            ->maxLength('nas_ip_address', 39)
            ->allowEmptyString('nas_ip_address');

        $validator
            ->scalar('nas_port_id')
            ->maxLength('nas_port_id', 255)
            ->allowEmptyString('nas_port_id');

        $validator
            ->scalar('ip_address')
            ->maxLength('ip_address', 39)
            ->allowEmptyString('ip_address');

        $validator
            ->scalar('ipv6_prefix')
            ->maxLength('ipv6_prefix', 43)
            ->allowEmptyString('ipv6_prefix');

        $validator
            ->uuid('access_point_id')
            ->allowEmptyString('access_point_id');

        $validator
            ->scalar('access_point_name')
            ->maxLength('access_point_name', 255)
            ->allowEmptyString('access_point_name');

        $validator
            ->uuid('routeros_device_id')
            ->allowEmptyString('routeros_device_id');

        $validator
            ->scalar('routeros_device_name')
            ->maxLength('routeros_device_name', 255)
            ->allowEmptyString('routeros_device_name');

        $validator
            ->dateTime('first_seen')
            ->requirePresence('first_seen', 'create')
            ->notEmptyDateTime('first_seen');

        $validator
            ->notEmptyString('first_seen_source');

        $validator
            ->dateTime('last_seen')
            ->requirePresence('last_seen', 'create')
            ->notEmptyDateTime('last_seen');

        $validator
            ->dateTime('enriched')
            ->allowEmptyDateTime('enriched');

        $validator
            ->uuid('created_by')
            ->allowEmptyString('created_by');

        $validator
            ->uuid('modified_by')
            ->allowEmptyString('modified_by');

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
        $rules->add($rules->existsIn(['customer_id'], 'Customers'), ['errorField' => 'customer_id']);
        $rules->add($rules->existsIn(['contract_id'], 'Contracts'), ['errorField' => 'contract_id']);

        // an account cannot start two intervals at the same moment
        $rules->add(
            $rules->isUnique(
                ['source', 'source_reference', 'first_seen'],
                __('This interval has already been recorded.'),
            ),
            ['errorField' => 'first_seen'],
        );

        // an interval that ends before it starts would be a bug in the update
        $rules->add(
            fn($entity): bool => $entity->last_seen >= $entity->first_seen,
            'lastSeenAfterFirstSeen',
            [
                'errorField' => 'last_seen',
                'message' => __('The end of the interval must not precede its start.'),
            ],
        );

        return $rules;
    }

    /**
     * The most recent interval recorded for an account, which is the one an
     * incoming interval may be able to extend.
     *
     * @param \App\Model\Enum\ConnectionHistorySource $source Source of the interval.
     * @param string $sourceReference Identity of the account within the source.
     * @return \App\Model\Entity\ConnectionHistory|null
     */
    public function getLatestForAccount(
        ConnectionHistorySource $source,
        string $sourceReference,
    ): ?ConnectionHistory {
        /** @var \App\Model\Entity\ConnectionHistory|null $latest */
        $latest = $this->find()
            ->where([
                'source' => $source->value,
                'source_reference' => $sourceReference,
            ])
            ->orderBy(['first_seen' => 'DESC'])
            ->first();

        return $latest;
    }

    /**
     * Find intervals belonging to a customer, newest first.
     *
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\ConnectionHistory> $query Base query.
     * @param string $customerId Customer to list the history for.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\ConnectionHistory>
     */
    public function findForCustomer(SelectQuery $query, string $customerId): SelectQuery
    {
        return $query
            ->where(['ConnectionHistory.customer_id' => $customerId])
            ->orderBy(['ConnectionHistory.first_seen' => 'DESC']);
    }

    /**
     * Find intervals belonging to a contract, newest first.
     *
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\ConnectionHistory> $query Base query.
     * @param string $contractId Contract to list the history for.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\ConnectionHistory>
     */
    public function findForContract(SelectQuery $query, string $contractId): SelectQuery
    {
        return $query
            ->where(['ConnectionHistory.contract_id' => $contractId])
            ->orderBy(['ConnectionHistory.first_seen' => 'DESC']);
    }

    /**
     * Find intervals sharing a station identifier regardless of the account.
     *
     * The same station turning up under several accounts is worth noticing, it
     * usually means either a mistake in the configuration or equipment that has
     * moved somewhere it should not have.
     *
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\ConnectionHistory> $query Base query.
     * @param string $stationId Station identifier to look for.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\ConnectionHistory>
     */
    public function findForStation(SelectQuery $query, string $stationId): SelectQuery
    {
        return $query
            ->where(['ConnectionHistory.station_id' => $stationId])
            ->orderBy(['ConnectionHistory.first_seen' => 'DESC']);
    }
}
