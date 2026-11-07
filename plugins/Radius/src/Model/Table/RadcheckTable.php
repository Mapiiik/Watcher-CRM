<?php
declare(strict_types=1);

namespace Radius\Model\Table;

use App\Model\Table\AppTable;
use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use Override;
use Radius\Model\Entity\Radcheck;

/**
 * Radcheck Model
 *
 * @property \Radius\Model\Table\AccountsTable&\Cake\ORM\Association\BelongsTo $Accounts
 * @method \Radius\Model\Entity\Radcheck newEmptyEntity()
 * @method \Radius\Model\Entity\Radcheck newEntity(array $data, array $options = [])
 * @method \Radius\Model\Entity\Radcheck[] newEntities(array $data, array $options = [])
 * @method \Radius\Model\Entity\Radcheck get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \Radius\Model\Entity\Radcheck findOrCreate($search, callable|array|null $callback = null, $options = [])
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
    #[Override]
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
     * Keeps a password out of the audit log.
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event The event.
     * @param \Radius\Model\Entity\Radcheck $check The check being saved.
     * @return void
     */
    public function beforeSave(EventInterface $event, Radcheck $check): void
    {
        $this->hideIfPassword($check);
    }

    /**
     * Keeps a password out of the audit log.
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event The event.
     * @param \Radius\Model\Entity\Radcheck $check The check being deleted.
     * @return void
     */
    public function beforeDelete(EventInterface $event, Radcheck $check): void
    {
        $this->hideIfPassword($check);
    }

    /**
     * Says what the audit log hides of this check. Only a password: Auth-Type and the rest say what
     * happened to the account. What does not say what it is counts as a password.
     *
     * @param \Radius\Model\Entity\Radcheck $check The check.
     * @return void
     */
    private function hideIfPassword(Radcheck $check): void
    {
        $readable = $check->has('attribute') && $check->attribute !== 'Cleartext-Password';

        $this->getBehavior('AuditLog')->setConfig('sensitive', $readable ? [] : ['value'], false);
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
    #[Override]
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
    #[Override]
    public static function defaultConnectionName(): string
    {
        return 'radius';
    }
}
