<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Tasks Model
 *
 * @property \App\Model\Table\TaskTypesTable&\Cake\ORM\Association\BelongsTo $TaskTypes
 * @property \App\Model\Table\CustomersTable&\Cake\ORM\Association\BelongsTo $Customers
 * @property \App\Model\Table\DealersTable&\Cake\ORM\Association\BelongsTo $Dealers
 * @property \App\Model\Table\TaskStatesTable&\Cake\ORM\Association\BelongsTo $TaskStates
 * @property \App\Model\Table\RoutersTable&\Cake\ORM\Association\BelongsTo $Routers
 *
 * @method \App\Model\Entity\Task newEmptyEntity()
 * @method \App\Model\Entity\Task newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Task[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Task get($primaryKey, $options = [])
 * @method \App\Model\Entity\Task findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Task patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Task[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Task|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Task saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Task[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Task[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Task[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Task[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class TasksTable extends Table
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

        $this->setTable('tasks');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');

        $this->belongsTo('TaskTypes', [
            'foreignKey' => 'task_type_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Customers', [
            'foreignKey' => 'customer_id',
        ]);
        $this->belongsTo('Dealers', [
            'className' => 'Customers',
            'foreignKey' => 'dealer_id',
            'conditions' => ['Dealers.dealer' => 1],
        ]);
        $this->belongsTo('TaskStates', [
            'foreignKey' => 'task_state_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Routers', [
            'foreignKey' => 'router_id',
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
            ->allowEmptyString('id', null, 'create')
            ->add('id', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('subject')
            ->allowEmptyString('subject');

        $validator
            ->scalar('text')
            ->allowEmptyString('text');

        $validator
            ->integer('priority')
            ->notEmptyString('priority');

        $validator
            ->integer('modified_by')
            ->notEmptyString('modified_by');

        $validator
            ->integer('created_by')
            ->notEmptyString('created_by');

        $validator
            ->email('email')
            ->allowEmptyString('email');

        $validator
            ->scalar('phone')
            ->allowEmptyString('phone');

        $validator
            ->dateTime('finish_date')
            ->allowEmptyDateTime('finish_date');

        $validator
            ->dateTime('start_date')
            ->allowEmptyDateTime('start_date');

        $validator
            ->dateTime('estimated_date')
            ->allowEmptyDateTime('estimated_date');

        $validator
            ->dateTime('critical_date')
            ->allowEmptyDateTime('critical_date');

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
        $rules->add($rules->isUnique(['id']), ['errorField' => 'id']);
        $rules->add($rules->existsIn(['task_type_id'], 'TaskTypes'), ['errorField' => 'task_type_id']);
        $rules->add($rules->existsIn(['customer_id'], 'Customers'), ['errorField' => 'customer_id']);
        $rules->add($rules->existsIn(['dealer_id'], 'Dealers'), ['errorField' => 'dealer_id']);
        $rules->add($rules->existsIn(['task_state_id'], 'TaskStates'), ['errorField' => 'task_state_id']);
        $rules->add($rules->existsIn(['router_id'], 'Routers'), ['errorField' => 'router_id']);

        return $rules;
    }
}
