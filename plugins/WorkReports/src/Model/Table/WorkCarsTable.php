<?php
declare(strict_types=1);

namespace WorkReports\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use Override;

/**
 * WorkCars Model
 *
 * A car without an owner belongs to the company, one with an owner is that person's own.
 *
 * @property \App\Model\Table\AppUsersTable&\Cake\ORM\Association\BelongsTo $Owners
 * @method \WorkReports\Model\Entity\WorkCar newEmptyEntity()
 * @method \WorkReports\Model\Entity\WorkCar get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \WorkReports\Model\Entity\WorkCar patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \WorkReports\Model\Entity\WorkCar|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \WorkReports\Model\Entity\WorkCar saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 */
class WorkCarsTable extends AppTable
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

        $this->setTable('work_cars');
        $this->setDisplayField('name_for_lists');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');

        $this->belongsTo('Owners', [
            'className' => 'AppUsers',
            'foreignKey' => 'owner_id',
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
            ->scalar('license_plate')
            ->allowEmptyString('license_plate');

        $validator
            ->uuid('owner_id')
            ->allowEmptyString('owner_id');

        $validator
            ->boolean('active')
            ->notEmptyString('active');

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
        $rules->add($rules->existsIn(['owner_id'], 'Owners'), ['errorField' => 'owner_id']);

        return $rules;
    }

    /**
     * The cars of the company.
     *
     * @param \Cake\ORM\Query\SelectQuery<\WorkReports\Model\Entity\WorkCar> $query Query
     * @return \Cake\ORM\Query\SelectQuery<\WorkReports\Model\Entity\WorkCar>
     */
    public function findCompany(SelectQuery $query): SelectQuery
    {
        return $query->where([$this->aliasField('owner_id') . ' IS' => null]);
    }

    /**
     * The cars a person drives as their own.
     *
     * @param \Cake\ORM\Query\SelectQuery<\WorkReports\Model\Entity\WorkCar> $query Query
     * @param string $ownerId User the cars belong to.
     * @return \Cake\ORM\Query\SelectQuery<\WorkReports\Model\Entity\WorkCar>
     */
    public function findPrivate(SelectQuery $query, string $ownerId): SelectQuery
    {
        return $query->where([$this->aliasField('owner_id') => $ownerId]);
    }
}
