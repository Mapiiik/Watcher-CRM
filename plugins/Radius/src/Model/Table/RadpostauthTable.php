<?php
declare(strict_types=1);

namespace Radius\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

/**
 * Radpostauth Model
 *
 * @property \Radius\Model\Table\AccountsTable&\Cake\ORM\Association\BelongsTo $Accounts
 * @method \Radius\Model\Entity\Radpostauth newEmptyEntity()
 * @method \Radius\Model\Entity\Radpostauth newEntity(array $data, array $options = [])
 * @method \Radius\Model\Entity\Radpostauth[] newEntities(array $data, array $options = [])
 * @method \Radius\Model\Entity\Radpostauth get($primaryKey, $options = [])
 * @method \Radius\Model\Entity\Radpostauth findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Radius\Model\Entity\Radpostauth patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Radius\Model\Entity\Radpostauth[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Radius\Model\Entity\Radpostauth|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Radius\Model\Entity\Radpostauth saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Radius\Model\Entity\Radpostauth[]|iterable<\Cake\Datasource\EntityInterface>|false saveMany(iterable $entities, $options = [])
 * @method \Radius\Model\Entity\Radpostauth[]|iterable<\Cake\Datasource\EntityInterface> saveManyOrFail(iterable $entities, $options = [])
 * @method \Radius\Model\Entity\Radpostauth[]|iterable<\Cake\Datasource\EntityInterface>|false deleteMany(iterable $entities, $options = [])
 * @method \Radius\Model\Entity\Radpostauth[]|iterable<\Cake\Datasource\EntityInterface> deleteManyOrFail(iterable $entities, $options = [])
 */
class RadpostauthTable extends AppTable
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

        $this->setTable('radpostauth');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

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
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('username')
            ->requirePresence('username', 'create')
            ->notEmptyString('username');

        $validator
            ->scalar('pass')
            ->allowEmptyString('pass');

        $validator
            ->scalar('reply')
            ->allowEmptyString('reply');

        $validator
            ->scalar('calledstationid')
            ->allowEmptyString('calledstationid');

        $validator
            ->scalar('callingstationid')
            ->allowEmptyString('callingstationid');

        $validator
            ->dateTime('authdate')
            ->notEmptyDateTime('authdate');

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
