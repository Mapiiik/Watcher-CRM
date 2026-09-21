<?php
declare(strict_types=1);

namespace WorkReports\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use Override;

/**
 * WorkReportWorkers Model
 *
 * @property \App\Model\Table\AppUsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\AppUsersTable&\Cake\ORM\Association\BelongsTo $Supervisors
 * @property \WorkReports\Model\Table\WorkCarsTable&\Cake\ORM\Association\BelongsTo $DefaultPrivateCars
 * @property \WorkReports\Model\Table\WorkCarsTable&\Cake\ORM\Association\BelongsTo $DefaultCompanyCars
 * @method \WorkReports\Model\Entity\WorkReportWorker newEmptyEntity()
 * @method \WorkReports\Model\Entity\WorkReportWorker get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \WorkReports\Model\Entity\WorkReportWorker patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \WorkReports\Model\Entity\WorkReportWorker|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \WorkReports\Model\Entity\WorkReportWorker saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 */
class WorkReportWorkersTable extends AppTable
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

        $this->setTable('work_report_workers');
        $this->setDisplayField('user_id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');

        $this->belongsTo('Users', [
            'className' => 'AppUsers',
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Supervisors', [
            'className' => 'AppUsers',
            'foreignKey' => 'supervisor_id',
        ]);
        $this->belongsTo('DefaultPrivateCars', [
            'className' => 'WorkReports.WorkCars',
            'foreignKey' => 'default_private_car_id',
        ]);
        $this->belongsTo('DefaultCompanyCars', [
            'className' => 'WorkReports.WorkCars',
            'foreignKey' => 'default_company_car_id',
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
            ->decimal('workload')
            ->greaterThan('workload', 0)
            ->notEmptyString('workload');

        $validator
            ->uuid('supervisor_id')
            ->allowEmptyString('supervisor_id');

        $validator
            ->uuid('default_private_car_id')
            ->allowEmptyString('default_private_car_id');

        $validator
            ->uuid('default_company_car_id')
            ->allowEmptyString('default_company_car_id');

        $validator
            ->boolean('active')
            ->notEmptyString('active');

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
        $rules->add($rules->isUnique(['user_id']), ['errorField' => 'user_id']);
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);
        $rules->add($rules->existsIn(['supervisor_id'], 'Supervisors'), ['errorField' => 'supervisor_id']);
        $rules->add(
            $rules->existsIn(['default_private_car_id'], 'DefaultPrivateCars'),
            ['errorField' => 'default_private_car_id'],
        );
        $rules->add(
            $rules->existsIn(['default_company_car_id'], 'DefaultCompanyCars'),
            ['errorField' => 'default_company_car_id'],
        );

        // the car a person drives as their own is one of theirs, the other one the company's
        $rules->add(
            function ($worker): bool {
                if ($worker->default_private_car_id === null) {
                    return true;
                }

                return $this->DefaultPrivateCars->exists([
                    'id' => $worker->default_private_car_id,
                    'owner_id' => $worker->user_id,
                ]);
            },
            'privateCarOwned',
            [
                'errorField' => 'default_private_car_id',
                'message' => __d('work_reports', 'The car is not one of the worker\'s own.'),
            ],
        );
        $rules->add(
            function ($worker): bool {
                if ($worker->default_company_car_id === null) {
                    return true;
                }

                return $this->DefaultCompanyCars->exists([
                    'id' => $worker->default_company_car_id,
                    'owner_id IS' => null,
                ]);
            },
            'companyCarOwned',
            [
                'errorField' => 'default_company_car_id',
                'message' => __d('work_reports', 'The car is not a company car.'),
            ],
        );

        return $rules;
    }

    /**
     * The workload a report of the user starts at: the one stated for the user, or a full one.
     *
     * @param string $userId User to look up.
     * @return string
     */
    public function workloadOf(string $userId): string
    {
        /** @var \WorkReports\Model\Entity\WorkReportWorker|null $worker */
        $worker = $this->find()->where(['user_id' => $userId])->first();

        return $worker === null ? '1' : (string)$worker->workload;
    }

    /**
     * Whether one user is the supervisor of the other.
     *
     * @param string $supervisorId The supervisor asked about.
     * @param string $userId The worker.
     * @return bool
     */
    public function isSupervisorOf(string $supervisorId, string $userId): bool
    {
        return $this->exists(['user_id' => $userId, 'supervisor_id' => $supervisorId]);
    }
}
