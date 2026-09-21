<?php
declare(strict_types=1);

namespace WorkReports\Model\Table;

use App\Model\Table\AppTable;
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\I18n\DateTime;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use Override;
use WorkReports\Model\Entity\WorkReportItem;
use WorkReports\Model\Entity\WorkReportItemType;
use WorkReports\Model\Enum\TimeMode;

/**
 * WorkReportItems Model
 *
 * @property \WorkReports\Model\Table\WorkReportsTable&\Cake\ORM\Association\BelongsTo $WorkReports
 * @property \WorkReports\Model\Table\WorkReportItemTypesTable&\Cake\ORM\Association\BelongsTo $WorkReportItemTypes
 * @property \App\Model\Table\CustomersTable&\Cake\ORM\Association\BelongsTo $Customers
 * @property \App\Model\Table\ContractsTable&\Cake\ORM\Association\BelongsTo $Contracts
 * @property \App\Model\Table\TasksTable&\Cake\ORM\Association\BelongsTo $Tasks
 * @property \WorkReports\Model\Table\WorkCarsTable&\Cake\ORM\Association\BelongsTo $PrivateCars
 * @property \WorkReports\Model\Table\WorkCarsTable&\Cake\ORM\Association\BelongsTo $CompanyCars
 * @property \WorkReports\Model\Table\WorkRatesTable&\Cake\ORM\Association\BelongsTo $WorkRates
 * @property \WorkReports\Model\Table\WorkReportItemLabelsTable&\Cake\ORM\Association\HasMany $WorkReportItemLabels
 * @property \WorkReports\Model\Table\WorkLabelsTable&\Cake\ORM\Association\BelongsToMany $WorkLabels
 * @property \WorkReports\Model\Table\WorkReportItemCollaboratorsTable&\Cake\ORM\Association\HasMany $WorkReportItemCollaborators
 * @property \App\Model\Table\AppUsersTable&\Cake\ORM\Association\BelongsToMany $Collaborators
 * @method \WorkReports\Model\Entity\WorkReportItem newEmptyEntity()
 * @method \WorkReports\Model\Entity\WorkReportItem newEntity(array $data, array $options = [])
 * @method \WorkReports\Model\Entity\WorkReportItem get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \WorkReports\Model\Entity\WorkReportItem patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \WorkReports\Model\Entity\WorkReportItem|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \WorkReports\Model\Entity\WorkReportItem saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 */
class WorkReportItemsTable extends AppTable
{
    /**
     * What may still change on an item of a submitted report: whether it was charged, and the
     * footprint of whoever marked it so.
     *
     * @var list<string>
     */
    public const CHANGEABLE_WHEN_LOCKED = ['charged', 'modified', 'modified_by'];

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

        $this->setTable('work_report_items');
        $this->setDisplayField('description');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');

        $this->belongsTo('WorkReports', [
            'className' => 'WorkReports.WorkReports',
            'foreignKey' => 'work_report_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('WorkReportItemTypes', [
            'className' => 'WorkReports.WorkReportItemTypes',
            'foreignKey' => 'work_report_item_type_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Customers', [
            'foreignKey' => 'customer_id',
        ]);
        $this->belongsTo('Contracts', [
            'foreignKey' => 'contract_id',
        ]);
        $this->belongsTo('Tasks', [
            'foreignKey' => 'task_id',
        ]);
        $this->belongsTo('PrivateCars', [
            'className' => 'WorkReports.WorkCars',
            'foreignKey' => 'private_car_id',
        ]);
        $this->belongsTo('CompanyCars', [
            'className' => 'WorkReports.WorkCars',
            'foreignKey' => 'company_car_id',
        ]);
        $this->belongsTo('WorkRates', [
            'className' => 'WorkReports.WorkRates',
            'foreignKey' => 'work_rate_id',
        ]);
        $this->hasMany('WorkReportItemLabels', [
            'className' => 'WorkReports.WorkReportItemLabels',
            'foreignKey' => 'work_report_item_id',
            'dependent' => true,
        ]);
        $this->belongsToMany('WorkLabels', [
            'className' => 'WorkReports.WorkLabels',
            'through' => 'WorkReports.WorkReportItemLabels',
            'foreignKey' => 'work_report_item_id',
            'targetForeignKey' => 'work_label_id',
            'sort' => ['WorkLabels.name'],
        ]);
        $this->hasMany('WorkReportItemCollaborators', [
            'className' => 'WorkReports.WorkReportItemCollaborators',
            'foreignKey' => 'work_report_item_id',
            'dependent' => true,
        ]);
        $this->belongsToMany('Collaborators', [
            'className' => 'AppUsers',
            'through' => 'WorkReports.WorkReportItemCollaborators',
            'foreignKey' => 'work_report_item_id',
            'targetForeignKey' => 'user_id',
            'sort' => ['Collaborators.last_name', 'Collaborators.first_name'],
        ]);
    }

