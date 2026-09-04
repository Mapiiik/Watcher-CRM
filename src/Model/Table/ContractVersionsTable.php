<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\ContractVersion;
use App\Model\Enum\ContractDeliveryMethod;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Type\EnumType;
use Cake\I18n\Date;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use Override;

/**
 * ContractVersions Model
 *
 * @property \App\Model\Table\ContractsTable&\Cake\ORM\Association\BelongsTo $Contracts
 * @property \App\Model\Table\ContractVersionProposalsTable&\Cake\ORM\Association\HasMany $ContractVersionProposals
 * @method \App\Model\Entity\ContractVersion newEmptyEntity()
 * @method \App\Model\Entity\ContractVersion newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\ContractVersion[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\ContractVersion get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\ContractVersion findOrCreate($search, callable|array|null $callback = null, $options = [])
 * @method \App\Model\Entity\ContractVersion patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\ContractVersion[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\ContractVersion|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\ContractVersion saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<\App\Model\Entity\ContractVersion>|false saveMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\ContractVersion> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\ContractVersion>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\ContractVersion> deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ContractVersionsTable extends AppTable
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

        $this->setTable('contract_versions');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->getSchema()->setColumnType(
            'sent_by',
            EnumType::from(ContractDeliveryMethod::class),
        );

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');

        $this->belongsTo('Contracts', [
            'foreignKey' => 'contract_id',
            'joinType' => 'INNER',
        ]);

        $this->hasMany('ContractVersionProposals', [
            'foreignKey' => 'contract_version_id',
            'sort' => ['ContractVersionProposals.effective_from' => 'DESC'],
        ]);
    }

    /**
     * Versions whose minimum term runs out shortly and still binds anybody.
     *
     * A version that a later one has replaced is left out. Its term is on record and often
     * still unsettled, because nobody goes back to tick a version that has been re-signed
     * over - but the term that binds the customer is the one on the version that replaced
     * it, and raising the old one asks somebody to act on nothing.
     *
     * What is not left out is a version whose validity has run out with no later one behind
     * it. That is a contract that has ended while its term runs on, which is the case most
     * worth seeing here.
     *
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\ContractVersion> $query The query to scope.
     * @param int $within_days How far ahead the end of a term is looked for.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\ContractVersion>
     */
    public function findObligationsEnding(SelectQuery $query, int $within_days): SelectQuery
    {
        $today = Date::today();

        $replaced = $this->getConnection()
            ->selectQuery()
            ->select(1)
            ->from(['LaterVersions' => 'contract_versions'])
            ->where([
                'LaterVersions.contract_id' => new IdentifierExpression('ContractVersions.contract_id'),
                'LaterVersions.valid_from >' => new IdentifierExpression('ContractVersions.valid_from'),
            ]);

        return $query
            ->where([
                'ContractVersions.obligations_settled' => false,
                'ContractVersions.obligation_until >=' => $today,
                'ContractVersions.obligation_until <=' => $today->addDays($within_days),
            ])
            ->where(fn(QueryExpression $exp): QueryExpression => $exp->notExists($replaced));
    }

    /**
     * Whether a version may still be taken back.
     *
     * Two things have to hold. Nothing was signed - a version with paper behind it is the record
     * of what the customer agreed to, and that is not ours to remove. And it belongs to the month
     * being lived through or to one ahead of it, which is what a version somebody is still putting
     * together looks like. Older ones are history even where the paperwork never caught up, and on
     * file there are a thousand of those from one import.
     *
     * @param \App\Model\Entity\ContractVersion $version The version being asked about.
     * @return bool
     */
    public function mayBeDeleted(ContractVersion $version): bool
    {
        return $version->conclusion_date === null
            && $version->valid_from >= Date::now()->firstOfMonth();
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
            ->uuid('contract_id')
            ->requirePresence('contract_id', 'create')
            ->notEmptyString('contract_id');

        $validator
            ->date('valid_from')
            ->requirePresence('valid_from', 'create')
            ->notEmptyDate('valid_from');

        $validator
            ->date('valid_until')
            ->allowEmptyDate('valid_until');

        $validator
            ->date('obligation_until')
            ->allowEmptyDate('obligation_until');

        $validator
            ->boolean('obligations_settled')
            ->notEmptyString('obligations_settled');

        $validator
            ->date('conclusion_date')
            ->allowEmptyDate('conclusion_date');

        $validator
            ->date('sent_date')
            ->allowEmptyDate('sent_date');

        $validator
            ->allowEmptyString('sent_by');

        $validator
            ->integer('number_of_amendments')
            ->notEmptyString('number_of_amendments');

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
    #[Override]
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn('contract_id', 'Contracts'), ['errorField' => 'contract_id']);

        // A version may still be taken back while nobody has signed it, and its proposals are the
        // record of the papers that were drawn up for it - so it goes with them or not at all.
        $rules->addDelete($rules->isNotLinkedTo('ContractVersionProposals'));

        // Both of these are asked whenever a version is saved, not only when a date has been
        // touched. A stretch of time that cannot exist was wrong the day it was written and time
        // does not put it right, so whoever opens such a version is the one to look it up and
        // correct it - the same as a contract missing what its service type requires.
        $rules->add(
            function (ContractVersion $entity): bool {
                if ($entity->valid_from === null || $entity->valid_until === null) {
                    return true;
                }

                // a version in force for a single day is a real one
                return $entity->valid_until >= $entity->valid_from;
            },
            'contractVersionPeriodIsPossible',
            [
                'errorField' => 'valid_until',
                'message' => __('The contract version cannot end before it begins.'),
            ],
        );

        $rules->add(
            function (ContractVersion $entity): bool {
                if ($entity->valid_from === null || $entity->obligation_until === null) {
                    return true;
                }

                // the term belongs to the version, so it cannot have run out before it existed
                return $entity->obligation_until >= $entity->valid_from;
            },
            'obligationEndsAfterItsVersionBegins',
            [
                'errorField' => 'obligation_until',
                'message' => __('The minimum term cannot run out before the version it belongs to begins.'),
            ],
        );

        // The two halves of the sending record answer for each other. A way with no day does
        // not say when, a day with no way does not say how it could be shown, and either on
        // its own reads later as a record when it is half of one.
        $rules->add(
            fn(ContractVersion $entity): bool => ($entity->sent_date === null) === ($entity->sent_by === null),
            'sendingIsRecordedWhole',
            [
                'errorField' => 'sent_by',
                'message' => __('Say both when the papers were sent and how, or neither.'),
            ],
        );

        return $rules;
    }
}
