<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Customer;
use App\Model\Enum\LoginRights;
use App\Utility\Strings;
use Cake\Database\Type\EnumType;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use Override;

/**
 * Logins Model
 *
 * @property \App\Model\Table\CustomersTable&\Cake\ORM\Association\BelongsTo $Customers
 * @method \App\Model\Entity\Login newEmptyEntity()
 * @method \App\Model\Entity\Login newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Login[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Login get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Login findOrCreate($search, callable|array|null $callback = null, $options = [])
 * @method \App\Model\Entity\Login patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Login[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Login|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Login saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<\App\Model\Entity\Login>|false saveMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\Login> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\Login>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\Login> deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class LoginsTable extends AppTable
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

        $this->setTable('logins');
        $this->setDisplayField('login');
        $this->setPrimaryKey('id');

        $this->getSchema()->setColumnType(
            'rights',
            EnumType::from(LoginRights::class),
        );

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');

        // the audit log says that a password changed, never what it is now
        $this->getBehavior('AuditLog')->setConfig('sensitive', ['password']);

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
    #[Override]
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->uuid('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('login')
            ->requirePresence('login', 'create')
            ->notEmptyString('login')
            ->add('login', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('password')
            ->requirePresence('password', 'create')
            ->notEmptyString('password');

        $validator
            ->notEmptyString('rights');

        $validator
            ->notEmptyString('locked');

        $validator
            ->dateTime('last_granted')
            ->allowEmptyDateTime('last_granted');

        $validator
            ->scalar('last_granted_ip')
            ->maxLength('last_granted_ip', 39)
            ->allowEmptyString('last_granted_ip');

        $validator
            ->dateTime('last_denied')
            ->allowEmptyDateTime('last_denied');

        $validator
            ->scalar('last_denied_ip')
            ->maxLength('last_denied_ip', 39)
            ->allowEmptyString('last_denied_ip');

        $validator
            ->uuid('customer_id')
            ->requirePresence('customer_id', 'create')
            ->notEmptyString('customer_id');

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
        $rules->add($rules->isUnique(['login']), ['errorField' => 'login']);
        $rules->add($rules->existsIn(['customer_id'], 'Customers'), ['errorField' => 'customer_id']);

        return $rules;
    }

    /**
     * A login no one has yet, made of the customer's names: `surname.first-name`, the company
     * where there are no names, as RADIUS accounts are named, and the customer number where there
     * is neither. A taken one gets `.2`, `.3` and so on.
     *
     * @param \App\Model\Entity\Customer $customer The customer.
     * @return string
     */
    public function suggestLogin(Customer $customer): string
    {
        // the dot goes only between names there are
        $login = self::loginOf(implode('.', array_filter(
            [trim((string)$customer->last_name), trim((string)$customer->first_name)],
            fn(string $name): bool => $name !== '',
        )));
        if ($login === '') {
            $login = self::loginOf(strtr((string)$customer->company, ['.' => '', ',' => '']));
        }
        if ($login === '') {
            $login = $customer->number;
        }

        $free = $login;
        $i = 1;
        while ($this->exists(['login' => $free])) {
            $free = $login . '.' . ++$i;
        }

        return $free;
    }

    /**
     * Text as a login: lower case without accents, words joined by dashes, nothing else.
     *
     * @param string $text The text.
     * @return string
     */
    private static function loginOf(string $text): string
    {
        $login = strtolower(Strings::removeAccents(trim($text)));
        $login = (string)preg_replace(['/\s*-\s*|\s+/', '/[^a-z0-9.\-]/', '/-{2,}/'], ['-', '', '-'], $login);

        return trim($login, '.-');
    }
}
