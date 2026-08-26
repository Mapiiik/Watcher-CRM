<?php
declare(strict_types=1);

namespace Tasks\Model\Table;

use App\Messages\Messages;
use App\Model\Table\AppTable;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use Override;
use Tasks\Model\Entity\Task;
use Tasks\Service\CompletedTaskReport;

/**
 * Tasks Model
 *
 * @property \App\Model\Table\TaskStatesTable&\Cake\ORM\Association\BelongsTo $TaskStates
 * @property \App\Model\Table\TaskTypesTable&\Cake\ORM\Association\BelongsTo $TaskTypes
 * @property \App\Model\Table\AppUsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @method \Tasks\Model\Entity\Task newEmptyEntity()
 * @method \Tasks\Model\Entity\Task newEntity(array $data, array $options = [])
 * @method \Tasks\Model\Entity\Task[] newEntities(array $data, array $options = [])
 * @method \Tasks\Model\Entity\Task get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \Tasks\Model\Entity\Task findOrCreate($search, callable|array|null $callback = null, $options = [])
 * @method \Tasks\Model\Entity\Task patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Tasks\Model\Entity\Task[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Tasks\Model\Entity\Task|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Tasks\Model\Entity\Task saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<\Tasks\Model\Entity\Task>|false saveMany(iterable $entities, $options = [])
 * @method iterable<\Tasks\Model\Entity\Task> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<\Tasks\Model\Entity\Task>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<\Tasks\Model\Entity\Task> deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class TasksTable extends AppTable
{
    /**
     * What saving a task had to say for itself.
     *
     * Saving a task can send mail, and whoever asked for the save is the one who should hear how
     * that went - but the model has no Flash and no console. It leaves the outcome here instead
     * and lets the layer above render it: `handleMessages()` in a controller or in a command.
     */
    public Messages $Messages;

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

        $this->Messages = new Messages();

        $this->setTable('tasks');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');

        $this->belongsTo('TaskStates', [
            'foreignKey' => 'task_state_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('TaskTypes', [
            'foreignKey' => 'task_type_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Users', [
            'className' => 'AppUsers',
            'foreignKey' => 'user_id',
        ]);
    }

    /**
     * Tasks nobody has finished yet.
     *
     * Whether a task is done is a property of its state, not of the task, so the state
     * is joined in rather than left to the caller.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Tasks\Model\Entity\Task> $query The query to scope.
     * @return \Cake\ORM\Query\SelectQuery<\Tasks\Model\Entity\Task>
     */
    public function findActive(SelectQuery $query): SelectQuery
    {
        return $query
            ->contain(['TaskStates'])
            ->where(['TaskStates.completed' => false]);
    }

    /**
     * Tasks a user is holding.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Tasks\Model\Entity\Task> $query The query to scope.
     * @param string $user_id The user the tasks belong to.
     * @return \Cake\ORM\Query\SelectQuery<\Tasks\Model\Entity\Task>
     */
    public function findForUser(SelectQuery $query, string $user_id): SelectQuery
    {
        return $query->where(['Tasks.user_id' => $user_id]);
    }

    /**
     * Tasks nobody holds.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Tasks\Model\Entity\Task> $query The query to scope.
     * @return \Cake\ORM\Query\SelectQuery<\Tasks\Model\Entity\Task>
     */
    public function findUnassigned(SelectQuery $query): SelectQuery
    {
        return $query->where(['Tasks.user_id IS' => null]);
    }

    /**
     * Tasks that want attention: a deadline near or past, an expected date already gone
     * by, or an urgent mark whatever the dates say.
     *
     * The two dates are asked differently on purpose. A critical date is what was promised,
     * so it is worth raising before it is missed. An expected date is only a plan, and a
     * plan for next week is not news - it becomes news once it has slipped.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Tasks\Model\Entity\Task> $query The query to scope.
     * @param int $within_days How far ahead a deadline still counts as pressing.
     * @return \Cake\ORM\Query\SelectQuery<\Tasks\Model\Entity\Task>
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
     * @param \Cake\ORM\Query\SelectQuery<\Tasks\Model\Entity\Task> $query The query to scope.
     * @param int $days How long a task may lie before it counts as stale.
     * @return \Cake\ORM\Query\SelectQuery<\Tasks\Model\Entity\Task>
     */
    public function findStale(SelectQuery $query, int $days): SelectQuery
    {
        return $query->where(['Tasks.modified <' => DateTime::now()->subDays($days)]);
    }

    /**
     * What a task has to be read together with for its summary line to say anything.
     *
     * The line is written by the entity out of whatever the application files a task under,
     * so the application is what knows. Whoever draws a task without going through a page of
     * its own - a dashboard card, a map - asks here rather than spelling it out again.
     *
     * @return array<mixed>
     */
    public function summaryContain(): array
    {
        return ['TaskTypes'];
    }

    /**
     * What a task has to be read together with for an email about it to say anything.
     *
     * The shared part is here; an application that files tasks under things of its own adds them,
     * the same way it does for the summary line.
     *
     * @return array<mixed>
     */
    public function reportContain(): array
    {
        return ['TaskTypes', 'TaskStates', 'Users', 'Creators', 'Modifiers'];
    }

    /**
     * Tell whoever asked when a task has just been closed.
     *
     * What counts is the *move* into a closed state, not being in one: reporting the latter would
     * send mail on every later save of a task that is already done. So the state it is leaving is
     * asked about too, and only open -> closed is news.
     *
     * A task filed straight into a closed state is reported as well. The work is finished either
     * way, and whatever has to follow it - an invoice, equipment booked back in - needs to know,
     * whether or not anybody ever saw the task open.
     *
     * This runs after the transaction has committed, so the entity is still dirty and its
     * originals are still readable: CakePHP cleans it only once this has been dispatched.
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event The event that was fired.
     * @param \Cake\Datasource\EntityInterface $entity The task that was saved.
     * @return void
     */
    public function afterSaveCommit(EventInterface $event, EntityInterface $entity): void
    {
        if (!$entity instanceof Task || !$entity->isDirty('task_state_id')) {
            return;
        }

        $wasCompleted = !$entity->isNew()
            && $this->TaskStates->get($entity->getOriginal('task_state_id'))->completed;

        if ($wasCompleted || !$this->TaskStates->get($entity->task_state_id)->completed) {
            return;
        }

        $task = $this->get($entity->id, contain: $this->reportContain());

        if ($task->task_type->report_on_completion) {
            CompletedTaskReport::send($task, $this->Messages);
        }
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
            ->uuid('task_state_id')
            ->requirePresence('task_state_id', 'create')
            ->notEmptyString('task_state_id');

        $validator
            ->uuid('task_type_id')
            ->requirePresence('task_type_id', 'create')
            ->notEmptyString('task_type_id');

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
            ->date('start_date')
            ->allowEmptyDate('start_date');

        $validator
            ->date('finish_date')
            ->allowEmptyDate('finish_date');

        $validator
            ->date('estimated_date')
            ->allowEmptyDate('estimated_date');

        $validator
            ->date('critical_date')
            ->allowEmptyDate('critical_date');

        $validator
            ->uuid('access_point_id')
            ->allowEmptyString('access_point_id');

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
        $rules->add($rules->existsIn(['task_state_id'], 'TaskStates'), ['errorField' => 'task_state_id']);
        $rules->add($rules->existsIn(['task_type_id'], 'TaskTypes'), ['errorField' => 'task_type_id']);
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);

        return $rules;
    }
}
