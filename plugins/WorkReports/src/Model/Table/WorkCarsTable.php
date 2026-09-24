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
 * @property \WorkReports\Model\Table\WorkReportItemsTable&\Cake\ORM\Association\HasMany $DrivenAsPrivate
 * @property \WorkReports\Model\Table\WorkReportItemsTable&\Cake\ORM\Association\HasMany $DrivenAsCompany
 * @property \WorkReports\Model\Table\WorkReportWorkersTable&\Cake\ORM\Association\HasMany $UsualPrivateCarOf
 * @property \WorkReports\Model\Table\WorkReportWorkersTable&\Cake\ORM\Association\HasMany $UsualCompanyCarOf
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

        // the work this car was driven to, and the people who drive it unless they say otherwise
        $this->hasMany('DrivenAsPrivate', [
            'className' => 'WorkReports.WorkReportItems',
            'foreignKey' => 'private_car_id',
        ]);
        $this->hasMany('DrivenAsCompany', [
            'className' => 'WorkReports.WorkReportItems',
            'foreignKey' => 'company_car_id',
        ]);
        $this->hasMany('UsualPrivateCarOf', [
            'className' => 'WorkReports.WorkReportWorkers',
            'foreignKey' => 'default_private_car_id',
        ]);
        $this->hasMany('UsualCompanyCarOf', [
            'className' => 'WorkReports.WorkReportWorkers',
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

        // a car that has been driven stays on the work it was driven to, and one somebody drives by
        // default stays on their row - a car nobody drives any more is switched off
        $driven = [
            'errorField' => 'name',
            'message' => __d('work_reports', 'The car is on reported work. Switch it off instead.'),
        ];
        $usual = [
            'errorField' => 'name',
            'message' => __d('work_reports', 'The car is the one a worker drives by default.'),
        ];
        $rules->addDelete($rules->isNotLinkedTo('DrivenAsPrivate'), 'notDrivenAsPrivate', $driven);
        $rules->addDelete($rules->isNotLinkedTo('DrivenAsCompany'), 'notDrivenAsCompany', $driven);
        $rules->addDelete($rules->isNotLinkedTo('UsualPrivateCarOf'), 'notUsualPrivateCar', $usual);
        $rules->addDelete($rules->isNotLinkedTo('UsualCompanyCarOf'), 'notUsualCompanyCar', $usual);

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
