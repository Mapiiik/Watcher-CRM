<?php
declare(strict_types=1);

namespace Radius\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

/**
 * Radcheck Model
 *
 * @property \Radius\Model\Table\AccountsTable&\Cake\ORM\Association\BelongsTo $Accounts
 * @method \Radius\Model\Entity\Radcheck newEmptyEntity()
 * @method \Radius\Model\Entity\Radcheck newEntity(array $data, array $options = [])
 * @method \Radius\Model\Entity\Radcheck[] newEntities(array $data, array $options = [])
 * @method \Radius\Model\Entity\Radcheck get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \Radius\Model\Entity\Radcheck findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Radius\Model\Entity\Radcheck findOrNewEntity($search)
 * @method \Radius\Model\Entity\Radcheck patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Radius\Model\Entity\Radcheck[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Radius\Model\Entity\Radcheck|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Radius\Model\Entity\Radcheck saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<\Radius\Model\Entity\Radcheck>|false saveMany(iterable $entities, $options = [])
 * @method iterable<\Radius\Model\Entity\Radcheck> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<\Radius\Model\Entity\Radcheck>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<\Radius\Model\Entity\Radcheck> deleteManyOrFail(iterable $entities, $options = [])
 */
class RadcheckTable extends AppTable
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

        $this->setTable('radcheck');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');

        $this->belongsTo('Radius.Accounts', [
            'foreignKey' => 'username',
            'bindingKey' => 'username',
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
            ->scalar('username')
            ->notEmptyString('username');

        $validator
            ->scalar('attribute')
            ->notEmptyString('attribute');

        $validator
            ->scalar('op')
            ->maxLength('op', 2)
            ->notEmptyString('op');

        $validator
            ->scalar('value')
            ->notEmptyString('value');

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
        //$rules->add($rules->isUnique(['username', 'attribute']), ['errorField' => 'username']);

        return $rules;
    }

    /**
     * Returns the database connection name to use by default.
     *
     * @return string
     */
    public static function defaultConnectionName(): string
    {
        return 'radius';
    }
}
