<?php
declare(strict_types=1);

namespace RADIUS\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Radusergroup Model
 *
 * @property \RADIUS\Model\Table\AccountsTable&\Cake\ORM\Association\BelongsTo $Accounts
 *
 * @method \RADIUS\Model\Entity\Radusergroup newEmptyEntity()
 * @method \RADIUS\Model\Entity\Radusergroup newEntity(array $data, array $options = [])
 * @method \RADIUS\Model\Entity\Radusergroup[] newEntities(array $data, array $options = [])
 * @method \RADIUS\Model\Entity\Radusergroup get($primaryKey, $options = [])
 * @method \RADIUS\Model\Entity\Radusergroup findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \RADIUS\Model\Entity\Radusergroup patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \RADIUS\Model\Entity\Radusergroup[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \RADIUS\Model\Entity\Radusergroup|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \RADIUS\Model\Entity\Radusergroup saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \RADIUS\Model\Entity\Radusergroup[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \RADIUS\Model\Entity\Radusergroup[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \RADIUS\Model\Entity\Radusergroup[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \RADIUS\Model\Entity\Radusergroup[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class RadusergroupTable extends Table
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

        $this->setTable('radusergroup');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');

        $this->belongsTo('RADIUS.Accounts', [
            'foreignKey' => 'username',
            'bindingKey' => 'username',
        ]);
        $this->hasMany('RADIUS.Radgroupcheck', [
            'foreignKey' => 'groupname',
            'bindingKey' => 'groupname',
        ]);
        $this->hasMany('RADIUS.Radgroupreply', [
            'foreignKey' => 'groupname',
            'bindingKey' => 'groupname',
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
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('username')
            ->notEmptyString('username');

        $validator
            ->scalar('groupname')
            ->notEmptyString('groupname');

        $validator
            ->integer('priority')
            ->notEmptyString('priority');

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
