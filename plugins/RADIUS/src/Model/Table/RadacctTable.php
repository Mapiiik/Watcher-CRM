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

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');

        $this->belongsTo('RADIUS.Accounts', [
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