    /**
     * The form states the day once and the times as a clock shows them, so the moments are put
     * together here. An until earlier than the from is past midnight.
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Event
     * @param \ArrayObject<string, mixed> $data Data being marshalled
     * @param \ArrayObject<string, mixed> $options Options
     * @psalm-suppress PossiblyUnusedParam
     * @return void
     */
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        if (!array_key_exists('time_from', (array)$data) && !array_key_exists('time_until', (array)$data)) {
            return;
        }

        $date = $data['date'] ?? null;
        $from = $data['time_from'] ?? null;
        $until = $data['time_until'] ?? null;
        unset($data['time_from'], $data['time_until']);

        if (!empty($data['whole_day']) || !is_string($date) || $date === '') {
            $data['work_from'] = null;
            $data['work_until'] = null;

            return;
        }

        $data['work_from'] = is_string($from) && $from !== '' ? DateTime::parse($date . ' ' . $from) : null;
        $data['work_until'] = is_string($until) && $until !== '' ? DateTime::parse($date . ' ' . $until) : null;

        if (
            $data['work_from'] instanceof DateTime
            && $data['work_until'] instanceof DateTime
            && $data['work_until'] <= $data['work_from']
        ) {
            $data['work_until'] = $data['work_until']->addDays(1);
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
            ->uuid('work_report_id')
            ->notEmptyString('work_report_id');

        $validator
            ->uuid('work_report_item_type_id')
            ->requirePresence('work_report_item_type_id', 'create')
            ->notEmptyString('work_report_item_type_id');

        $validator
            ->date('date')
            ->requirePresence('date', 'create')
            ->notEmptyDate('date');

        $validator
            ->boolean('whole_day')
            ->notEmptyString('whole_day');

        $validator
            ->dateTime('work_from')
            ->allowEmptyDateTime('work_from');

        $validator
            ->dateTime('work_until')
            ->allowEmptyDateTime('work_until');

        $validator
            ->scalar('description')
            ->allowEmptyString('description');

        $validator
            ->uuid('customer_id')
            ->allowEmptyString('customer_id');

        $validator
            ->uuid('contract_id')
            ->allowEmptyString('contract_id');

        $validator
            ->uuid('task_id')
            ->allowEmptyString('task_id');

        $validator
            ->uuid('access_point_id')
            ->allowEmptyString('access_point_id');

        foreach (['private_car', 'company_car'] as $car) {
            $validator
                ->uuid($car . '_id')
                ->allowEmptyString($car . '_id');

            $validator
                ->nonNegativeInteger($car . '_distance')
                ->allowEmptyString($car . '_distance');
        }

        $validator
            ->decimal('cash_collected')
            ->allowEmptyString('cash_collected');

        $validator
            ->boolean('billable')
            ->notEmptyString('billable');

        $validator
            ->decimal('billed_hours')
            ->greaterThan('billed_hours', 0)
            ->allowEmptyString('billed_hours');

        $validator
            ->uuid('work_rate_id')
            ->allowEmptyString('work_rate_id');

        $validator
            ->decimal('rate_multiplier')
            ->greaterThan('rate_multiplier', 0)
            ->notEmptyString('rate_multiplier');

        $validator
            ->scalar('billing_text')
            ->allowEmptyString('billing_text');

        $validator
            ->boolean('charged')
            ->notEmptyString('charged');

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
        $rules->add($rules->existsIn(['work_report_id'], 'WorkReports'), ['errorField' => 'work_report_id']);
        $rules->add(
            $rules->existsIn(['work_report_item_type_id'], 'WorkReportItemTypes'),
            ['errorField' => 'work_report_item_type_id'],
        );
        $rules->add($rules->existsIn(['customer_id'], 'Customers'), ['errorField' => 'customer_id']);
        $rules->add($rules->existsIn(['contract_id'], 'Contracts'), ['errorField' => 'contract_id']);
        $rules->add($rules->existsIn(['task_id'], 'Tasks'), ['errorField' => 'task_id']);
        $rules->add($rules->existsIn(['private_car_id'], 'PrivateCars'), ['errorField' => 'private_car_id']);
        $rules->add($rules->existsIn(['company_car_id'], 'CompanyCars'), ['errorField' => 'company_car_id']);
        $rules->add($rules->existsIn(['work_rate_id'], 'WorkRates'), ['errorField' => 'work_rate_id']);

        $rules->add(
            fn(WorkReportItem $item): bool => !$this->isLocked($item)
                || array_diff($item->getDirty(), self::CHANGEABLE_WHEN_LOCKED) === [],
            'unlocked',
            [
                'errorField' => 'work_report_id',
                'message' => __d('work_reports', 'The report has been submitted, its items can no longer be changed.'),
            ],
        );
        $rules->addDelete(
            fn(WorkReportItem $item): bool => !$this->isLocked($item),
            'unlocked',
            [
                'errorField' => 'work_report_id',
                'message' => __d('work_reports', 'The report has been submitted, its items can no longer be changed.'),
            ],
        );

        $rules->add(
            function (WorkReportItem $item): bool {
                $report = $this->WorkReports->find()->where(['id' => $item->work_report_id])->first();

                return $report === null
                    || $item->date->format('Y-m') === $report->month->format('Y-m');
            },
            'dateInMonth',
            [
                'errorField' => 'date',
                'message' => __d('work_reports', 'The day is not in the month of the report.'),
            ],
        );

        $rules->add(
            [$this, 'checkTime'],
            'timeByType',
            ['errorField' => 'work_from'],
        );

        $rules->add(
            function (WorkReportItem $item): bool {
                $type = $this->typeOf($item);

                return $type === null || !$type->has_text || trim((string)$item->description) !== '';
            },
            'descriptionByType',
            [
                'errorField' => 'description',
                'message' => __d('work_reports', 'Items of this type need a description.'),
            ],
        );

        $rules->add(
            function (WorkReportItem $item): bool {
                if ($item->contract_id === null) {
                    return true;
                }

                return $item->customer_id !== null && $this->Contracts->exists([
                    'id' => $item->contract_id,
                    'customer_id' => $item->customer_id,
                ]);
            },
            'contractOfCustomer',
            [
                'errorField' => 'contract_id',
                'message' => __d('work_reports', 'The contract is not one of the customer\'s.'),
            ],
        );

        foreach (['private_car', 'company_car'] as $car) {
            $rules->add(
                fn(WorkReportItem $item): bool => empty($item->get($car . '_distance'))
                    || $item->get($car . '_id') !== null,
                $car . 'WithDistance',
                [
                    'errorField' => $car . '_id',
                    'message' => __d('work_reports', 'Say which car the distance was driven in.'),
                ],
            );
        }

        $rules->add(
            fn(WorkReportItem $item): bool => !$item->billable
                || ($item->work_rate_id !== null && $item->billed_hours !== null),
            'billing',
            [
                'errorField' => 'billed_hours',
                'message' => __d('work_reports', 'Work to be billed needs the hours and the rate.'),
            ],
        );

        return $rules;
    }

