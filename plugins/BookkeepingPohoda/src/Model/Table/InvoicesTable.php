<?php
declare(strict_types=1);

namespace BookkeepingPohoda\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Invoices Model
 *
 * @property \App\Model\Table\CustomersTable&\Cake\ORM\Association\BelongsTo $Customers
 * @property \CakeDC\Users\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Creators
 * @property \CakeDC\Users\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Modifiers
 * @method \BookkeepingPohoda\Model\Entity\Invoice newEmptyEntity()
 * @method \BookkeepingPohoda\Model\Entity\Invoice newEntity(array $data, array $options = [])
 * @method \BookkeepingPohoda\Model\Entity\Invoice[] newEntities(array $data, array $options = [])
 * @method \BookkeepingPohoda\Model\Entity\Invoice get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \BookkeepingPohoda\Model\Entity\Invoice findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \BookkeepingPohoda\Model\Entity\Invoice patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \BookkeepingPohoda\Model\Entity\Invoice[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \BookkeepingPohoda\Model\Entity\Invoice|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \BookkeepingPohoda\Model\Entity\Invoice saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<\BookkeepingPohoda\Model\Entity\Invoice>|false saveMany(iterable $entities, $options = [])
 * @method iterable<\BookkeepingPohoda\Model\Entity\Invoice> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<\BookkeepingPohoda\Model\Entity\Invoice>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<\BookkeepingPohoda\Model\Entity\Invoice> deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class InvoicesTable extends Table
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

        $this->setTable('invoices');
        $this->setDisplayField('number');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');

        $this->belongsTo('Customers', [
            'foreignKey' => 'customer_id',
            'className' => 'Customers',
        ]);
        $this->belongsTo('Creators', [
            'className' => 'AppUsers',
            'foreignKey' => 'created_by',
        ]);
        $this->belongsTo('Modifiers', [
            'className' => 'AppUsers',
            'foreignKey' => 'modified_by',
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
            ->allowEmptyString('id', null, 'create');

        $validator
            ->requirePresence('number', 'create')
            ->notEmptyString('number');

        $validator
            ->integer('variable_symbol')
            ->allowEmptyString('variable_symbol');

        $validator
            ->date('creation_date')
            ->allowEmptyDate('creation_date');

        $validator
            ->date('due_date')
            ->allowEmptyDate('due_date');

        $validator
            ->scalar('text')
            ->allowEmptyString('text');

        $validator
            ->decimal('total')
            ->allowEmptyString('total');

        $validator
            ->decimal('debt')
            ->allowEmptyString('debt');

        $validator
            ->date('payment_date')
            ->allowEmptyDate('payment_date');

        $validator
            ->boolean('send_by_email')
            ->notEmptyString('send_by_email');

        $validator
            ->dateTime('email_sent')
            ->allowEmptyDateTime('email_sent');

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

        return $rules;
    }
}
