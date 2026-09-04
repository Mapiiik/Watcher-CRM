<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Contracts\Proposal\ProposalChanges;
use App\Contracts\Proposal\ProposalConfirmations;
use App\Contracts\Proposal\ProposalSnapshot;
use App\Contracts\Proposal\ProposedVersion;
use App\Model\Entity\ContractVersion;
use App\Model\Entity\ContractVersionProposal;
use App\Model\Enum\ContractDeliveryMethod;
use Cake\Database\Type\EnumType;
use Cake\I18n\Date;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use InvalidArgumentException;
use Override;

/**
 * ContractVersionProposals Model
 *
 * @property \App\Model\Table\ContractsTable&\Cake\ORM\Association\BelongsTo $Contracts
 * @property \App\Model\Table\ContractVersionsTable&\Cake\ORM\Association\BelongsTo $ContractVersions
 * @property \App\Model\Table\ContractVersionsTable&\Cake\ORM\Association\BelongsTo $TerminatedContractVersions
 * @method \App\Model\Entity\ContractVersionProposal newEmptyEntity()
 * @method \App\Model\Entity\ContractVersionProposal newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\ContractVersionProposal> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\ContractVersionProposal get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\ContractVersionProposal findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\ContractVersionProposal patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\ContractVersionProposal> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\ContractVersionProposal|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\ContractVersionProposal saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\ContractVersionProposal>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\ContractVersionProposal>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\ContractVersionProposal>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\ContractVersionProposal> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\ContractVersionProposal>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\ContractVersionProposal>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\ContractVersionProposal>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\ContractVersionProposal> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ContractVersionProposalsTable extends AppTable
{
    /**
     * What may no longer be touched once the papers have gone out.
     *
     * @var array<string>
     */
    private const SETTLED_ONCE_SENT = [
        'snapshot',
        'changes',
        'confirmations',
        'effective_from',
        'terminates_contract_version_id',
        'terminated_contract_number',
    ];

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

        $this->setTable('contract_version_proposals');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->getSchema()->setColumnType('snapshot', 'json');
        $this->getSchema()->setColumnType('changes', 'json');
        $this->getSchema()->setColumnType('confirmations', 'json');
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
        $this->belongsTo('ContractVersions', [
            'foreignKey' => 'contract_version_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('TerminatedContractVersions', [
            'className' => 'ContractVersions',
            'foreignKey' => 'terminates_contract_version_id',
            'joinType' => 'LEFT',
        ]);
    }

    /**
     * Proposals nobody has settled yet, one way or the other.
     *
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\ContractVersionProposal> $query Base query.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\ContractVersionProposal>
     */
    public function findOpen(SelectQuery $query): SelectQuery
    {
        return $query->where([
            $this->aliasField('applied') . ' IS' => null,
            $this->aliasField('revoked') . ' IS' => null,
        ]);
    }

    /**
     * Proposals the customer has agreed to and nobody has carried over.
     *
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\ContractVersionProposal> $query Base query.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\ContractVersionProposal>
     */
    public function findPendingTransfer(SelectQuery $query): SelectQuery
    {
        return $this->findOpen($query)
            ->where([$this->aliasField('conclusion_date') . ' IS NOT' => null]);
    }

    /**
     * Whether the proposal may still be changed.
     *
     * Sending is what locks it, not signing: what stood behind a paper that has left the building
     * is not rewritten afterwards. A correction is a new proposal, and the old one is revoked.
     *
     * @param \App\Model\Entity\ContractVersionProposal $proposal The proposal being asked about.
     * @return bool
     */
    public function mayBeEdited(ContractVersionProposal $proposal): bool
    {
        return !$proposal->hasBeenSent()
            && !$proposal->hasBeenApplied()
            && !$proposal->hasBeenRevoked();
    }

    /**
     * Whether the proposal may be taken back altogether.
     *
     * A sent paper is the record of what went out and a carried-over one of what happened, and
     * neither is ours to remove. A revoked proposal that never went anywhere is somebody's mistake
     * rather than history, so that one may go.
     *
     * @param \App\Model\Entity\ContractVersionProposal $proposal The proposal being asked about.
     * @return bool
     */
    public function mayBeDeleted(ContractVersionProposal $proposal): bool
    {
        return !$proposal->hasBeenSent() && !$proposal->hasBeenApplied();
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
            ->uuid('contract_version_id')
            ->requirePresence('contract_version_id', 'create')
            ->notEmptyString('contract_version_id');

        $validator
            ->uuid('terminates_contract_version_id')
            ->allowEmptyString('terminates_contract_version_id');

        $validator
            ->scalar('terminated_contract_number')
            ->maxLength('terminated_contract_number', 255)
            ->allowEmptyString('terminated_contract_number');

        $validator
            ->date('effective_from')
            ->requirePresence('effective_from', 'create')
            ->notEmptyDate('effective_from');

        $validator
            ->array('snapshot')
            ->requirePresence('snapshot', 'create')
            ->notEmptyArray('snapshot');

        $validator
            ->dateTime('snapshot_taken')
            ->requirePresence('snapshot_taken', 'create')
            ->notEmptyDateTime('snapshot_taken');

        $validator
            ->array('changes')
            ->requirePresence('changes', 'create')
            ->allowEmptyArray('changes');

        $validator
            ->array('confirmations')
            ->allowEmptyArray('confirmations');

        $validator
            ->date('sent_date')
            ->allowEmptyDate('sent_date');

        $validator
            ->allowEmptyString('sent_by');

        $validator
            ->date('conclusion_date')
            ->allowEmptyDate('conclusion_date');

        $validator
            ->dateTime('applied')
            ->allowEmptyDateTime('applied');

        $validator
            ->uuid('applied_by')
            ->allowEmptyString('applied_by');

        $validator
            ->dateTime('revoked')
            ->allowEmptyDateTime('revoked');

        $validator
            ->uuid('revoked_by')
            ->allowEmptyString('revoked_by');

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
        $rules->add($rules->existsIn(['contract_id'], 'Contracts'), ['errorField' => 'contract_id']);
        $rules->add(
            $rules->existsIn(['contract_version_id'], 'ContractVersions'),
            ['errorField' => 'contract_version_id'],
        );
        $rules->add(
            $rules->existsIn(['terminates_contract_version_id'], 'TerminatedContractVersions'),
            ['errorField' => 'terminates_contract_version_id'],
        );

        // A sent paper is the record of what went out and a carried-over one of what happened.
        // Permissions keep the buttons away, but the last word is here, so that an administrator
        // going straight at it is asked the same question.
        $rules->addDelete(
            fn(ContractVersionProposal $entity): bool => $this->mayBeDeleted($entity),
            'settledProposalIsNotRemoved',
            [
                'errorField' => 'sent_date',
                'message' => __(
                    'The papers for this proposal have gone out, so the record of them stays.'
                    . ' Revoke it instead.',
                ),
            ],
        );

        $this->addShapeRules($rules);
        $this->addBelongingRules($rules);
        $this->addTerminationRules($rules);
        $this->addPaperworkRules($rules);

        return $rules;
    }

    /**
     * The stored shapes have to be ones the value objects will answer for.
     *
     * Nothing else in the application looks inside these columns, so if a malformed one got in it
     * would surface at printing, which is the worst place to find out.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return void
     */
    private function addShapeRules(RulesChecker $rules): void
    {
        $rules->add(
            function (ContractVersionProposal $entity): bool {
                try {
                    $entity->proposedChanges();
                } catch (InvalidArgumentException) {
                    return false;
                }

                return true;
            },
            'changesAreShaped',
            [
                'errorField' => 'changes',
                'message' => __('The proposed changes are not in a shape the application can read.'),
            ],
        );

        $rules->add(
            function (ContractVersionProposal $entity): bool {
                try {
                    $entity->stateOfThings();
                } catch (InvalidArgumentException) {
                    return false;
                }

                return true;
            },
            'snapshotIsShaped',
            [
                'errorField' => 'snapshot',
                'message' => __('The snapshot does not carry everything the documents need.'),
            ],
        );

        $rules->add(
            function (ContractVersionProposal $entity): bool {
                try {
                    $entity->confirmations();
                } catch (InvalidArgumentException) {
                    return false;
                }

                return true;
            },
            'confirmationsAreShaped',
            [
                'errorField' => 'confirmations',
                'message' => __('The confirmations name a question nobody asks.'),
            ],
        );

        // A line may only act on a billing the snapshot knows; otherwise there is nothing to say
        // what it replaces, and the preview before transfer would have nothing to compare against.
        $rules->add(
            function (ContractVersionProposal $entity): bool {
                $snapshot = $this->readSnapshot($entity);

                if ($snapshot === null) {
                    return true;
                }

                foreach ($this->readChanges($entity)->billings ?? [] as $line) {
                    if (!$line->isAddition() && !$snapshot->knowsBilling((string)$line->billing_id)) {
                        return false;
                    }
                }

                return true;
            },
            'billingIsInTheSnapshot',
            [
                'errorField' => 'changes',
                'message' => __('A proposed change acts on a billing the snapshot does not know.'),
            ],
        );

        // Two lines on one billing would end it twice and start two replacements at once.
        $rules->add(
            function (ContractVersionProposal $entity): bool {
                $named = [];

                foreach ($this->readChanges($entity)->billings ?? [] as $line) {
                    if ($line->isAddition()) {
                        continue;
                    }

                    if (in_array($line->billing_id, $named, true)) {
                        return false;
                    }

                    $named[] = $line->billing_id;
                }

                return true;
            },
            'noTwoItemsOnTheSameBilling',
            [
                'errorField' => 'changes',
                'message' => __('Two proposed changes act on the same billing.'),
            ],
        );
    }

    /**
     * The proposal, its version and the version it terminates all have to belong together.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return void
     */
    private function addBelongingRules(RulesChecker $rules): void
    {
        $rules->add(
            function (ContractVersionProposal $entity): bool {
                $version = $this->versionOf($entity->contract_version_id);

                return $version === null || $version->contract_id === $entity->contract_id;
            },
            'proposalBelongsToItsContract',
            [
                'errorField' => 'contract_version_id',
                'message' => __('The contract version belongs to a different contract.'),
            ],
        );

        $rules->add(
            function (ContractVersionProposal $entity): bool {
                if (!$entity->terminatesAnotherVersion()) {
                    return true;
                }

                $terminated = $this->versionOf($entity->terminates_contract_version_id);

                return $terminated === null || $terminated->contract_id === $entity->contract_id;
            },
            'terminatedVersionIsOnTheSameContract',
            [
                'errorField' => 'terminates_contract_version_id',
                'message' => __('The terminated contract version belongs to a different contract.'),
            ],
        );

        $rules->add(
            fn(ContractVersionProposal $entity): bool => !$entity->terminatesAnotherVersion()
                || $entity->terminates_contract_version_id !== $entity->contract_version_id,
            'terminatedVersionIsNotTheOneProposed',
            [
                'errorField' => 'terminates_contract_version_id',
                'message' => __('A contract version cannot terminate itself.'),
            ],
        );
    }

    /**
     * What has to hold before a proposal may end anything.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return void
     */
    private function addTerminationRules(RulesChecker $rules): void
    {
        $rules->add(
            function (ContractVersionProposal $entity): bool {
                if (!$entity->terminatesAnotherVersion()) {
                    return true;
                }

                $terminated = $this->versionOf($entity->terminates_contract_version_id);

                return $terminated === null || $terminated->conclusion_date !== null;
            },
            'terminatedVersionIsConcluded',
            [
                'errorField' => 'terminates_contract_version_id',
                'message' => __('The contract version being terminated has not been concluded.'),
            ],
        );

        $rules->add(
            function (ContractVersionProposal $entity): bool {
                $changes = $this->readChanges($entity);

                if ($changes === null || !$changes->version->endsTheVersion()) {
                    return true;
                }

                $version = $this->versionOf($entity->contract_version_id);

                return $version === null || $version->conclusion_date !== null;
            },
            'endedVersionIsConcluded',
            [
                'errorField' => 'changes',
                'message' => __('A contract version that has not been concluded cannot be terminated.'),
            ],
        );

        // Ending is one act written in two places, and letting the two dates drift apart would put
        // the paper's end on one day and the invoicing's on another.
        $rules->add(
            function (ContractVersionProposal $entity): bool {
                $changes = $this->readChanges($entity);

                if ($changes === null) {
                    return true;
                }

                $endsVersion = $changes->version->endsTheVersion();
                $endsContract = $changes->contract->endsTheContract();

                if (!$endsVersion && !$endsContract) {
                    return true;
                }

                if (!$endsVersion || !$endsContract) {
                    return false;
                }

                $versionEnds = $changes->version->get('valid_until');
                $contractEnds = $changes->contract->get('termination_date');

                return $versionEnds !== null
                    && $contractEnds !== null
                    && $versionEnds->equals($contractEnds);
            },
            'terminationDatesAgree',
            [
                'errorField' => 'changes',
                'message' => __(
                    'Say both when the contract version stops being valid and when the contract'
                    . ' is terminated, and say the same day.',
                ),
            ],
        );

        // The number goes on the paper, and a proposal is where it now stays; before, it was typed
        // in at every printing and thrown away afterwards.
        $rules->add(
            function (ContractVersionProposal $entity): bool {
                $changes = $this->readChanges($entity);
                $ends = $entity->terminatesAnotherVersion()
                    || ($changes !== null && $changes->endsTheContract());

                return !$ends || ($entity->terminated_contract_number ?? '') !== '';
            },
            'terminatedContractNumberIsGiven',
            [
                'errorField' => 'terminated_contract_number',
                'message' => __('Please enter the number of the contract being terminated.'),
            ],
        );

        // An end date on a version does not say by itself that the contract is for a fixed term -
        // it is also how a superseded version is recorded - and a fixed term is its own minimum
        // period of performance, so the obligation has to reach the end of it.
        $rules->add(
            function (ContractVersionProposal $entity): bool {
                $ends = $this->versionEndAfterProposal($entity);

                if ($ends === null) {
                    return true;
                }

                if (!$this->readConfirmations($entity)?->confirms(ProposalConfirmations::FIXED_TERM)) {
                    return false;
                }

                $obligation = $this->versionObligationAfterProposal($entity);

                return $obligation !== null && $obligation->equals($ends);
            },
            'fixedTermIsAcknowledged',
            [
                'errorField' => 'confirmations',
                'message' => __(
                    'The contract version ends on a given date, so the papers will be printed as a'
                    . ' fixed-term contract. Please confirm that this is intended and set the'
                    . ' obligation to the date until which the version is valid.',
                ),
            ],
        );
    }

    /**
     * What has to hold about the record of the paper itself.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return void
     */
    private function addPaperworkRules(RulesChecker $rules): void
    {
        // A way with no day does not say when, a day with no way does not say how it could be
        // shown, and either on its own reads later as a record when it is half of one.
        $rules->add(
            fn(ContractVersionProposal $entity): bool => ($entity->sent_date === null) === ($entity->sent_by === null),
            'sendingIsRecordedWhole',
            [
                'errorField' => 'sent_by',
                'message' => __('Say both when the papers were sent and how, or neither.'),
            ],
        );

        // Once the papers have gone out, what stood behind them is settled. Recording that they
        // went again, or that they came back signed, is not rewriting it.
        $rules->add(
            function (ContractVersionProposal $entity): bool {
                if ($entity->isNew() || $entity->getOriginal('sent_date') === null) {
                    return true;
                }

                foreach (self::SETTLED_ONCE_SENT as $field) {
                    if ($entity->isDirty($field)) {
                        return false;
                    }
                }

                return true;
            },
            'sentProposalIsNotRewritten',
            [
                'errorField' => 'changes',
                'message' => __(
                    'The papers for this proposal have already been sent, so what stands behind'
                    . ' them is not changed here. Revoke it and draw up a new one.',
                ),
            ],
        );

        // The transfer offers itself only on a concluded proposal and checks again before it
        // writes, but the last word is here, so that no other way in can get around it.
        $rules->add(
            fn(ContractVersionProposal $entity): bool => $entity->applied === null || $entity->conclusion_date !== null,
            'appliedNeedsAConclusion',
            [
                'errorField' => 'applied',
                'message' => __('A proposal cannot be carried over before it has been concluded.'),
            ],
        );

        $rules->add(
            fn(ContractVersionProposal $entity): bool => $entity->applied === null || $entity->revoked === null,
            'appliedAndRevokedExcludeEachOther',
            [
                'errorField' => 'revoked',
                'message' => __('A proposal that has been carried over cannot also be revoked.'),
            ],
        );
    }

    /**
     * The day the version stops being valid once the proposal has been carried over.
     *
     * @param \App\Model\Entity\ContractVersionProposal $entity The proposal being asked about.
     * @return \Cake\I18n\Date|null Null when the version runs on.
     */
    private function versionEndAfterProposal(ContractVersionProposal $entity): ?Date
    {
        return $this->versionDateAfterProposal($entity, 'valid_until');
    }

    /**
     * The day the obligation runs out once the proposal has been carried over.
     *
     * @param \App\Model\Entity\ContractVersionProposal $entity The proposal being asked about.
     * @return \Cake\I18n\Date|null Null when nothing binds the customer.
     */
    private function versionObligationAfterProposal(ContractVersionProposal $entity): ?Date
    {
        return $this->versionDateAfterProposal($entity, 'obligation_until');
    }

    /**
     * One of the version's dates as it will stand once the proposal has been carried over.
     *
     * What the proposal names wins, including when it names it empty; what it does not name is
     * whatever the version says today.
     *
     * @param \App\Model\Entity\ContractVersionProposal $entity The proposal being asked about.
     * @param string $field Which date.
     * @return \Cake\I18n\Date|null
     */
    private function versionDateAfterProposal(ContractVersionProposal $entity, string $field): ?Date
    {
        $proposed = $this->readChanges($entity)->version ?? ProposedVersion::untouched();

        if ($proposed->names($field)) {
            return $proposed->get($field);
        }

        return $this->versionOf($entity->contract_version_id)?->get($field);
    }

    /**
     * The version a proposal names, or null when it names none that exists.
     *
     * @param string|null $id Which version.
     * @return \App\Model\Entity\ContractVersion|null
     */
    private function versionOf(?string $id): ?ContractVersion
    {
        if ($id === null) {
            return null;
        }

        /** @var \App\Model\Entity\ContractVersion|null $version */
        $version = $this->ContractVersions->find()
            ->where(['ContractVersions.id' => $id])
            ->first();

        return $version;
    }

    /**
     * The proposed changes, or null when they are in no shape to be read.
     *
     * The shape itself is somebody else's rule, so the rules that read the changes just stand
     * aside rather than reporting the same fault twice.
     *
     * @param \App\Model\Entity\ContractVersionProposal $entity The proposal being asked about.
     * @return \App\Contracts\Proposal\ProposalChanges|null
     */
    private function readChanges(ContractVersionProposal $entity): ?ProposalChanges
    {
        try {
            return $entity->proposedChanges();
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * The snapshot, or null when it is in no shape to be read.
     *
     * @param \App\Model\Entity\ContractVersionProposal $entity The proposal being asked about.
     * @return \App\Contracts\Proposal\ProposalSnapshot|null
     */
    private function readSnapshot(ContractVersionProposal $entity): ?ProposalSnapshot
    {
        try {
            return $entity->stateOfThings();
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * The confirmations, or null when they are in no shape to be read.
     *
     * @param \App\Model\Entity\ContractVersionProposal $entity The proposal being asked about.
     * @return \App\Contracts\Proposal\ProposalConfirmations|null
     */
    private function readConfirmations(ContractVersionProposal $entity): ?ProposalConfirmations
    {
        try {
            return $entity->confirmations();
        } catch (InvalidArgumentException) {
            return null;
        }
    }
}
