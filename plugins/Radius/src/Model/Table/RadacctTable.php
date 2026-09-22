<?php
declare(strict_types=1);

namespace Radius\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use DateTimeInterface;
use Override;

/**
 * Radacct Model
 *
 * @property \Radius\Model\Table\AccountsTable&\Cake\ORM\Association\BelongsTo $Accounts
 * @method \Radius\Model\Entity\Radacct newEmptyEntity()
 * @method \Radius\Model\Entity\Radacct newEntity(array $data, array $options = [])
 * @method \Radius\Model\Entity\Radacct[] newEntities(array $data, array $options = [])
 * @method \Radius\Model\Entity\Radacct get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \Radius\Model\Entity\Radacct findOrCreate($search, callable|array|null $callback = null, $options = [])
 * @method \Radius\Model\Entity\Radacct patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Radius\Model\Entity\Radacct[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Radius\Model\Entity\Radacct|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Radius\Model\Entity\Radacct saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<\Radius\Model\Entity\Radacct>|false saveMany(iterable $entities, $options = [])
 * @method iterable<\Radius\Model\Entity\Radacct> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<\Radius\Model\Entity\Radacct>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<\Radius\Model\Entity\Radacct> deleteManyOrFail(iterable $entities, $options = [])
 */
class RadacctTable extends AppTable
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

        $this->setTable('radacct');
        $this->setDisplayField('radacctid');
        $this->setPrimaryKey('radacctid');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');

        $this->belongsTo('Radius.Accounts', [
            'foreignKey' => 'username',
            'bindingKey' => 'username',
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
            ->allowEmptyString('radacctid', null, 'create');

        $validator
            ->scalar('acctsessionid')
            ->requirePresence('acctsessionid', 'create')
            ->notEmptyString('acctsessionid');

        $validator
            ->scalar('acctuniqueid')
            ->requirePresence('acctuniqueid', 'create')
            ->notEmptyString('acctuniqueid')
            ->add('acctuniqueid', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('username')
            ->allowEmptyString('username');

        $validator
            ->scalar('realm')
            ->allowEmptyString('realm');

        $validator
            ->scalar('nasipaddress')
            ->maxLength('nasipaddress', 39)
            ->requirePresence('nasipaddress', 'create')
            ->notEmptyString('nasipaddress');

        $validator
            ->scalar('nasportid')
            ->allowEmptyString('nasportid');

        $validator
            ->scalar('nasporttype')
            ->allowEmptyString('nasporttype');

        $validator
            ->dateTime('acctstarttime')
            ->allowEmptyDateTime('acctstarttime');

        $validator
            ->dateTime('acctupdatetime')
            ->allowEmptyDateTime('acctupdatetime');

        $validator
            ->dateTime('acctstoptime')
            ->allowEmptyDateTime('acctstoptime');

        $validator
            ->allowEmptyString('acctinterval');

        $validator
            ->allowEmptyString('acctsessiontime');

        $validator
            ->scalar('acctauthentic')
            ->allowEmptyString('acctauthentic');

        $validator
            ->scalar('connectinfo_start')
            ->allowEmptyString('connectinfo_start');

        $validator
            ->scalar('connectinfo_stop')
            ->allowEmptyString('connectinfo_stop');

        $validator
            ->allowEmptyString('acctinputoctets');

        $validator
            ->allowEmptyString('acctoutputoctets');

        $validator
            ->scalar('calledstationid')
            ->allowEmptyString('calledstationid');

        $validator
            ->scalar('callingstationid')
            ->allowEmptyString('callingstationid');

        $validator
            ->scalar('acctterminatecause')
            ->allowEmptyString('acctterminatecause');

        $validator
            ->scalar('servicetype')
            ->allowEmptyString('servicetype');

        $validator
            ->scalar('framedprotocol')
            ->allowEmptyString('framedprotocol');

        $validator
            ->scalar('framedipaddress')
            ->maxLength('framedipaddress', 39)
            ->allowEmptyString('framedipaddress');

        $validator
            ->scalar('framedipv6address')
            ->maxLength('framedipv6address', 39)
            ->allowEmptyString('framedipv6address');

        $validator
            ->scalar('framedipv6prefix')
            ->maxLength('framedipv6prefix', 39)
            ->allowEmptyString('framedipv6prefix');

        $validator
            ->scalar('framedinterfaceid')
            ->allowEmptyString('framedinterfaceid');

        $validator
            ->scalar('delegatedipv6prefix')
            ->maxLength('delegatedipv6prefix', 39)
            ->allowEmptyString('delegatedipv6prefix');

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
        //$rules->add($rules->isUnique(['username']), ['errorField' => 'username']);
        $rules->add($rules->isUnique(['acctuniqueid']), ['errorField' => 'acctuniqueid']);

        return $rules;
    }

    /**
     * The octets moved in a period, both ways, by the contract of the account.
     *
     * A session is counted in the share of it that falls in the period. Where it has no stop, the
     * last interim update is where it is known to have got to - the NAS does not send every stop,
     * so an open session is not necessarily one still running. The result is an estimate for that
     * reason, and good enough for the totals the regulator asks for.
     *
     * @param \DateTimeInterface $from Start of the period.
     * @param \DateTimeInterface $to End of the period, exclusive.
     * @return array<string, float> Octets by contract id.
     */
    public function octetsByContract(DateTimeInterface $from, DateTimeInterface $to): array
    {
        $sql = <<<'SQL'
            WITH sessions AS (
                SELECT a.contract_id,
                    COALESCE(r.acctinputoctets, 0) + COALESCE(r.acctoutputoctets, 0) AS octets,
                    r.acctstarttime AS started,
                    GREATEST(COALESCE(r.acctstoptime, r.acctupdatetime, r.acctstarttime), r.acctstarttime) AS ended
                FROM radacct r
                JOIN accounts a ON a.username = r.username
                WHERE a.contract_id IS NOT NULL
                    AND r.acctstarttime < :to
                    AND COALESCE(r.acctstoptime, r.acctupdatetime, r.acctstarttime) >= :from
            )
            SELECT contract_id, SUM(
                CASE WHEN ended = started THEN octets
                ELSE octets * EXTRACT(EPOCH FROM (LEAST(ended, :to) - GREATEST(started, :from)))
                    / EXTRACT(EPOCH FROM (ended - started))
                END
            ) AS octets
            FROM sessions
            GROUP BY contract_id
            SQL;

        $rows = $this->getConnection()->execute($sql, [
            'from' => $from->format('Y-m-d H:i:sP'),
            'to' => $to->format('Y-m-d H:i:sP'),
        ])->fetchAll('assoc');

        $octets = [];
        foreach ($rows as $row) {
            $octets[(string)$row['contract_id']] = max(0.0, (float)$row['octets']);
        }

        return $octets;
    }

    /**
     * Returns the database connection name to use by default.
     *
     * @return string
     */
    #[Override]
    public static function defaultConnectionName(): string
    {
        return 'radius';
    }
}
