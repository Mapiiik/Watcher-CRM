<?php
declare(strict_types=1);

namespace WorkReports\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use Override;

/**
 * WorkReportOnCalls Model
 *
 * @property \WorkReports\Model\Table\WorkReportsTable&\Cake\ORM\Association\BelongsTo $WorkReports
 * @method \WorkReports\Model\Entity\WorkReportOnCall newEmptyEntity()
 * @method \WorkReports\Model\Entity\WorkReportOnCall get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \WorkReports\Model\Entity\WorkReportOnCall patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \WorkReports\Model\Entity\WorkReportOnCall|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \WorkReports\Model\Entity\WorkReportOnCall saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 */
class WorkReportOnCallsTable extends AppTable
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

        $this->setTable('work_report_on_calls');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');

        $this->belongsTo('WorkReports', [
            'className' => 'WorkReports.WorkReports',
            'foreignKey' => 'work_report_id',
            'joinType' => 'INNER',
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
            ->uuid('work_report_id')
            ->notEmptyString('work_report_id');

        $validator
            ->date('date')
            ->requirePresence('date', 'create')
            ->notEmptyDate('date');

        $validator
            ->decimal('hours')
            ->greaterThanOrEqual('hours', 0)
            ->notEmptyString('hours');

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
        $rules->add($rules->isUnique(['work_report_id', 'date']), ['errorField' => 'date']);
        $rules->add($rules->existsIn(['work_report_id'], 'WorkReports'), ['errorField' => 'work_report_id']);

        return $rules;
    }
}
