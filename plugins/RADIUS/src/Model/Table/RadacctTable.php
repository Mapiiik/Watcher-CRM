<?php
declare(strict_types=1);

namespace RADIUS\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Radacct Model
 *
 * @method \RADIUS\Model\Entity\Radacct newEmptyEntity()
 * @method \RADIUS\Model\Entity\Radacct newEntity(array $data, array $options = [])
 * @method \RADIUS\Model\Entity\Radacct[] newEntities(array $data, array $options = [])
 * @method \RADIUS\Model\Entity\Radacct get($primaryKey, $options = [])
 * @method \RADIUS\Model\Entity\Radacct findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \RADIUS\Model\Entity\Radacct patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \RADIUS\Model\Entity\Radacct[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \RADIUS\Model\Entity\Radacct|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \RADIUS\Model\Entity\Radacct saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \RADIUS\Model\Entity\Radacct[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \RADIUS\Model\Entity\Radacct[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \RADIUS\Model\Entity\Radacct[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \RADIUS\Model\Entity\Radacct[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class RadacctTable extends Table
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

        $this->setTable('radacct');
        $this->setDisplayField('radacctid');
        $this->setPrimaryKey('radacctid');
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
            ->maxLength('acctsessionid', 32)
            ->requirePresence('acctsessionid', 'create')
            ->notEmptyString('acctsessionid');

        $validator
            ->scalar('acctuniqueid')
            ->maxLength('acctuniqueid', 32)
            ->requirePresence('acctuniqueid', 'create')
            ->notEmptyString('acctuniqueid');

        $validator
            ->scalar('username')
            ->maxLength('username', 253)
            ->allowEmptyString('username');

        $validator
            ->scalar('realm')
            ->maxLength('realm', 64)
            ->allowEmptyString('realm');

        $validator
            ->scalar('nasipaddress')
            ->maxLength('nasipaddress', 39)
            ->requirePresence('nasipaddress', 'create')
            ->notEmptyString('nasipaddress');

        $validator
            ->scalar('nasportid')
            ->maxLength('nasportid', 15)
            ->allowEmptyString('nasportid');

        $validator
            ->scalar('nasporttype')
            ->maxLength('nasporttype', 32)
            ->allowEmptyString('nasporttype');

        $validator
            ->dateTime('acctstarttime')
            ->allowEmptyDateTime('acctstarttime');

        $validator
            ->dateTime('acctstoptime')
            ->allowEmptyDateTime('acctstoptime');

        $validator
            ->allowEmptyString('acctsessiontime');

        $validator
            ->scalar('acctauthentic')
            ->maxLength('acctauthentic', 32)
            ->allowEmptyString('acctauthentic');

        $validator
            ->scalar('connectinfo_start')
            ->maxLength('connectinfo_start', 50)
            ->allowEmptyString('connectinfo_start');

        $validator
            ->scalar('connectinfo_stop')
            ->maxLength('connectinfo_stop', 50)
            ->allowEmptyString('connectinfo_stop');

        $validator
            ->allowEmptyString('acctinputoctets');

        $validator
            ->allowEmptyString('acctoutputoctets');

        $validator
            ->scalar('calledstationid')
            ->maxLength('calledstationid', 50)
            ->allowEmptyString('calledstationid');

        $validator
            ->scalar('callingstationid')
            ->maxLength('callingstationid', 50)
            ->allowEmptyString('callingstationid');

        $validator
            ->scalar('acctterminatecause')
            ->maxLength('acctterminatecause', 32)
            ->allowEmptyString('acctterminatecause');

        $validator
            ->scalar('servicetype')
            ->maxLength('servicetype', 32)
            ->allowEmptyString('servicetype');

        $validator
            ->scalar('framedprotocol')
            ->maxLength('framedprotocol', 32)
            ->allowEmptyString('framedprotocol');

        $validator
            ->scalar('framedipaddress')
            ->maxLength('framedipaddress', 39)
            ->allowEmptyString('framedipaddress');

        $validator
            ->allowEmptyString('acctstartdelay');

        $validator
            ->allowEmptyString('acctstopdelay');

        $validator
            ->scalar('groupname')
            ->maxLength('groupname', 253)
            ->allowEmptyString('groupname');

        $validator
            ->scalar('xascendsessionsvrkey')
            ->maxLength('xascendsessionsvrkey', 10)
            ->allowEmptyString('xascendsessionsvrkey');

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
        $rules->add($rules->isUnique(['username']), ['errorField' => 'username']);

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
