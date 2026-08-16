<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Task;
use App\Model\Entity\TaskType;
use Cake\Datasource\EntityInterface;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use Override;

/**
 * Tasks Model
 *
 * @property \App\Model\Table\TaskTypesTable&\Cake\ORM\Association\BelongsTo $TaskTypes
 * @property \App\Model\Table\CustomersTable&\Cake\ORM\Association\BelongsTo $Customers
 * @property \App\Model\Table\ContractsTable&\Cake\ORM\Association\BelongsTo $Contracts
 * @property \App\Model\Table\CustomersTable&\Cake\ORM\Association\BelongsTo $Dealers
 * @property \App\Model\Table\TaskStatesTable&\Cake\ORM\Association\BelongsTo $TaskStates
 * @method \App\Model\Entity\Task newEmptyEntity()
 * @method \App\Model\Entity\Task newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Task[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Task get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Task findOrCreate($search, callable|array|null $callback = null, $options = [])
 * @method \App\Model\Entity\Task patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Task[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Task|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Task saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<\App\Model\Entity\Task>|false saveMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\Task> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\Task>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\Task> deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class TasksTable extends AppTable
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
        $this->belongsTo('Contracts', [
            'foreignKey' => 'contract_id',
        ]);
        $this->belongsTo('Dealers', [
            'className' => 'Customers',
            'foreignKey' => 'dealer_id',
            'conditions' => ['Dealers.dealer IN' => [1, 2]],
        ]);
        $this->belongsTo('TaskStates', [
            'foreignKey' => 'task_state_id',
            'joinType' => 'INNER',
        ]);
    }

    /**
     * Tasks nobody has finished yet.
     *
     * Whether a task is done is a property of its state, not of the task, so the state
     * is joined in rather than left to the caller.
     *
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Task> $query The query to scope.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Task>
     */
    public function findActive(SelectQuery $query): SelectQuery
    {
        return $query
            ->contain(['TaskStates'])
            ->where(['TaskStates.completed' => false]);
    }

    /**
     * Tasks a dealer is holding.
     *
     * Tasks are assigned to a dealer rather than to a user, so the caller passes the
     * `customer_id` of the identity it asks on behalf of.
     *
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Task> $query The query to scope.
     * @param string $dealer_id The dealer the tasks belong to.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Task>
     */
    public function findForDealer(SelectQuery $query, string $dealer_id): SelectQuery
    {
        return $query->where(['Tasks.dealer_id' => $dealer_id]);
    }

    /**
     * Tasks nobody holds.
     *
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Task> $query The query to scope.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Task>
     */
    public function findUnassigned(SelectQuery $query): SelectQuery
    {
        return $query->where(['Tasks.dealer_id IS' => null]);
    }

    /**
     * Tasks that want attention: a deadline near or past, an expected date already gone
     * by, or an urgent mark whatever the dates say.
     *
     * The two dates are asked differently on purpose. A critical date is what was promised,
     * so it is worth raising before it is missed. An expected date is only a plan, and a
     * plan for next week is not news - it becomes news once it has slipped.
     *
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Task> $query The query to scope.
     * @param int $within_days How far ahead a deadline still counts as pressing.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Task>
     */
    public function findPressing(SelectQuery $query, int $within_days): SelectQuery
    {
        return $query->where([
            'OR' => [
                // a deadline is a promise, so it is raised before it is broken
                'Tasks.critical_date <=' => Date::today()->addDays($within_days),
                // an estimate is a plan; planning something for next week is not news,
                // the plan having slipped is
                'Tasks.estimated_date <' => Date::today(),
                'Tasks.priority >=' => Task::PRIORITY_URGENT,
            ],
        ]);
    }

    /**
     * Tasks that have lain untouched for a while.
     *
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Task> $query The query to scope.
     * @param int $days How long a task may lie before it counts as stale.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Task>
     */
    public function findStale(SelectQuery $query, int $days): SelectQuery
    {
        return $query->where(['Tasks.modified <' => DateTime::now()->subDays($days)]);
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
            ->uuid('id')
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
            ->scalar('email')
            ->allowEmptyString('email');

        $validator
            ->scalar('phone')
            ->allowEmptyString('phone');

        $validator
            ->date('finish_date')
            ->allowEmptyDate('finish_date');

        $validator
            ->date('start_date')
            ->allowEmptyDate('start_date');

        $validator
            ->date('estimated_date')
            ->allowEmptyDate('estimated_date');

        $validator
            ->date('critical_date')
            ->allowEmptyDate('critical_date');

        $validator
            ->uuid('access_point_id')
            ->allowEmptyString('access_point_id');

        $validator
            ->uuid('task_type_id')
            ->requirePresence('task_type_id', 'create')
            ->notEmptyString('task_type_id');

        $validator
            ->uuid('task_state_id')
            ->requirePresence('task_state_id', 'create')
            ->notEmptyString('task_state_id');

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
        $rules->add($rules->isUnique(['id']), ['errorField' => 'id']);
        $rules->add($rules->existsIn(['task_type_id'], 'TaskTypes'), ['errorField' => 'task_type_id']);
        $rules->add($rules->existsIn(['customer_id'], 'Customers'), ['errorField' => 'customer_id']);
        $rules->add($rules->existsIn(['contract_id'], 'Contracts'), ['errorField' => 'contract_id']);
        $rules->add($rules->existsIn(['dealer_id'], 'Dealers'), ['errorField' => 'dealer_id']);
        $rules->add($rules->existsIn(['task_state_id'], 'TaskStates'), ['errorField' => 'task_state_id']);

        $rules->add(
            function ($entity, $_options): bool {
                // load task type
                $task_type = $this->findTaskType($entity);
                // a type that is not there is for existsIn above to report, not for this rule
                if ($task_type === null) {
                    return true;
                }

                // check if customer required for this task type
                if ($task_type->customer_required) {
                    return !empty($entity->customer_id);
                }

                return true;
            },
            'isRequiredCustomerFilled',
            [
                'errorField' => 'customer_id',
                'message' => __('The specified task type requires the assignment of an customer.'),
            ],
        );

        $rules->add(
            function ($entity, $_options): bool {
                // load task type
                $task_type = $this->findTaskType($entity);
                // a type that is not there is for existsIn above to report, not for this rule
                if ($task_type === null) {
                    return true;
                }

                // check if contract required for this task type
                if ($task_type->contract_required) {
                    return !empty($entity->contract_id);
                }

                return true;
            },
            'isRequiredContractFilled',
            [
                'errorField' => 'contract_id',
                'message' => __('The specified task type requires the assignment of an contract.'),
            ],
        );

        return $rules;
    }

    /**
     * The task type a task names, or null where it names none or one that is not there.
     *
     * The rules asking what a task type requires run whatever the `existsIn` above made of the same
     * field - a checker runs every rule it holds rather than stopping at the first one to fail.
     * Reading the type with `get()` therefore threw out of the rules rather than failing them, and
     * a caller waiting for a `false` got an exception instead.
     *
     * @param \Cake\Datasource\EntityInterface $entity The task being saved.
     * @return \App\Model\Entity\TaskType|null
     */
    private function findTaskType(EntityInterface $entity): ?TaskType
    {
        $task_type_id = $entity->get('task_type_id');
        if ($task_type_id === null) {
            return null;
        }

        /** @var \App\Model\Entity\TaskType|null $task_type */
        $task_type = $this->TaskTypes->find()
            ->where(['TaskTypes.id' => $task_type_id])
            ->first();

        return $task_type;
    }
}
