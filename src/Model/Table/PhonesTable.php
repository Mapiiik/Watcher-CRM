<?php
declare(strict_types=1);

namespace App\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Phones Model
 *
 * @property \App\Model\Table\CustomersTable&\Cake\ORM\Association\BelongsTo $Customers
 * @method \App\Model\Entity\Phone newEmptyEntity()
 * @method \App\Model\Entity\Phone newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Phone[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Phone get($primaryKey, $options = [])
 * @method \App\Model\Entity\Phone findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Phone patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Phone[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Phone|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Phone saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Phone[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Phone[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Phone[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Phone[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class PhonesTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
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
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('phone')
            ->requirePresence('phone', 'create')
            ->notEmptyString('phone');

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

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        if (isset($data['phone']) && is_string($data['phone']) && (strlen($data['phone']) > 0)) {
            $phone = $data['phone'];
            $old = ['+','-',' '];
            $new = ['','',''];

            $phone = trim(str_replace(['+', '-', ' '], ['', '', ''], $phone));

            switch (strlen($phone)) {
                case 9:
                        $phone = '420' . $phone;
                    break;
                case 12:
                        $phone = $phone;
                    break;
                case 11: //some other countries, Netherlands etc.
                        $phone = $phone;
                    break;
                default:
                        $data['phone'] = null;

                    return;
            }

            $phone = substr($phone, 0, 3) . ' ' . substr($phone, 3);
            $data['phone'] = '+' . $phone;
        }
    }
}
