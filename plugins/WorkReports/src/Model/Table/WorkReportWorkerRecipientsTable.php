<?php
declare(strict_types=1);

namespace WorkReports\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use Override;

/**
 * WorkReportWorkerRecipients Model
 *
 * @property \WorkReports\Model\Table\WorkReportWorkersTable&\Cake\ORM\Association\BelongsTo $WorkReportWorkers
 * @property \App\Model\Table\AppUsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @method \WorkReports\Model\Entity\WorkReportWorkerRecipient newEmptyEntity()
 * @method \WorkReports\Model\Entity\WorkReportWorkerRecipient get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \WorkReports\Model\Entity\WorkReportWorkerRecipient patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \WorkReports\Model\Entity\WorkReportWorkerRecipient|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \WorkReports\Model\Entity\WorkReportWorkerRecipient saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 */
class WorkReportWorkerRecipientsTable extends AppTable
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

        $this->setTable('work_report_worker_recipients');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');

        $this->belongsTo('WorkReportWorkers', [
            'className' => 'WorkReports.WorkReportWorkers',
            'foreignKey' => 'work_report_worker_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Users', [
            'className' => 'AppUsers',
            'foreignKey' => 'user_id',
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
            ->uuid('work_report_worker_id')
            ->notEmptyString('work_report_worker_id');

        $validator
            ->uuid('user_id')
            ->notEmptyString('user_id');

        $validator
            ->boolean('may_edit')
            ->notEmptyString('may_edit');

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
        $rules->add(
            $rules->isUnique(['work_report_worker_id', 'user_id']),
            ['errorField' => 'user_id'],
        );
        $rules->add(
            $rules->existsIn(['work_report_worker_id'], 'WorkReportWorkers'),
            ['errorField' => 'work_report_worker_id'],
        );
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);

        return $rules;
    }
}
