<?php
declare(strict_types=1);

namespace App\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

/**
 * Phones Model
 *
 * @property \App\Model\Table\CustomersTable&\Cake\ORM\Association\BelongsTo $Customers
 * @method \App\Model\Entity\Phone newEmptyEntity()
 * @method \App\Model\Entity\Phone newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Phone[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Phone get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Phone findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Phone patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Phone[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Phone|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Phone saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<\App\Model\Entity\Phone>|false saveMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\Phone> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\Phone>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\Phone> deleteManyOrFail(iterable $entities, $options = [])
 */
class PhonesTable extends AppTable
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

        $this->setTable('phones');
        $this->setDisplayField('phone');
        $this->setPrimaryKey('id');

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
            ->uuid('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('phone')
            ->requirePresence('phone', 'create')
            ->notEmptyString('phone');

        $validator
            ->boolean('use_for_billing')
            ->notEmptyString('use_for_billing');

        $validator
            ->boolean('use_for_outages')
            ->notEmptyString('use_for_outages');

        $validator
            ->boolean('use_for_commercial')
            ->notEmptyString('use_for_commercial');

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
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['customer_id'], 'Customers'), ['errorField' => 'customer_id']);

        $rules->add(
            function ($entity, $options) {
                $phoneUtil = PhoneNumberUtil::getInstance();
                try {
                    $phoneNumber = $phoneUtil->parse($entity->phone, env('APP_DEFAULT_PHONE_REGION'));

                    return $phoneUtil->isValidNumber($phoneNumber);
                } catch (NumberParseException $e) {
                    return false;
                }
            },
            'isPhoneNumberValid',
            [
                'errorField' => 'phone',
                'message' => __('The phone number is not valid.'),
            ]
        );

        return $rules;
    }

    /**
     * Normalization of phone numbers
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Event
     * @param \ArrayObject<string, mixed> $data Data
     * @param \ArrayObject<string, mixed> $options Options
     * @return void
     */
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        if (isset($data['phone']) && is_string($data['phone']) && (strlen($data['phone']) > 0)) {
            $phoneUtil = PhoneNumberUtil::getInstance();
            try {
                $phoneNumber = $phoneUtil->parse($data['phone'], env('APP_DEFAULT_PHONE_REGION'));

                if ($phoneUtil->isValidNumber($phoneNumber)) {
                    // The phone number is fine, formatting...
                    $data['phone'] = $phoneUtil->format($phoneNumber, PhoneNumberFormat::INTERNATIONAL);
                }
            } catch (NumberParseException $e) {
                // The problem will be caught by the "isPhoneNumberValid" validator
            }
        }
    }
}
