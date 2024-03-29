<?php
declare(strict_types=1);

namespace Radius\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

/**
 * Radacct Model
 *
 * @property \Radius\Model\Table\AccountsTable&\Cake\ORM\Association\BelongsTo $Accounts
 * @method \Radius\Model\Entity\Radacct newEmptyEntity()
 * @method \Radius\Model\Entity\Radacct newEntity(array $data, array $options = [])
 * @method \Radius\Model\Entity\Radacct[] newEntities(array $data, array $options = [])
 * @method \Radius\Model\Entity\Radacct get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \Radius\Model\Entity\Radacct findOrCreate($search, ?callable $callback = null, $options = [])
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
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        //$rules->add($rules->isUnique(['username']), ['errorField' => 'username']);
        $rules->add($rules->isUnique(['acctuniqueid']), ['errorField' => 'acctuniqueid']);

        return $rules;
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
