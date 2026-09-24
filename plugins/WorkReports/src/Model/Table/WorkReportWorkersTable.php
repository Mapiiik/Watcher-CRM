<?php
declare(strict_types=1);

namespace WorkReports\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use Override;

/**
 * WorkReportWorkers Model
 *
 * @property \App\Model\Table\AppUsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \WorkReports\Model\Table\WorkReportWorkerRecipientsTable&\Cake\ORM\Association\HasMany $WorkReportWorkerRecipients
 * @property \WorkReports\Model\Table\WorkReportsTable&\Cake\ORM\Association\HasMany $WorkReports
 * @property \App\Model\Table\AppUsersTable&\Cake\ORM\Association\BelongsToMany $Recipients
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
        $this->belongsTo('DefaultPrivateCars', [
            'className' => 'WorkReports.WorkCars',
            'foreignKey' => 'default_private_car_id',
        ]);
        $this->belongsTo('DefaultCompanyCars', [
            'className' => 'WorkReports.WorkCars',
            'foreignKey' => 'default_company_car_id',
        ]);
        // the months are the user's rather than this row's, so they are found by the user
        $this->hasMany('WorkReports', [
            'className' => 'WorkReports.WorkReports',
            'foreignKey' => 'user_id',
            'bindingKey' => 'user_id',
        ]);
        $this->hasMany('WorkReportWorkerRecipients', [
            'className' => 'WorkReports.WorkReportWorkerRecipients',
            'foreignKey' => 'work_report_worker_id',
            'dependent' => true,
        ]);
        $this->belongsToMany('Recipients', [
            'className' => 'AppUsers',
            'through' => 'WorkReports.WorkReportWorkerRecipients',
            'foreignKey' => 'work_report_worker_id',
            'targetForeignKey' => 'user_id',
            'sort' => ['Recipients.last_name', 'Recipients.first_name'],
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

        // taking the row away would leave the months behind with nobody to read them, so a worker
        // who has stopped reporting is switched off rather than deleted
        $rules->addDelete(
            $rules->isNotLinkedTo('WorkReports'),
            'noReports',
            [
                'errorField' => 'user_id',
                'message' => __d('work_reports', 'The worker has reports. Switch the worker off instead.'),
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
     * Whether one user gets the reports of the other.
     *
     * @param string $recipientId The recipient asked about.
     * @param string $userId The worker.
     * @return bool
     */
    public function isRecipientOf(string $recipientId, string $userId): bool
    {
        return $this->find()
            ->innerJoinWith('Recipients', fn($query) => $query->where(['Recipients.id' => $recipientId]))
            ->where([$this->aliasField('user_id') => $userId])
            ->count() > 0;
    }

    /**
     * Whether one user may change the reports of the other and return them.
     *
     * @param string $recipientId The recipient asked about.
     * @param string $userId The worker.
     * @return bool
     */
    public function mayEdit(string $recipientId, string $userId): bool
    {
        return $this->find()
            ->innerJoinWith('WorkReportWorkerRecipients', fn($query) => $query->where([
                'WorkReportWorkerRecipients.user_id' => $recipientId,
                'WorkReportWorkerRecipients.may_edit' => true,
            ]))
            ->where([$this->aliasField('user_id') => $userId])
            ->count() > 0;
    }

    /**
     * Whether the user is on the list of people who report work, whatever state their row is in.
     * Somebody who has stopped reporting still has the months they wrote.
     *
     * @param string $userId User to look up.
     * @return bool
     */
    public function isWorker(string $userId): bool
    {
        return $this->exists(['user_id' => $userId]);
    }

    /**
     * Whether the user reports work at the moment.
     *
     * @param string $userId User to look up.
     * @return bool
     */
    public function isActiveWorker(string $userId): bool
    {
        return $this->exists(['user_id' => $userId, 'active' => true]);
    }

    /**
     * The users who report their work, as a subquery of their user ids.
     *
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    public function activeIds(): SelectQuery
    {
        return $this->find()
            ->select([$this->aliasField('user_id')])
            ->where([$this->aliasField('active') => true]);
    }

    /**
     * The workers whose reports the user gets, as a subquery of their user ids.
     *
     * @param string $recipientId The recipient.
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    public function workersOf(string $recipientId): SelectQuery
    {
        return $this->activeIds()
            ->innerJoinWith('Recipients', fn($query) => $query->where(['Recipients.id' => $recipientId]));
    }

    /**
     * Who gets the reports of the worker.
     *
     * @param string $userId The worker.
     * @return list<\App\Model\Entity\AppUser>
     */
    public function recipientsOf(string $userId): array
    {
        /** @var \WorkReports\Model\Entity\WorkReportWorker|null $worker */
        $worker = $this->find()->contain(['Recipients'])->where(['user_id' => $userId])->first();

        return $worker === null ? [] : array_values($worker->recipients);
    }
}
