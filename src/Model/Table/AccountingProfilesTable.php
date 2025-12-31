<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use Override;

/**
 * AccountingProfiles Model
 *
 * @property \App\Model\Table\CustomersTable&\Cake\ORM\Association\HasMany $Customers
 * @method \App\Model\Entity\AccountingProfile newEmptyEntity()
 * @method \App\Model\Entity\AccountingProfile newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\AccountingProfile[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\AccountingProfile get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\AccountingProfile findOrCreate($search, callable|array|null $callback = null, $options = [])
 * @method \App\Model\Entity\AccountingProfile patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\AccountingProfile[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\AccountingProfile|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\AccountingProfile saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<\App\Model\Entity\AccountingProfile>|false saveMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\AccountingProfile> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\AccountingProfile>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\AccountingProfile> deleteManyOrFail(iterable $entities, $options = [])
 */
class AccountingProfilesTable extends AppTable
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

        $this->setTable('accounting_profiles');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');

        $this->hasMany('Customers', [
            'foreignKey' => 'accounting_profile_id',
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
            ->maxLength('name', 32)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->numeric('vat_rate')
            ->notEmptyString('vat_rate');

        $validator
            ->boolean('reverse_charge')
            ->notEmptyString('reverse_charge');

        $validator
            ->scalar('accounting_assignment_code')
            ->maxLength('accounting_assignment_code', 255)
            ->allowEmptyString('accounting_assignment_code');

        $validator
            ->scalar('bank_account_code')
            ->maxLength('bank_account_code', 255)
            ->allowEmptyString('bank_account_code');

        $validator
            ->scalar('activity_code')
            ->maxLength('activity_code', 255)
            ->allowEmptyString('activity_code');

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
        $rules->addDelete($rules->isNotLinkedTo('Customers'));

        return $rules;
    }
}
