<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Routers Model
 *
 * @property \App\Model\Table\RangesTable&\Cake\ORM\Association\HasMany $Ranges
 * @property \App\Model\Table\TasksTable&\Cake\ORM\Association\HasMany $Tasks
 *
 * @method \App\Model\Entity\Router newEmptyEntity()
 * @method \App\Model\Entity\Router newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Router[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Router get($primaryKey, $options = [])
 * @method \App\Model\Entity\Router findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Router patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Router[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Router|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Router saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Router[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Router[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Router[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Router[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class RoutersTable extends Table
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

        $this->setTable('routers');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');
        
        $this->hasMany('Ranges', [
            'foreignKey' => 'router_id',
        ]);
        $this->hasMany('Tasks', [
            'foreignKey' => 'router_id',
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
            ->scalar('name')
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('ip')
            ->maxLength('ip', 39)
            ->requirePresence('ip', 'create')
            ->notEmptyString('ip');

        $validator
            ->integer('port')
            ->requirePresence('port', 'create')
            ->notEmptyString('port');

        $validator
            ->scalar('caption')
            ->allowEmptyString('caption');

        $validator
            ->boolean('accounting')
            ->notEmptyString('accounting');

        $validator
            ->numeric('gpsx')
            ->allowEmptyString('gpsx');

        $validator
            ->numeric('gpsy')
            ->allowEmptyString('gpsy');

        $validator
            ->scalar('note')
            ->allowEmptyString('note');

        return $validator;
    }
}
