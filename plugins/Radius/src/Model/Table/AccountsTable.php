<?php
declare(strict_types=1);

namespace Radius\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

/**
 * Accounts Model
 *
 * @property \App\Model\Table\CustomersTable&\Cake\ORM\Association\BelongsTo $Customers
 * @property \App\Model\Table\ContractsTable&\Cake\ORM\Association\BelongsTo $Contracts
 * @property \CakeDC\Users\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Creators
 * @property \CakeDC\Users\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Modifiers
 * @method \Radius\Model\Entity\Account newEmptyEntity()
 * @method \Radius\Model\Entity\Account newEntity(array $data, array $options = [])
 * @method \Radius\Model\Entity\Account[] newEntities(array $data, array $options = [])
 * @method \Radius\Model\Entity\Account get($primaryKey, $options = [])
 * @method \Radius\Model\Entity\Account findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Radius\Model\Entity\Account patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Radius\Model\Entity\Account[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Radius\Model\Entity\Account|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Radius\Model\Entity\Account saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Radius\Model\Entity\Account[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \Radius\Model\Entity\Account[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \Radius\Model\Entity\Account[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \Radius\Model\Entity\Account[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class AccountsTable extends AppTable
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

        $this->setTable('accounts');
        $this->setDisplayField('username');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');

        $this->belongsTo('Customers', [
            'foreignKey' => 'customer_id',
            'strategy' => 'select',
        ]);
        $this->belongsTo('Contracts', [
            'foreignKey' => 'contract_id',
            'strategy' => 'select',
        ]);
        $this->belongsTo('Creators', [
            'className' => 'CakeDC/Users.Users',
            'foreignKey' => 'created_by',
            'strategy' => 'select',
        ]);
        $this->belongsTo('Modifiers', [
            'className' => 'CakeDC/Users.Users',
            'foreignKey' => 'modified_by',
            'strategy' => 'select',
        ]);
        $this->hasMany('Radius.Radcheck', [
            'foreignKey' => 'username',
            'bindingKey' => 'username',
            'dependent' => true,
            'saveStrategy' => 'replace',
        ]);
        $this->hasMany('Radius.Radreply', [
            'foreignKey' => 'username',
            'bindingKey' => 'username',
            'dependent' => true,
            'saveStrategy' => 'replace',
        ]);
        $this->HasMany('Radius.Radusergroup', [
            'foreignKey' => 'username',
            'bindingKey' => 'username',
            'dependent' => true,
            'saveStrategy' => 'replace',
        ]);
        $this->hasMany('Radius.Radpostauth', [
            'foreignKey' => 'username',
            'bindingKey' => 'username',
        ]);
        $this->hasMany('Radius.Radacct', [
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
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('username')
            ->notEmptyString('username')
            ->add('username', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('password')
            ->notEmptyString('password');

        $validator
            ->integer('type')
            ->notEmptyString('type');

        $validator
            ->boolean('active')
            ->notEmptyString('active');

        $validator
            ->integer('customer_id')
            ->notEmptyString('customer_id');

        $validator
            ->integer('contract_id')
            ->notEmptyString('contract_id');

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
        $rules->add($rules->existsIn(['customer_id'], 'Customers'), ['errorField' => 'customer_id']);
        $rules->add($rules->existsIn(['contract_id'], 'Contracts'), ['errorField' => 'contract_id']);

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
