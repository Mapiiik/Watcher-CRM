<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Rule\ExistingAccessPointRule;
use Cake\ORM\Association;
use Cake\ORM\RulesChecker;
use Override;
use Tasks\Model\Rule\RequiredLinkRule;
use Tasks\Model\Table\TasksTable as TasksTasksTable;

/**
 * Tasks Model
 *
 * On top of the shared task: what this application files a task under - a customer, a contract
 * and a place of the network - and what a task type may insist on before a task filed under it
 * can be saved.
 *
 * @property \App\Model\Table\TaskStatesTable&\Cake\ORM\Association\BelongsTo $TaskStates
 * @property \App\Model\Table\TaskTypesTable&\Cake\ORM\Association\BelongsTo $TaskTypes
 * @property \App\Model\Table\AppUsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\CustomersTable&\Cake\ORM\Association\BelongsTo $Customers
 * @property \App\Model\Table\ContractsTable&\Cake\ORM\Association\BelongsTo $Contracts
 * @method \App\Model\Entity\Task newEmptyEntity()
 * @method \App\Model\Entity\Task newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Task[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Task get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Task findOrCreate($search, callable|array|null $callback = null, $options = [])
 * @method \App\Model\Entity\Task patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Task[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Task|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Task saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<\App\Model\Entity\Task>|false saveMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\Task> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\Task>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\Task> deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class TasksTable extends TasksTasksTable
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

        $this->belongsTo('Customers', [
            'foreignKey' => 'customer_id',
        ]);
        $this->belongsTo('Contracts', [
            'foreignKey' => 'contract_id',
        ]);
    }

    /**
     * A task's summary line reads its customer and the address of its contract, so both have to
     * come with it.
     *
     * The addresses are read per customer while the rows are ordered by columns of the task, which
     * the `subquery` strategy turns into a `GROUP BY` over an order PostgreSQL will not accept -
     * the task listing spells this out for the same reason.
     *
     * @return array<mixed>
     */
    #[Override]
    public function summaryContain(): array
    {
        return [
            'TaskTypes',
            'Contracts' => ['InstallationAddresses'],
            'Customers' => [
                'Addresses' => ['strategy' => Association::STRATEGY_SELECT],
            ],
        ];
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
        $rules = parent::buildRules($rules);

        $rules->add($rules->existsIn(['customer_id'], 'Customers'), ['errorField' => 'customer_id']);
        $rules->add($rules->existsIn(['contract_id'], 'Contracts'), ['errorField' => 'contract_id']);

        // The places of the network are Watcher NMS's, so what stands for `existsIn` here asks it.
        $rules->add(
            new ExistingAccessPointRule(),
            'accessPointIsThere',
            [
                'errorField' => 'access_point_id',
                'message' => __('The specified access point is not one Watcher NMS keeps.'),
            ],
        );

        $rules->add(
            new RequiredLinkRule('customer_required', 'customer_id'),
            'isRequiredCustomerFilled',
            [
                'errorField' => 'customer_id',
                'message' => __('The specified task type requires the assignment of an customer.'),
            ],
        );

        $rules->add(
            new RequiredLinkRule('contract_required', 'contract_id'),
            'isRequiredContractFilled',
            [
                'errorField' => 'contract_id',
                'message' => __('The specified task type requires the assignment of an contract.'),
            ],
        );

        $rules->add(
            new RequiredLinkRule('access_point_required', 'access_point_id'),
            'isRequiredAccessPointFilled',
            [
                'errorField' => 'access_point_id',
                'message' => __('The specified task type requires the assignment of an access point.'),
            ],
        );

        return $rules;
    }
}
