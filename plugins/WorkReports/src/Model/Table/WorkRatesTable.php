<?php
declare(strict_types=1);

namespace WorkReports\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use Override;

/**
 * WorkRates Model
 *
 * @property \WorkReports\Model\Table\WorkReportItemsTable&\Cake\ORM\Association\HasMany $WorkReportItems
 * @method \WorkReports\Model\Entity\WorkRate newEmptyEntity()
 * @method \WorkReports\Model\Entity\WorkRate get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \WorkReports\Model\Entity\WorkRate patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \WorkReports\Model\Entity\WorkRate|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \WorkReports\Model\Entity\WorkRate saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 */
class WorkRatesTable extends AppTable
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

        $this->setTable('work_rates');
        $this->setDisplayField('name_for_lists');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');

        $this->hasMany('WorkReportItems', [
            'className' => 'WorkReports.WorkReportItems',
            'foreignKey' => 'work_rate_id',
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
            ->scalar('code')
            ->requirePresence('code', 'create')
            ->notEmptyString('code');

        $validator
            ->scalar('name')
            ->allowEmptyString('name');

        $validator
            ->decimal('price')
            ->greaterThanOrEqual('price', 0)
            ->allowEmptyString('price');

        $validator
            ->scalar('accounting_product_code')
            ->allowEmptyString('accounting_product_code');

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
        $rules->add($rules->isUnique(['code']), ['errorField' => 'code']);
        $rules->addDelete($rules->isNotLinkedTo('WorkReportItems'));

        return $rules;
    }
}
