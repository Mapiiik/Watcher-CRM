<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Enum\CustomerMessageBodyFormat;
use App\Model\Enum\CustomerMessageDeliveryStatus;
use App\Model\Enum\CustomerMessageDirection;
use App\Model\Enum\CustomerMessageType;
use Cake\Database\Type\EnumType;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

/**
 * CustomerMessages Model
 *
 * @property \App\Model\Table\CustomersTable&\Cake\ORM\Association\BelongsTo $Customers
 * @method \App\Model\Entity\CustomerMessage newEmptyEntity()
 * @method \App\Model\Entity\CustomerMessage newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\CustomerMessage> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\CustomerMessage get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\CustomerMessage findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\CustomerMessage patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\CustomerMessage> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\CustomerMessage|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\CustomerMessage saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\CustomerMessage>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\CustomerMessage> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\CustomerMessage>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\CustomerMessage> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 * @mixin \App\Model\Behavior\FootprintBehavior
 * @mixin \App\Model\Behavior\StringModificationsBehavior
 */
class CustomerMessagesTable extends AppTable
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

        $this->setTable('customer_messages');
        $this->setDisplayField('subject');
        $this->setPrimaryKey('id');

        $this->getSchema()->setColumnType(
            'type',
            EnumType::from(CustomerMessageType::class)
        );
        $this->getSchema()->setColumnType(
            'direction',
            EnumType::from(CustomerMessageDirection::class)
        );
        $this->getSchema()->setColumnType(
            'body_format',
            EnumType::from(CustomerMessageBodyFormat::class)
        );
        $this->getSchema()->setColumnType(
            'delivery_status',
            EnumType::from(CustomerMessageDeliveryStatus::class)
        );

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');

        $this->belongsTo('Customers', [
            'foreignKey' => 'customer_id',
            'joinType' => 'INNER',
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
            ->requirePresence('type', 'create')
            ->notEmptyString('type');

        $validator
            ->requirePresence('direction', 'create')
            ->notEmptyString('direction');

        $validator
            ->requirePresence('recipients', 'create')
            ->notEmptyString('recipients');

        $validator
            ->scalar('subject')
            ->maxLength('subject', 255)
            ->requirePresence('subject', 'create')
            ->notEmptyString('subject');

        $validator
            ->scalar('body')
            ->requirePresence('body', 'create')
            ->notEmptyString('body');

        $validator
            ->requirePresence('body_format', 'create')
            ->notEmptyString('body_format');

        $validator
            ->requirePresence('delivery_status', 'create')
            ->notEmptyString('delivery_status');

        $validator
            ->dateTime('processed')
            ->allowEmptyDateTime('processed');

        $validator
            ->scalar('identifier')
            ->maxLength('identifier', 255)
            ->allowEmptyString('identifier');

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
        $rules->add($rules->existsIn(['customer_id'], 'Customers'), ['errorField' => 'customer_id']);

        return $rules;
    }
}
