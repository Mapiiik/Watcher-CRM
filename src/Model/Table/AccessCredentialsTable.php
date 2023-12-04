<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

/**
 * AccessCredentials Model
 *
 * @property \App\Model\Table\CustomersTable&\Cake\ORM\Association\BelongsTo $Customers
 * @property \App\Model\Table\ContractsTable&\Cake\ORM\Association\BelongsTo $Contracts
 * @method \App\Model\Entity\AccessCredential newEmptyEntity()
 * @method \App\Model\Entity\AccessCredential newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\AccessCredential> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\AccessCredential get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\AccessCredential findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\AccessCredential patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\AccessCredential> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\AccessCredential|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\AccessCredential saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\AccessCredential>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\AccessCredential> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\AccessCredential>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\AccessCredential> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class AccessCredentialsTable extends AppTable
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('access_credentials');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');

        $this->belongsTo('Customers', [
            'foreignKey' => 'customer_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Contracts', [
            'foreignKey' => 'contract_id',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->uuid('customer_id')
            ->notEmptyString('customer_id');

        $validator
            ->uuid('contract_id')
            ->allowEmptyString('contract_id');

        $validator
            ->scalar('name')
            ->allowEmptyString('name');

        $validator
            ->scalar('username')
            ->allowEmptyString('username');

        $validator
            ->scalar('password')
            ->allowEmptyString('password');

        $validator
            ->scalar('ip')
            ->maxLength('ip', 39)
            ->allowEmptyString('ip');

        $validator
            ->integer('port')
            ->allowEmptyString('port');

        $validator
            ->scalar('note')
            ->allowEmptyString('note');

        $validator
            ->uuid('created_by')
            ->allowEmptyString('created_by');

        $validator
            ->uuid('modified_by')
            ->allowEmptyString('modified_by');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn('customer_id', 'Customers'), ['errorField' => 'customer_id']);
        $rules->add($rules->existsIn('contract_id', 'Contracts'), ['errorField' => 'contract_id']);

        return $rules;
    }
}
