<?php
declare(strict_types=1);

namespace Radius\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

/**
 * Radusergroup Model
 *
 * @property \Radius\Model\Table\AccountsTable&\Cake\ORM\Association\BelongsTo $Accounts
 * @property \Radius\Model\Table\RadgroupcheckTable&\Cake\ORM\Association\HasMany $Radgroupcheck
 * @property \Radius\Model\Table\RadgroupreplyTable&\Cake\ORM\Association\HasMany $Radgroupreply
 * @method \Radius\Model\Entity\Radusergroup newEmptyEntity()
 * @method \Radius\Model\Entity\Radusergroup newEntity(array $data, array $options = [])
 * @method \Radius\Model\Entity\Radusergroup[] newEntities(array $data, array $options = [])
 * @method \Radius\Model\Entity\Radusergroup get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \Radius\Model\Entity\Radusergroup findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Radius\Model\Entity\Radusergroup findOrNewEntity($search)
 * @method \Radius\Model\Entity\Radusergroup patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Radius\Model\Entity\Radusergroup[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Radius\Model\Entity\Radusergroup|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Radius\Model\Entity\Radusergroup saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<\Radius\Model\Entity\Radusergroup>|false saveMany(iterable $entities, $options = [])
 * @method iterable<\Radius\Model\Entity\Radusergroup> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<\Radius\Model\Entity\Radusergroup>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<\Radius\Model\Entity\Radusergroup> deleteManyOrFail(iterable $entities, $options = [])
 */
class RadusergroupTable extends AppTable
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

        $this->setTable('radusergroup');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');

        $this->belongsTo('Radius.Accounts', [
            'foreignKey' => 'username',
            'bindingKey' => 'username',
        ]);
        $this->hasMany('Radius.Radgroupcheck', [
            'foreignKey' => 'groupname',
            'bindingKey' => 'groupname',
        ]);
        $this->hasMany('Radius.Radgroupreply', [
            'foreignKey' => 'groupname',
            'bindingKey' => 'groupname',
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
            ->scalar('groupname')
            ->notEmptyString('groupname');

        $validator
            ->integer('priority')
            ->notEmptyString('priority');

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
        //$rules->add($rules->isUnique(['username']), ['errorField' => 'username']);

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
