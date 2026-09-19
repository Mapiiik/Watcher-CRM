<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Contracts\Proposal\ProposalChanges;
use App\Contracts\Proposal\ProposalConfirmations;
use App\Contracts\Proposal\ProposalSnapshot;
use App\Contracts\Proposal\ProposedVersion;
use App\Model\Entity\ContractProposal;
use App\Model\Entity\ContractVersion;
use App\Model\Entity\CustomerProposal;
use App\Model\Enum\ProposalPurpose;
use App\Service\ContractPrint\ContractDocuments;
use Cake\Database\Type\EnumType;
use Cake\I18n\Date;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use InvalidArgumentException;
use Override;

/**
 * ContractProposals Model
 *
 * @property \App\Model\Table\ContractsTable&\Cake\ORM\Association\BelongsTo $Contracts
 * @property \App\Model\Table\ContractVersionsTable&\Cake\ORM\Association\BelongsTo $ContractVersions
 * @property \App\Model\Table\CustomerProposalsTable&\Cake\ORM\Association\BelongsTo $CustomerProposals
 * @property \App\Model\Table\ContractVersionsTable&\Cake\ORM\Association\BelongsTo $TerminatedContractVersions
 * @method \App\Model\Entity\ContractProposal newEmptyEntity()
 * @method \App\Model\Entity\ContractProposal newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\ContractProposal> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\ContractProposal get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\ContractProposal findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\ContractProposal patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\ContractProposal> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\ContractProposal|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\ContractProposal saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\ContractProposal>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\ContractProposal>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\ContractProposal>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\ContractProposal> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\ContractProposal>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\ContractProposal>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\ContractProposal>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\ContractProposal> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ContractProposalsTable extends AppTable
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

        $this->setTable('contract_proposals');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->getSchema()->setColumnType('snapshot', 'json');
        $this->getSchema()->setColumnType('changes', 'json');
        $this->getSchema()->setColumnType('confirmations', 'json');
        $this->getSchema()->setColumnType(
            'purpose',
            EnumType::from(ProposalPurpose::class),
        );

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');

        $this->belongsTo('Contracts', [
            'foreignKey' => 'contract_id',
            'joinType' => 'INNER',
        ]);
        // Left, because a paper for a new contract may be drawn up before the version it is
        // about exists - applying it is what brings that version into being.
        $this->belongsTo('ContractVersions', [
            'foreignKey' => 'contract_version_id',
            'joinType' => 'LEFT',
        ]);
        // The envelope these papers go out in, and where the sending and the signature are kept.
        $this->belongsTo('CustomerProposals', [
            'foreignKey' => 'customer_proposal_id',
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
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\ContractProposal> $query Base query.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\ContractProposal>
     */
    public function findOpen(SelectQuery $query): SelectQuery
    {
        return $query->where([
            $this->aliasField('applied') . ' IS' => null,
            $this->aliasField('revoked') . ' IS' => null,
        ]);
    }

    /**
     * Proposals the customer has agreed to and nobody has applied.
     *
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\ContractProposal> $query Base query.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\ContractProposal>
     */
    public function findWaitingToBeApplied(SelectQuery $query): SelectQuery
    {
        // The signature is on the envelope, so the papers are asked about through it.
        return $this->findOpen($query)
            ->innerJoinWith('CustomerProposals')
            ->where(['CustomerProposals.conclusion_date IS NOT' => null]);
    }

    /**
     * Whether the proposal may still be changed.
     *
     * Sending is what locks it, not signing: what stood behind a paper that has left the building
     * is not rewritten afterwards. A correction is a new proposal, and the old one is revoked.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal being asked about.
     * @return bool
     */
    public function mayBeEdited(ContractProposal $proposal): bool
    {
        return $this->CustomerProposals->mayBeEdited($this->theEnvelopeOf($proposal))
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
     * @param \App\Model\Entity\ContractProposal $proposal The proposal being asked about.
     * @return bool
     */
    public function mayBeDeleted(ContractProposal $proposal): bool
    {
        // Asked of the envelope directly rather than through its own gate: what stops the envelope
        // going is partly that these very papers are in it, which is no reason to keep them.
        $envelope = $this->theEnvelopeOf($proposal);

        return !$envelope->hasBeenSent()
            && !$envelope->hasBeenConcluded()
            && !$proposal->hasBeenApplied()
            && !$this->anyPapersAreFiledAgainst($proposal);
    }

    /**
     * The envelope a set of papers goes out in, fetched where it did not come with them.
     *
     * The gates are asked in places that load as little as they can - the permissions read three
     * columns and nothing else - so the envelope is looked up rather than required.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The papers being asked about.
     * @return \App\Model\Entity\CustomerProposal
     */
    private function theEnvelopeOf(ContractProposal $proposal): CustomerProposal
    {
        if (isset($proposal->customer_proposal)) {
            return $proposal->customer_proposal;
        }

        /** @var \App\Model\Entity\CustomerProposal $envelope */
        $envelope = $this->CustomerProposals->get($proposal->customer_proposal_id);

        return $envelope;
    }

    /**
     * Whether any paper is filed against these papers.
     *
     * A document that was drawn or came back is the record of something that happened, and letting
     * the papers go would leave it pointing at nothing - reachable from nowhere, and holding on to
     * its file for ever.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The papers being asked about.
     * @return bool
     */
    private function anyPapersAreFiledAgainst(ContractProposal $proposal): bool
    {
        return TableRegistry::getTableLocator()->get('Files.FileLinks')->exists([
            'FileLinks.model' => ContractDocuments::MODEL,
            'FileLinks.foreign_key' => $proposal->id,
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
            ->uuid('contract_id')
            ->requirePresence('contract_id', 'create')
            ->notEmptyString('contract_id');

        // Empty says the version is still to come. Which purposes may say that is a rule rather
        // than a matter of shape, so it is checked where the other rules are.
        $validator
            ->uuid('contract_version_id')
            ->allowEmptyString('contract_version_id');

        $validator
            ->uuid('customer_proposal_id')
            ->allowEmptyString('customer_proposal_id');

        $validator
            ->requirePresence('purpose', 'create')
            ->notEmptyString('purpose');

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
            fn(ContractProposal $entity): bool => $this->mayBeDeleted($entity),
            'settledProposalIsNotRemoved',
            [
                'errorField' => 'customer_proposal_id',
                'message' => __(
                    'This proposal has been sent or has documents on file, so the record of it'
                    . ' stays. Revoke it instead.',
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
            function (ContractProposal $entity): bool {
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
            function (ContractProposal $entity): bool {
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
            function (ContractProposal $entity): bool {
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
        // what it replaces, and the preview before the changes are applied would have nothing to compare against.
        $rules->add(
            function (ContractProposal $entity): bool {
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
            function (ContractProposal $entity): bool {
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
            function (ContractProposal $entity): bool {
                $version = $this->versionOf($entity->contract_version_id);

                return $version === null || $version->contract_id === $entity->contract_id;
            },
            'proposalBelongsToItsContract',
            [
                'errorField' => 'contract_version_id',
                'message' => __('The contract version belongs to a different contract.'),
            ],
        );

        // Papers for a new contract may be drawn up before the version they are about exists, and
        // applying them brings it into being. Nothing else has anything to start: a change
        // amends a version that was agreed to and an ending ends one that is running.
        $rules->add(
            function (ContractProposal $entity): bool {
                return $entity->contract_version_id !== null
                    || $entity->purpose->mayStartAVersion()
                    || !$entity->keepsVersions();
            },
            'onlyANewContractStartsItsVersion',
            [
                'errorField' => 'contract_version_id',
                'message' => __('A contract proposal for this purpose is about a version that'
                    . ' already exists.'),
            ],
        );

        // A contract whose service keeps no versions has none to name, end or change: the proposal
        // is about its billings and the contract alone.
        $rules->add(
            function (ContractProposal $entity): bool {
                if ($entity->keepsVersions()) {
                    return true;
                }

                $changes = $this->readChanges($entity);

                return $entity->contract_version_id === null
                    && $entity->terminates_contract_version_id === null
                    && ($changes === null || $changes->version->isEmpty());
            },
            'noVersionWhereTheServiceKeepsNone',
            [
                'errorField' => 'contract_version_id',
                'message' => __('The service of this contract keeps no contract versions.'),
            ],
        );

        // An envelope is the customer's, so it cannot hold papers of somebody else's contract.
        $rules->add(
            function (ContractProposal $entity): bool {
                $round = $this->roundOf($entity->customer_proposal_id);
                if ($round === null) {
                    return true;
                }

                $contract = $this->Contracts->find()
                    ->where(['Contracts.id' => $entity->contract_id])
                    ->first();

                return $contract === null || $contract->customer_id === $round->customer_id;
            },
            'proposalBelongsToItsRound',
            [
                'errorField' => 'customer_proposal_id',
                'message' => __('That customer proposal belongs to a different customer.'),
            ],
        );

        // Adding papers to an envelope is changing the envelope, and one that has gone out or come
        // back signed is not changed - the papers would otherwise read as having gone out with it
        // when they were never in it. Whoever needs them draws up a proposal of their own.
        $rules->add(
            function (ContractProposal $entity): bool {
                if (!$entity->isDirty('customer_proposal_id')) {
                    return true;
                }

                return $this->CustomerProposals->mayBeEdited($this->theEnvelopeOf($entity));
            },
            'papersJoinAnOpenRound',
            [
                'errorField' => 'customer_proposal_id',
                'message' => __('That proposal has already gone out, so nothing more goes in it.'),
            ],
        );

        // A contract proposal comes back signed, and a customer proposal that is only handed over
        // never does, so the two cannot go out together.
        $rules->add(
            function (ContractProposal $entity): bool {
                if (!$entity->isDirty('customer_proposal_id')) {
                    return true;
                }

                return $this->roundOf($entity->customer_proposal_id)?->purpose?->comesBackSigned() ?? true;
            },
            'papersJoinARoundThatIsSigned',
            [
                'errorField' => 'customer_proposal_id',
                'message' => __('That customer proposal is only handed over, so no contract proposal goes in it.'),
            ],
        );

        // One contract, one set of papers in an envelope. Two of them go out in the same letter
        // and come back on the same day, so which of them was agreed to would be nobody's to say -
        // and a contract that really wants two answers wants two proposals. Papers given up on are
        // not in the letter, so they leave the way clear for the ones that replace them.
        $rules->add(
            function (ContractProposal $entity): bool {
                if ($entity->customer_proposal_id === null || $entity->hasBeenRevoked()) {
                    return true;
                }

                $conditions = [
                    'ContractProposals.customer_proposal_id' => $entity->customer_proposal_id,
                    'ContractProposals.contract_id' => $entity->contract_id,
                    'ContractProposals.revoked IS' => null,
                ];

                if (!$entity->isNew()) {
                    $conditions['ContractProposals.id !='] = $entity->id;
                }

                return !$this->exists($conditions);
            },
            'oneSetOfPapersPerContractInARound',
            [
                'errorField' => 'customer_proposal_id',
                'message' => __('That customer proposal already holds a contract proposal for'
                    . ' this contract.'),
            ],
        );

        $rules->add(
            function (ContractProposal $entity): bool {
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
            fn(ContractProposal $entity): bool => !$entity->terminatesAnotherVersion()
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
        // A change is an amendment, and there is nothing to amend until the contract is signed.
        // Asked when the proposal is created or moved to another version, so that one already
        // being worked on is not locked by it.
        $rules->add(
            function (ContractProposal $entity): bool {
                if (
                    $entity->purpose !== ProposalPurpose::ServiceChange
                    || !$entity->keepsVersions()
                    || !($entity->isNew() || $entity->isDirty('contract_version_id'))
                ) {
                    return true;
                }

                $version = $this->versionOf($entity->contract_version_id);

                return $version === null || $version->conclusion_date !== null;
            },
            'aChangeAmendsASignedVersion',
            [
                'errorField' => 'contract_version_id',
                'message' => __('A change amends a signed contract. Record the signature of this'
                    . ' contract version first.'),
            ],
        );

        $rules->add(
            function (ContractProposal $entity): bool {
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
            function (ContractProposal $entity): bool {
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
            function (ContractProposal $entity): bool {
                $changes = $this->readChanges($entity);

                if ($changes === null) {
                    return true;
                }

                // Without versions the contract's own end is all there is to agree with.
                if (!$entity->keepsVersions()) {
                    return true;
                }

                $endsVersion = $changes->version->endsTheVersion();
                $endsContract = $changes->contract->endsTheContract();

                // A version ending while the contract runs on is how an agreement to end one
                // version and sign another is written, so only the other direction is held to.
                if (!$endsContract) {
                    return true;
                }

                if (!$endsVersion) {
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
                    'A contract is terminated on the day its version stops being valid. Say both,'
                    . ' and say the same day.',
                ),
            ],
        );

        // The purpose is a stored answer to a question the changes cannot be asked, so the two have
        // to be held together. The form cannot produce a disagreement between them; a customer
        // portal writing over the API could, and the purpose is the first thing it will send.
        $rules->add(
            function (ContractProposal $entity): bool {
                $changes = $this->readChanges($entity);

                if ($changes === null) {
                    return true;
                }

                if ($entity->purpose !== ProposalPurpose::Termination) {
                    return !$changes->contract->endsTheContract();
                }

                // An ending says its day on the version, or on the contract where there is none.
                return $entity->keepsVersions()
                    ? $changes->version->endsTheVersion()
                    : $changes->contract->endsTheContract();
            },
            'changesMatchThePurpose',
            [
                'errorField' => 'changes',
                'message' => __(
                    'What the proposal asks for does not match what it is for: only an ending ends'
                    . ' the contract, and an ending has to say the day.',
                ),
            ],
        );

        // The number goes on the paper, and a proposal is where it now stays; before, it was typed
        // in at every printing and thrown away afterwards.
        $rules->add(
            function (ContractProposal $entity): bool {
                $ends = $entity->terminatesAnotherVersion()
                    || $entity->purpose === ProposalPurpose::Termination;

                // Nothing is printed for a contract that keeps no versions, so nothing to put it on.
                return !$ends || !$entity->keepsVersions() || ($entity->terminated_contract_number ?? '') !== '';
            },
            'terminatedContractNumberIsGiven',
            [
                'errorField' => 'terminated_contract_number',
                'message' => __('Please enter the number of the contract being terminated.'),
            ],
        );

        // An end date on a version does not say by itself that the contract is for a fixed term -
        // it is also how an ending is written down, and how a superseded version is recorded - and
        // a fixed term is its own minimum period of performance, so the obligation has to reach the
        // end of it. Which of the two it is only the purpose can say; asking it of an ending would
        // have the obligation moved to the day the customer left, losing that they left early.
        $rules->add(
            function (ContractProposal $entity): bool {
                if ($entity->purpose === ProposalPurpose::Termination) {
                    return true;
                }

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
                    'The contract version ends on a given date, so the documents will be'
                    . ' printed as a fixed-term contract. Please confirm that this is intended'
                    . ' and set the obligation to the date until which the version is valid.',
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
        // Once the papers have gone out, what stood behind them is settled. Recording that they
        // went again, or that they came back signed, is not rewriting it.
        $rules->add(
            function (ContractProposal $entity): bool {
                if ($entity->isNew() || !$this->theEnvelopeOf($entity)->hasBeenSent()) {
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
                    'This proposal has already been sent, so what it says is not changed here.'
                    . ' Revoke it and create a new one.',
                ),
            ],
        );

        // Applying the changes offers itself only on a concluded proposal and checks again before it
        // writes, but the last word is here, so that no other way in can get around it.
        $rules->add(
            fn(ContractProposal $entity): bool => $entity->applied === null
                || $this->theEnvelopeOf($entity)->hasBeenConcluded(),
            'appliedNeedsAConclusion',
            [
                'errorField' => 'applied',
                'message' => __('The changes of a proposal cannot be applied before it has been'
                    . ' signed.'),
            ],
        );

        $rules->add(
            fn(ContractProposal $entity): bool => $entity->applied === null || $entity->revoked === null,
            'appliedAndRevokedExcludeEachOther',
            [
                'errorField' => 'revoked',
                'message' => __('A proposal whose changes have been applied cannot also be'
                    . ' revoked.'),
            ],
        );
    }

    /**
     * The day the version stops being valid once the proposal has been applied.
     *
     * @param \App\Model\Entity\ContractProposal $entity The proposal being asked about.
     * @return \Cake\I18n\Date|null Null when the version runs on.
     */
    private function versionEndAfterProposal(ContractProposal $entity): ?Date
    {
        return $this->versionDateAfterProposal($entity, 'valid_until');
    }

    /**
     * The day the obligation runs out once the proposal has been applied.
     *
     * @param \App\Model\Entity\ContractProposal $entity The proposal being asked about.
     * @return \Cake\I18n\Date|null Null when nothing binds the customer.
     */
    private function versionObligationAfterProposal(ContractProposal $entity): ?Date
    {
        return $this->versionDateAfterProposal($entity, 'obligation_until');
    }

    /**
     * One of the version's dates as it will stand once the proposal has been applied.
     *
     * What the proposal names wins, including when it names it empty; what it does not name is
     * whatever the version says today.
     *
     * @param \App\Model\Entity\ContractProposal $entity The proposal being asked about.
     * @param string $field Which date.
     * @return \Cake\I18n\Date|null
     */
    private function versionDateAfterProposal(ContractProposal $entity, string $field): ?Date
    {
        $proposed = $this->readChanges($entity)->version ?? ProposedVersion::untouched();

        if ($proposed->names($field)) {
            return $proposed->get($field);
        }

        return $this->versionOf($entity->contract_version_id)?->get($field);
    }

    /**
     * The round a proposal says it went out in, or null when it names none that exists.
     *
     * @param string|null $id Which round.
     * @return \App\Model\Entity\CustomerProposal|null
     */
    private function roundOf(?string $id): ?CustomerProposal
    {
        if ($id === null) {
            return null;
        }

        /** @var \App\Model\Entity\CustomerProposal|null $round */
        $round = $this->CustomerProposals->find()
            ->where(['CustomerProposals.id' => $id])
            ->first();

        return $round;
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
     * @param \App\Model\Entity\ContractProposal $entity The proposal being asked about.
     * @return \App\Contracts\Proposal\ProposalChanges|null
     */
    private function readChanges(ContractProposal $entity): ?ProposalChanges
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
     * @param \App\Model\Entity\ContractProposal $entity The proposal being asked about.
     * @return \App\Contracts\Proposal\ProposalSnapshot|null
     */
    private function readSnapshot(ContractProposal $entity): ?ProposalSnapshot
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
     * @param \App\Model\Entity\ContractProposal $entity The proposal being asked about.
     * @return \App\Contracts\Proposal\ProposalConfirmations|null
     */
    private function readConfirmations(ContractProposal $entity): ?ProposalConfirmations
    {
        try {
            return $entity->confirmations();
        } catch (InvalidArgumentException) {
            return null;
        }
    }
}
