<?php
declare(strict_types=1);

namespace WorkReports\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use Override;

/**
 * WorkReportItemLabels Model
 *
 * @property \WorkReports\Model\Table\WorkReportItemsTable&\Cake\ORM\Association\BelongsTo $WorkReportItems
 * @property \WorkReports\Model\Table\WorkLabelsTable&\Cake\ORM\Association\BelongsTo $WorkLabels
 * @method \WorkReports\Model\Entity\WorkReportItemLabel newEmptyEntity()
 * @method \WorkReports\Model\Entity\WorkReportItemLabel get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \WorkReports\Model\Entity\WorkReportItemLabel patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \WorkReports\Model\Entity\WorkReportItemLabel|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \WorkReports\Model\Entity\WorkReportItemLabel saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 */
class WorkReportItemLabelsTable extends AppTable
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

        $this->setTable('work_report_item_labels');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');

        $this->belongsTo('WorkReportItems', [
            'className' => 'WorkReports.WorkReportItems',
            'foreignKey' => 'work_report_item_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('WorkLabels', [
            'className' => 'WorkReports.WorkLabels',
            'foreignKey' => 'work_label_id',
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
            ->uuid('work_report_item_id')
            ->notEmptyString('work_report_item_id');

        $validator
            ->uuid('work_label_id')
            ->notEmptyString('work_label_id');

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
            $rules->isUnique(['work_report_item_id', 'work_label_id']),
            ['errorField' => 'work_label_id'],
        );
        $rules->add(
            $rules->existsIn(['work_report_item_id'], 'WorkReportItems'),
            ['errorField' => 'work_report_item_id'],
        );
        $rules->add($rules->existsIn(['work_label_id'], 'WorkLabels'), ['errorField' => 'work_label_id']);

        return $rules;
    }
}
