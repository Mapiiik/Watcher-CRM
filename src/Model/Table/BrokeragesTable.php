<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Brokerages Model
 *
 * @property \App\Model\Table\BrokerageDealersTable&\Cake\ORM\Association\HasMany $BrokerageDealers
 * @property \App\Model\Table\ContractsTable&\Cake\ORM\Association\HasMany $Contracts
 * @property \App\Model\Table\IpsTable&\Cake\ORM\Association\HasMany $Ips
 * @property \App\Model\Table\RemovedIpsTable&\Cake\ORM\Association\HasMany $RemovedIps
 *
 * @method \App\Model\Entity\Brokerage newEmptyEntity()
 * @method \App\Model\Entity\Brokerage newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Brokerage[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Brokerage get($primaryKey, $options = [])
 * @method \App\Model\Entity\Brokerage findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Brokerage patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Brokerage[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Brokerage|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Brokerage saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Brokerage[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Brokerage[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Brokerage[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Brokerage[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class BrokeragesTable extends Table
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

        $this->setTable('brokerages');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->hasMany('BrokerageDealers', [
            'foreignKey' => 'brokerage_id',
        ]);
        $this->hasMany('Contracts', [
            'foreignKey' => 'brokerage_id',
        ]);
        $this->hasMany('Ips', [
            'foreignKey' => 'brokerage_id',
        ]);
        $this->hasMany('RemovedIps', [
            'foreignKey' => 'brokerage_id',
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
            ->allowEmptyString('name');

        return $validator;
    }
}