    /**
     * Whether the item states its time the way its type asks for.
     *
     * @param \WorkReports\Model\Entity\WorkReportItem $item Item to check.
     * @param array<string, mixed> $options Options passed to the rule.
     * @return string|bool
     */
    public function checkTime(WorkReportItem $item, array $options = []): string|bool
    {
        $type = $this->typeOf($item);
        if ($type === null) {
            return true;
        }

        $hasTimes = $item->work_from !== null && $item->work_until !== null;

        if ($item->whole_day) {
            if ($type->time_mode === TimeMode::Range) {
                return __d('work_reports', 'Items of this type are stated from and until, not as a whole day.');
            }

            return $item->work_from === null && $item->work_until === null
                ? true
                : __d('work_reports', 'A whole day has no from and until.');
        }

        if ($type->time_mode === TimeMode::WholeDay) {
            return __d('work_reports', 'Items of this type take the whole day.');
        }

        if (!$hasTimes) {
            return __d('work_reports', 'State from and until.');
        }

        return $item->work_until > $item->work_from
            ? true
            : __d('work_reports', 'The until has to come after the from.');
    }

    /**
     * Whether the report the item belongs to has been submitted.
     *
     * @param \WorkReports\Model\Entity\WorkReportItem $item Item to check.
     * @return bool
     */
    protected function isLocked(WorkReportItem $item): bool
    {
        $report = $this->WorkReports->find()->where(['id' => $item->work_report_id])->first();

        return $report !== null && $report->isLocked();
    }

    /**
     * The type of the item, as it is stored.
     *
     * @param \WorkReports\Model\Entity\WorkReportItem $item Item to look up.
     * @return \WorkReports\Model\Entity\WorkReportItemType|null
     */
    protected function typeOf(WorkReportItem $item): ?WorkReportItemType
    {
        /** @var \WorkReports\Model\Entity\WorkReportItemType|null */
        return $this->WorkReportItemTypes->find()->where(['id' => $item->work_report_item_type_id])->first();
    }
}
