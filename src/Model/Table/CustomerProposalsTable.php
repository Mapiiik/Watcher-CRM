<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\CustomerProposal;
use App\Model\Enum\CustomerProposalPurpose;
use App\Model\Enum\DocumentsDeliveryType;
use App\Service\CustomerPrint\CustomerDocuments;
use Cake\Database\Type\EnumType;
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Override;

/**
 * CustomerProposals Model
 *
 * @property \App\Model\Table\CustomersTable&\Cake\ORM\Association\BelongsTo $Customers
 * @property \App\Model\Table\ContractProposalsTable&\Cake\ORM\Association\HasMany $ContractProposals
 * @method \App\Model\Entity\CustomerProposal newEmptyEntity()
 * @method \App\Model\Entity\CustomerProposal newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\CustomerProposal> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\CustomerProposal get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\CustomerProposal findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\CustomerProposal patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\CustomerProposal> patchEntities(iterable $entities, array $options = [])
 * @method \App\Model\Entity\CustomerProposal|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\CustomerProposal saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\CustomerProposal>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\CustomerProposal>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\CustomerProposal>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\CustomerProposal> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\CustomerProposal>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\CustomerProposal>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\CustomerProposal>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\CustomerProposal> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class CustomerProposalsTable extends AppTable
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

        $this->setTable('customer_proposals');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->getSchema()->setColumnType(
            'purpose',
            EnumType::from(CustomerProposalPurpose::class),
        );
        $this->getSchema()->setColumnType(
            'delivery_type',
            EnumType::from(DocumentsDeliveryType::class),
        );

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');

        $this->belongsTo('Customers', [
            'foreignKey' => 'customer_id',
            'joinType' => 'INNER',
        ]);
        // The papers of the customer's contracts that go out in this envelope. They are never
        // taken with it: the envelope is where their sending and their signature are kept, so
        // letting it go while they are in it would leave them saying nothing about either - which
        // is why a rule stops it happening at all.
        $this->hasMany('ContractProposals', [
            'foreignKey' => 'customer_proposal_id',
            'dependent' => false,
        ]);
    }

    /**
     * Rounds nobody has settled yet, one way or the other.
     *
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\CustomerProposal> $query Base query.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\CustomerProposal>
     */
    public function findOpen(SelectQuery $query): SelectQuery
    {
        return $query->where([
            $this->aliasField('conclusion_date') . ' IS' => null,
            $this->aliasField('revoked') . ' IS' => null,
        ]);
    }

    /**
     * Gives up on the round, and on everything still standing in it.
     *
     * Written into the papers rather than read back from here afterwards. Giving up is final and
     * nothing joins a round that has been given up on, so the one word written across them cannot
     * come apart the way a copied date would - and the papers go on answering for themselves
     * wherever they are read without their envelope.
     *
     * What was already applied keeps what it says: an envelope does not undo what has reached
     * the live records. So does anything given up on earlier, which has its own day and its own
     * name against it.
     *
     * @param \App\Model\Entity\CustomerProposal $proposal The round.
     * @param string|null $by Who is giving up on it.
     * @return bool Whether it was written.
     */
    public function giveUpOnTheRound(CustomerProposal $proposal, ?string $by): bool
    {
        $proposal->revoked = DateTime::now();
        $proposal->revoked_by = $by;

        return (bool)$this->getConnection()->transactional(function () use ($proposal): bool {
            if (!$this->save($proposal, ['checkRules' => false])) {
                return false;
            }

            $standing = $this->ContractProposals->find()->where([
                'ContractProposals.customer_proposal_id' => $proposal->id,
                'ContractProposals.revoked IS' => null,
                'ContractProposals.applied IS' => null,
            ]);

            foreach ($standing as $papers) {
                $papers->revoked = $proposal->revoked;
                $papers->revoked_by = $proposal->revoked_by;

                if (!$this->ContractProposals->save($papers, ['checkRules' => false])) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * Whether the round may still be changed.
     *
     * Sending is what locks it, the same as it does for a contract's proposal: what stood behind a
     * paper that has left the building is not rewritten afterwards. A correction is a new round,
     * and the old one is revoked.
     *
     * @param \App\Model\Entity\CustomerProposal $proposal The round being asked about.
     * @return bool
     */
    public function mayBeEdited(CustomerProposal $proposal): bool
    {
        return $proposal->isOpen() && !$proposal->hasBeenSent();
    }

    /**
     * Whether the round may be removed.
     *
     * What went out to a customer is what happened, and what came back signed is theirs; neither
     * is ours to remove. A round that never went anywhere is somebody's mistake rather than
     * history, so that one may go.
     *
     * @param \App\Model\Entity\CustomerProposal $proposal The round being asked about.
     * @return bool
     */
    public function mayBeDeleted(CustomerProposal $proposal): bool
    {
        return !$proposal->hasBeenSent()
            && !$proposal->hasBeenConcluded()
            && !$this->anythingHangsOn($proposal);
    }

    /**
     * Whether anything would be left behind by letting the round go.
     *
     * Two things may: the papers of a contract, which keep their sending and their signature here
     * and would have nowhere to keep them; and a document, which is the record of something that
     * happened and would be left pointing at nothing, holding on to its file for ever.
     *
     * @param \App\Model\Entity\CustomerProposal $proposal The round being asked about.
     * @return bool
     */
    private function anythingHangsOn(CustomerProposal $proposal): bool
    {
        if ($this->ContractProposals->exists(['ContractProposals.customer_proposal_id' => $proposal->id])) {
            return true;
        }

        return TableRegistry::getTableLocator()->get('Files.FileLinks')->exists([
            'FileLinks.model' => CustomerDocuments::MODEL,
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
            ->uuid('customer_id')
            ->requirePresence('customer_id', 'create')
            ->notEmptyString('customer_id');

        // Empty says the round carries no paper of the customer's own and is only the envelope
        // its contracts' papers went out in.
        $validator
            ->allowEmptyString('purpose');

        $validator
            ->date('effective_from')
            ->requirePresence('effective_from', 'create')
            ->notEmptyDate('effective_from');

        $validator
            ->date('sent_date')
            ->allowEmptyDate('sent_date');

        $validator
            ->allowEmptyString('delivery_type');

        $validator
            ->date('conclusion_date')
            ->allowEmptyDate('conclusion_date');

        $validator
            ->dateTime('revoked')
            ->allowEmptyDateTime('revoked');

        $validator
            ->scalar('note')
            ->allowEmptyString('note');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    #[Override]
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['customer_id'], 'Customers'), ['errorField' => 'customer_id']);

        // The day and the way belong together: a record saying papers went out without saying how
        // is half an answer, and the listing beside it would print half a line.
        $rules->add(
            fn(CustomerProposal $proposal): bool => $proposal->sent_date === null
                || $proposal->delivery_type !== null,
            'sendingSaysHow',
            [
                'errorField' => 'delivery_type',
                'message' => __('Please say how the proposal was sent.'),
            ],
        );

        $rules->addDelete(
            fn(CustomerProposal $proposal): bool => $this->mayBeDeleted($proposal),
            'nothingIsLeftBehind',
            [
                'errorField' => 'id',
                'message' => __(
                    'This customer proposal has been sent, or there are contract proposals in'
                    . ' it. Revoke it instead.',
                ),
            ],
        );

        return $rules;
    }
}
