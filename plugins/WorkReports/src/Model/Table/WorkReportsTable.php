<?php
declare(strict_types=1);

namespace WorkReports\Model\Table;

use App\Model\Table\AppTable;
use Cake\Database\Type\EnumType;
use Cake\I18n\Date;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use Override;
use WorkReports\Model\Entity\WorkReport;
use WorkReports\Model\Enum\WorkReportState;

/**
 * WorkReports Model
 *
 * @property \App\Model\Table\AppUsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\AppUsersTable&\Cake\ORM\Association\BelongsTo $Submitters
 * @property \WorkReports\Model\Table\WorkReportItemsTable&\Cake\ORM\Association\HasMany $WorkReportItems
 * @property \WorkReports\Model\Table\WorkReportOnCallsTable&\Cake\ORM\Association\HasMany $WorkReportOnCalls
 * @method \WorkReports\Model\Entity\WorkReport newEmptyEntity()
 * @method \WorkReports\Model\Entity\WorkReport get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \WorkReports\Model\Entity\WorkReport patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \WorkReports\Model\Entity\WorkReport|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \WorkReports\Model\Entity\WorkReport saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 */
class WorkReportsTable extends AppTable
{
    use LocatorAwareTrait;

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

        $this->setTable('work_reports');
        $this->setDisplayField('month');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');

        $this->getSchema()->setColumnType('state', EnumType::from(WorkReportState::class));

        $this->belongsTo('Users', [
            'className' => 'AppUsers',
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Submitters', [
            'className' => 'AppUsers',
            'foreignKey' => 'submitted_by',
        ]);
        $this->hasMany('WorkReportItems', [
            'className' => 'WorkReports.WorkReportItems',
            'foreignKey' => 'work_report_id',
            'dependent' => true,
            'sort' => ['WorkReportItems.date', 'WorkReportItems.work_from'],
        ]);
        $this->hasMany('WorkReportOnCalls', [
            'className' => 'WorkReports.WorkReportOnCalls',
            'foreignKey' => 'work_report_id',
            'dependent' => true,
            'sort' => ['WorkReportOnCalls.date'],
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
            ->uuid('user_id')
            ->requirePresence('user_id', 'create')
            ->notEmptyString('user_id');

        $validator
            ->date('month')
            ->requirePresence('month', 'create')
            ->notEmptyDate('month')
            ->add('month', 'firstDay', [
                'rule' => fn($value): bool => (new Date($value))->day === 1,
                'message' => __d('work_reports', 'A report starts on the first day of a month.'),
            ]);

        $validator
            ->decimal('workload')
            ->greaterThan('workload', 0)
            ->notEmptyString('workload');

        $validator
            ->scalar('note')
            ->allowEmptyString('note');

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
        $rules->add($rules->isUnique(['user_id', 'month']), ['errorField' => 'month']);
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);

        return $rules;
    }

    /**
     * The report of the user for the month the day falls in, if there is one.
     *
     * @param string $userId Worker.
     * @param \Cake\I18n\Date $day Any day of the month.
     * @return \WorkReports\Model\Entity\WorkReport|null
     */
    public function findFor(string $userId, Date $day): ?WorkReport
    {
        /** @var \WorkReports\Model\Entity\WorkReport|null */
        return $this->find()
            ->where(['user_id' => $userId, 'month' => $day->firstOfMonth()])
            ->first();
    }

    /**
     * The report of the user for the month the day falls in, started when there is none yet.
     *
     * A report comes to be with its first item, at the workload the worker has at that moment.
     *
     * @param string $userId Worker.
     * @param \Cake\I18n\Date $day Any day of the month.
     * @return \WorkReports\Model\Entity\WorkReport
     */
    public function findOrCreateFor(string $userId, Date $day): WorkReport
    {
        $report = $this->findFor($userId, $day);
        if ($report !== null) {
            return $report;
        }

        /** @var \WorkReports\Model\Table\WorkReportWorkersTable $workers */
        $workers = $this->fetchTable('WorkReports.WorkReportWorkers');

        $report = $this->newEntity([
            'user_id' => $userId,
            'month' => $day->firstOfMonth(),
            'workload' => $workers->workloadOf($userId),
        ]);
        $report->state = WorkReportState::Open;

        return $this->saveOrFail($report);
    }
}
