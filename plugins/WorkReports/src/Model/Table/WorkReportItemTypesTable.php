<?php
declare(strict_types=1);

namespace WorkReports\Model\Table;

use App\Model\Table\AppTable;
use Cake\Database\Type\EnumType;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use Override;
use WorkReports\Model\Enum\TimeMode;

/**
 * WorkReportItemTypes Model
 *
 * @property \WorkReports\Model\Table\WorkReportItemsTable&\Cake\ORM\Association\HasMany $WorkReportItems
 * @method \WorkReports\Model\Entity\WorkReportItemType newEmptyEntity()
 * @method \WorkReports\Model\Entity\WorkReportItemType get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \WorkReports\Model\Entity\WorkReportItemType patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \WorkReports\Model\Entity\WorkReportItemType|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \WorkReports\Model\Entity\WorkReportItemType saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 */
class WorkReportItemTypesTable extends AppTable
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

        $this->setTable('work_report_item_types');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');

        $this->getSchema()->setColumnType('time_mode', EnumType::from(TimeMode::class));

        $this->hasMany('WorkReportItems', [
            'className' => 'WorkReports.WorkReportItems',
            'foreignKey' => 'work_report_item_type_id',
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
            ->scalar('name')
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->boolean('has_text')
            ->notEmptyString('has_text');

        $validator
            ->enum('time_mode', TimeMode::class)
            ->notEmptyString('time_mode');

        $validator
            ->boolean('counts_as_worked')
            ->notEmptyString('counts_as_worked');

        $validator
            ->boolean('reduces_fund')
            ->notEmptyString('reduces_fund');

        $validator
            ->boolean('active')
            ->notEmptyString('active');

        $validator
            ->integer('sort')
            ->notEmptyString('sort');

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
        $rules->addDelete($rules->isNotLinkedTo('WorkReportItems'));

        return $rules;
    }
}
