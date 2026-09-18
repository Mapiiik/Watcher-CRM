<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Contracts\Proposal\ProposalConfirmations;
use App\Model\Entity\ContractProposal;
use App\Model\Enum\DocumentsDeliveryType;
use App\Model\Enum\DocumentVariant;
use App\Model\Enum\ProposalPurpose;
use App\Model\Table\ContractProposalsTable;
use App\Service\ContractPrint\ContractDocuments;
use App\Test\Traits\TableTestTrait;
use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use Files\Service\FileStorage;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;

/**
 * App\Model\Table\ContractProposalsTable Test Case
 */
#[CoversClass(ContractProposalsTable::class)]
class ContractProposalsTableTest extends TestCase
{
    use TableTestTrait;

    /**
     * The contract the fixtures hang everything off.
     */
    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

    /**
     * A second contract, for asking what happens across the boundary.
     */
    private const OTHER_CONTRACT_ID = '9c0d5e5c-2a6b-4f8e-9a3d-1b7c4e2f6a90';

    /**
     * A concluded version, valid until a given day, with its obligation on the same day.
     */
    private const VERSION_ID = '74824fba-20b2-46fc-806c-df795aa9e429';

    /**
     * The proposal the fixture carries: open, unsent, changing nothing.
     */
    private const PROPOSAL_ID = 'c9a1f2b3-4d5e-4f60-8a71-9b2c3d4e5f60';

    /**
     * The proposal that proposal is a part of.
     */
    private const ROUND_ID = 'a7c1d5e2-3f48-4b90-9c61-2d0e7a5b8f34';

    /**
     * Whose papers all of these are.
     */
    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    /**
     * A billing the fixture snapshot knows about.
     */
    private const KNOWN_BILLING_ID = 'b2000000-0000-4000-8000-000000000002';

    /**
     * Test subject
     *
     * @var \App\Model\Table\ContractProposalsTable
     */
    protected $Proposals;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.AppUsers',
        'app.AccountingProfiles',
        'app.Customers',
        'app.Countries',
        'app.Addresses',
        'app.Commissions',
        'app.ContractStates',
        'app.ServiceTypes',
        'app.Contracts',
        'app.ContractVersions',
        'app.Queues',
        'app.Services',
        'app.Billings',
        'app.CustomerProposals',
        'app.ContractProposals',
        'plugin.Files.Files',
        'plugin.Files.FileLinks',
        'plugin.Settings.Settings',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('ContractProposals')
            ? []
            : ['className' => ContractProposalsTable::class];
        $this->Proposals = $this->getTableLocator()->get('ContractProposals', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        /** @phpstan-ignore unset.possiblyHookedProperty */
        unset($this->Proposals);

        parent::tearDown();
    }

    /**
     * A contract version put there by the test rather than by a fixture.
     *
     * The unsigned paperwork and the contract checks read every version on file, so an unsigned or
     * open-ended one sitting in the fixtures would answer questions other tests are asking.
     *
     * @param array<string, mixed> $version What the version says.
     * @return string Its id.
     */
    private function aVersion(array $version = []): string
    {
        $versions = $this->getTableLocator()->get('ContractVersions');

        $entity = $versions->newEntity($version + [
            'contract_id' => self::CONTRACT_ID,
            'valid_from' => '2026-01-01',
            'valid_until' => null,
            'obligation_until' => null,
            'obligations_settled' => false,
            'conclusion_date' => '2026-01-01',
            'number_of_amendments' => 0,
        ]);

        $versions->saveOrFail($entity);

        return (string)$entity->id;
    }

    /**
     * What a proposal needs to be saved at all, before the test says what it is really about.
     *
     * @param array<string, mixed> $proposal What this proposal says.
     * @return array<string, mixed>
     */
    private function proposalData(array $proposal = []): array
    {
        return $proposal + [
            'contract_id' => self::CONTRACT_ID,
            'contract_version_id' => $this->aVersion(),
            'purpose' => ProposalPurpose::ServiceChange->value,
            'effective_from' => '2026-10-01',
            'snapshot' => $this->aSnapshot(),
            'snapshot_taken' => DateTime::now(),
            'changes' => [],
            'confirmations' => [],
        ];
    }

    /**
     * The least a snapshot may say and still be one the documents could print from.
     *
     * @return array<string, mixed>
     */
    private function aSnapshot(): array
    {
        return [
            'contract' => ['id' => self::CONTRACT_ID, 'number' => '2022/0001'],
            'customer' => ['nid' => 1, 'addresses' => [], 'emails' => [], 'phones' => []],
            'version' => ['id' => self::VERSION_ID],
            'billings' => [
                ['id' => self::KNOWN_BILLING_ID, 'billing_from' => '2022-01-01'],
            ],
        ];
    }

    /**
     * Saves a proposal and says what the table made of it.
     *
     * @param array<string, mixed> $proposal What this proposal says.
     * @return \App\Model\Entity\ContractProposal
     */
    private function save(array $proposal = []): ContractProposal
    {
        // The sending and the signature belong to the round the papers go out in, so a test that
        // says a proposal has gone out is saying it of the envelope. Written in the order the
        // office works in: papers into an open envelope, the envelope out, and only then may what
        // it holds be applied.
        $ofTheRound = array_intersect_key(
            $proposal,
            array_flip(['sent_date', 'delivery_type', 'conclusion_date']),
        );
        $afterwards = array_intersect_key($proposal, array_flip(['applied', 'applied_by']));

        $round = $proposal['customer_proposal_id'] ?? $this->anEnvelope();

        $entity = $this->Proposals->newEntity($this->proposalData(
            array_diff_key($proposal, $ofTheRound + $afterwards)
            + ['customer_proposal_id' => $round],
        ));
        $this->Proposals->save($entity);

        if ($ofTheRound !== []) {
            $envelopes = $this->getTableLocator()->get('CustomerProposals');
            $envelopes->saveOrFail(
                $envelopes->patchEntity($envelopes->get($round), $ofTheRound),
                ['checkRules' => false],
            );
        }

        if ($afterwards !== [] && $entity->getErrors() === []) {
            $this->Proposals->save($this->Proposals->patchEntity($entity, $afterwards));
        }

        return $entity;
    }

    /**
     * An envelope of its own, put there by the test rather than by a fixture.
     *
     * One apiece, because a round holds one set of papers for a contract and every proposal these
     * tests draw up is for the same one.
     *
     * @return string Its id.
     */
    private function anEnvelope(): string
    {
        $envelopes = $this->getTableLocator()->get('CustomerProposals');

        $entity = $envelopes->newEntity([
            'customer_id' => self::CUSTOMER_ID,
            'effective_from' => '2026-10-01',
        ]);

        $envelopes->saveOrFail($entity, ['checkRules' => false]);

        return (string)$entity->id;
    }

    /**
     * Read without the round it goes out in, a set of papers says so rather than answering that
     * nothing has gone out - which would be a different thing from not knowing.
     *
     * @return void
     */
    public function testPapersReadWithoutTheirRoundSaySo(): void
    {
        $papers = $this->Proposals->get(self::PROPOSAL_ID);

        $this->expectException(RuntimeException::class);
        $papers->hasBeenSent();
    }

    /**
     * And read with it, they answer what it says.
     *
     * @return void
     */
    public function testPapersAnswerWithWhatTheirRoundSays(): void
    {
        $envelopes = $this->getTableLocator()->get('CustomerProposals');
        $envelopes->saveOrFail(
            $envelopes->patchEntity($envelopes->get(self::ROUND_ID), [
                'sent_date' => '2026-10-01',
                'delivery_type' => DocumentsDeliveryType::Email,
                'conclusion_date' => '2026-10-05',
            ]),
            ['checkRules' => false],
        );

        $papers = $this->Proposals->get(self::PROPOSAL_ID, contain: ['CustomerProposals']);

        $this->assertSame('2026-10-01', $papers->sent_date?->toDateString());
        $this->assertSame(DocumentsDeliveryType::Email, $papers->delivery_type);
        $this->assertSame('2026-10-05', $papers->conclusion_date?->toDateString());
        $this->assertTrue($papers->hasBeenSent());
        $this->assertTrue($papers->hasBeenConcluded());
    }

    /**
     * Papers are put into a round that is still open. One that has gone out is not added to - the
     * papers would otherwise read as having gone out with it when they were never in it.
     *
     * @return void
     */
    public function testPapersDoNotJoinARoundThatHasGoneOut(): void
    {
        $envelopes = $this->getTableLocator()->get('CustomerProposals');
        $round = $this->anEnvelope();
        $envelopes->saveOrFail(
            $envelopes->patchEntity($envelopes->get($round), [
                'sent_date' => '2026-10-01',
                'delivery_type' => DocumentsDeliveryType::Email,
            ]),
            ['checkRules' => false],
        );

        $papers = $this->save(['customer_proposal_id' => $round]);

        $this->assertArrayHasKey('customer_proposal_id', $papers->getErrors());
    }

    /**
     * Papers with a document filed against them are not removed: it is the record of something
     * that happened, and it would be left pointing at nothing.
     *
     * @return void
     */
    public function testPapersWithSomethingFiledAgainstThemAreNotRemoved(): void
    {
        $papers = $this->Proposals->get(self::PROPOSAL_ID, contain: ['CustomerProposals']);
        $this->assertTrue($this->Proposals->mayBeDeleted($papers));

        $root = TMP . 'papers-filed-' . uniqid();
        Configure::write('Files.root', $root);

        try {
            $storage = new FileStorage();
            $storage->link(
                $storage->store('%PDF-1.7 drawn', 'application/pdf'),
                ContractDocuments::MODEL,
                self::PROPOSAL_ID,
                'contract-new',
                DocumentVariant::Generated->value,
                ['name' => 'contract.pdf'],
            );
        } finally {
            Configure::delete('Files.root');
        }

        $this->assertFalse($this->Proposals->mayBeDeleted($papers));
    }

    /**
     * A proposal that says nothing beyond where it belongs is saved as it stands.
     *
     * @return void
     */
    public function testAProposalThatChangesNothingIsSaved(): void
    {
        $proposal = $this->save();

        $this->assertEmpty($proposal->getErrors());
        $this->assertNotEmpty($proposal->id);
        $this->assertTrue($proposal->proposedChanges()->isEmpty());
    }

    /**
     * The version a proposal belongs to has to be on the contract the proposal names.
     *
     * @return void
     */
    public function testAProposalCannotReachOntoAnotherContract(): void
    {
        $proposal = $this->save(['contract_id' => self::OTHER_CONTRACT_ID]);

        $this->assertArrayHasKey('contract_version_id', $proposal->getErrors());
    }

    /**
     * An envelope holds one set of papers for a contract. Both would go out in the same letter and
     * come back on the same day, so which of the two was agreed to would be nobody's to say.
     *
     * @return void
     */
    public function testAContractGetsOneSetOfPapersInARound(): void
    {
        $second = $this->save(['customer_proposal_id' => self::ROUND_ID]);

        $this->assertArrayHasKey('customer_proposal_id', $second->getErrors());
    }

    /**
     * Another contract in the same envelope is what an envelope is for.
     *
     * @return void
     */
    public function testAnotherContractShareTheSameRound(): void
    {
        $papers = $this->save([
            'customer_proposal_id' => self::ROUND_ID,
            'contract_id' => self::OTHER_CONTRACT_ID,
            'contract_version_id' => $this->aVersion(['contract_id' => self::OTHER_CONTRACT_ID]),
        ]);

        $this->assertEmpty($papers->getErrors());
    }

    /**
     * Papers given up on are not in the letter, so they leave the way clear for the ones drawn up
     * to replace them.
     *
     * @return void
     */
    public function testPapersGivenUpOnLeaveTheirPlaceInTheRound(): void
    {
        $this->Proposals->updateAll(['revoked' => DateTime::now()], ['id' => self::PROPOSAL_ID]);

        $again = $this->save(['customer_proposal_id' => self::ROUND_ID]);

        $this->assertEmpty($again->getErrors());
    }

    /**
     * And so does the version it terminates - the shorthand covers one contract replacing its own
     * version, not a version belonging to somebody else's contract.
     *
     * @return void
     */
    public function testATerminatedVersionCannotBeOnAnotherContract(): void
    {
        $elsewhere = $this->aVersion(['contract_id' => self::OTHER_CONTRACT_ID]);

        $proposal = $this->save([
            'terminates_contract_version_id' => $elsewhere,
            'terminated_contract_number' => 'The other contract',
        ]);

        $this->assertArrayHasKey('terminates_contract_version_id', $proposal->getErrors());
    }

    /**
     * A version cannot replace itself.
     *
     * @return void
     */
    public function testAVersionCannotTerminateItself(): void
    {
        $version = $this->aVersion();

        $proposal = $this->save([
            'contract_version_id' => $version,
            'terminates_contract_version_id' => $version,
            'terminated_contract_number' => 'Lorem ipsum dolor sit amet',
        ]);

        $this->assertArrayHasKey('terminates_contract_version_id', $proposal->getErrors());
    }

    /**
     * What was never concluded was never in force, so there is nothing to terminate.
     *
     * @return void
     */
    public function testAnUnconcludedVersionCannotBeTerminated(): void
    {
        $unconcluded = $this->aVersion(['conclusion_date' => null]);

        $proposal = $this->save([
            'terminates_contract_version_id' => $unconcluded,
            'terminated_contract_number' => 'Lorem ipsum dolor sit amet',
        ]);

        $this->assertArrayHasKey('terminates_contract_version_id', $proposal->getErrors());
    }

    /**
     * The same when a proposal ends the version it belongs to.
     *
     * @return void
     */
    public function testAnUnconcludedVersionCannotBeEnded(): void
    {
        $proposal = $this->save([
            'purpose' => ProposalPurpose::Termination->value,
            'contract_version_id' => $this->aVersion(['conclusion_date' => null]),
            'terminated_contract_number' => 'Lorem ipsum dolor sit amet',
            'changes' => [
                'version' => ['valid_until' => '2026-12-31'],
                'contract' => ['termination_date' => '2026-12-31'],
            ],
        ]);

        $this->assertArrayHasKey('changes', $proposal->getErrors());
    }

    /**
     * A version may end while the contract runs on: that is how an agreement to end one version
     * and sign another is written, instead of the single paper that does both at once.
     *
     * @return void
     */
    public function testAVersionMayEndWhileTheContractRunsOn(): void
    {
        $proposal = $this->save([
            'purpose' => ProposalPurpose::Termination->value,
            'terminated_contract_number' => 'Lorem ipsum dolor sit amet',
            'changes' => ['version' => ['valid_until' => '2026-12-31']],
        ]);

        $this->assertEmpty($proposal->getErrors());
    }

    /**
     * The other way round is not an act: a contract ends on the day its version stops being
     * valid, so ending one without the other says nothing about when the service stops.
     *
     * @return void
     */
    public function testEndingTheContractAloneIsRefused(): void
    {
        $proposal = $this->save([
            'purpose' => ProposalPurpose::Termination->value,
            'terminated_contract_number' => 'Lorem ipsum dolor sit amet',
            'changes' => ['contract' => ['termination_date' => '2026-12-31']],
        ]);

        $this->assertArrayHasKey('changes', $proposal->getErrors());
    }

    /**
     * And where both are said, they have to be the same day, or the paper ends on one and the
     * invoicing on another.
     *
     * @return void
     */
    public function testTheTwoEndingDatesHaveToAgree(): void
    {
        $proposal = $this->save([
            'purpose' => ProposalPurpose::Termination->value,
            'terminated_contract_number' => 'Lorem ipsum dolor sit amet',
            'changes' => [
                'version' => ['valid_until' => '2026-12-31'],
                'contract' => ['termination_date' => '2027-01-31'],
            ],
        ]);

        $this->assertArrayHasKey('changes', $proposal->getErrors());
    }

    /**
     * The number of the contract being terminated goes on the paper, so a proposal that ends
     * something has to carry it. It used to be typed in at every printing and thrown away.
     *
     * @return void
     */
    public function testEndingWithoutTheTerminatedNumberIsRefused(): void
    {
        $ending = [
            'purpose' => ProposalPurpose::Termination->value,
            'changes' => [
                'version' => ['valid_until' => '2026-12-31'],
                'contract' => ['termination_date' => '2026-12-31'],
            ],
        ];

        $without = $this->save($ending);
        $this->assertArrayHasKey('terminated_contract_number', $without->getErrors());

        $with = $this->save($ending + ['terminated_contract_number' => 'Lorem ipsum dolor sit amet']);
        $this->assertEmpty($with->getErrors());
    }

    /**
     * An end date on a version is also how a superseded one is recorded, so printing it as a
     * fixed-term contract has to be said out loud - and a fixed term is its own minimum period of
     * performance, so the obligation has to reach the end of it.
     *
     * @return void
     */
    public function testAFixedTermHasToBeAcknowledgedAndMatchTheObligation(): void
    {
        $fixedTerm = [
            'purpose' => ProposalPurpose::NewContract->value,
            'changes' => [
                'version' => ['valid_until' => '2026-12-31', 'obligation_until' => '2026-12-31'],
            ],
        ];

        $unacknowledged = $this->save($fixedTerm);
        $this->assertArrayHasKey('confirmations', $unacknowledged->getErrors());

        $mismatched = $this->save([
            'purpose' => ProposalPurpose::NewContract->value,
            'changes' => [
                'version' => ['valid_until' => '2026-12-31', 'obligation_until' => '2027-06-30'],
            ],
            'confirmations' => [ProposalConfirmations::FIXED_TERM => true],
        ]);
        $this->assertArrayHasKey('confirmations', $mismatched->getErrors());

        $agreed = $this->save($fixedTerm + [
            'confirmations' => [ProposalConfirmations::FIXED_TERM => true],
        ]);
        $this->assertEmpty($agreed->getErrors());
    }

    /**
     * An ending is never asked to confirm a fixed term, and never has its obligation moved.
     *
     * The two look alike from the outside - both put an end date on a version - and only the
     * purpose tells them apart. Asked of an ending, the rule would have the obligation set to the
     * day the customer left, and with it would go the fact that they left before it ran out, which
     * is what says whether anything is still owed.
     *
     * @return void
     */
    public function testAnEndingIsNotHeldToTheFixedTermRule(): void
    {
        $proposal = $this->save([
            'purpose' => ProposalPurpose::Termination->value,
            'terminated_contract_number' => 'Lorem ipsum dolor sit amet',
            'changes' => [
                'version' => ['valid_until' => '2026-12-31'],
                'contract' => ['termination_date' => '2026-12-31'],
            ],
        ]);

        $this->assertEmpty($proposal->getErrors());
    }

    /**
     * The purpose is a stored answer, so nothing may be filed under one purpose while asking for
     * what another one does - the form cannot, but a portal writing over the API could.
     *
     * @return void
     */
    public function testTheChangesHaveToMatchThePurpose(): void
    {
        $endingUnderAChange = $this->save([
            'purpose' => ProposalPurpose::ServiceChange->value,
            'terminated_contract_number' => 'Lorem ipsum dolor sit amet',
            'changes' => [
                'version' => ['valid_until' => '2026-12-31'],
                'contract' => ['termination_date' => '2026-12-31'],
            ],
            'confirmations' => [ProposalConfirmations::FIXED_TERM => true],
        ]);
        $this->assertArrayHasKey('changes', $endingUnderAChange->getErrors());

        $endingThatEndsNothing = $this->save([
            'purpose' => ProposalPurpose::Termination->value,
            'terminated_contract_number' => 'Lorem ipsum dolor sit amet',
        ]);
        $this->assertArrayHasKey('changes', $endingThatEndsNothing->getErrors());
    }

    /**
     * A line has to act on a billing the snapshot knows, or there is nothing to say what it
     * replaces and nothing to hold the live record up against before applying it.
     *
     * @return void
     */
    public function testALineCannotActOnABillingTheSnapshotDoesNotKnow(): void
    {
        $proposal = $this->save([
            'changes' => [
                'billings' => [
                    ['billing_id' => 'b1000000-0000-4000-8000-000000000001', 'terminates_only' => true],
                ],
            ],
        ]);

        $this->assertArrayHasKey('changes', $proposal->getErrors());
    }

    /**
     * Two lines on one billing would end it twice and start two replacements at once.
     *
     * @return void
     */
    public function testTwoLinesCannotActOnTheSameBilling(): void
    {
        $proposal = $this->save([
            'changes' => [
                'billings' => [
                    ['billing_id' => self::KNOWN_BILLING_ID, 'terminates_only' => true],
                    ['billing_id' => self::KNOWN_BILLING_ID, 'service_id' => 'eaacfeb3-1430-43ce-842e-497c5c95d953'],
                ],
            ],
        ]);

        $this->assertArrayHasKey('changes', $proposal->getErrors());
    }

    /**
     * A change is an amendment, and there is nothing to amend before the contract is signed. A new
     * contract on the same version is not a change, and a change already being worked on is not
     * locked by the rule.
     *
     * @return void
     */
    public function testAChangeAmendsOnlyASignedVersion(): void
    {
        $unsigned = $this->aVersion(['conclusion_date' => null]);

        $refused = $this->save(['contract_version_id' => $unsigned]);
        $this->assertArrayHasKey(
            'aChangeAmendsASignedVersion',
            $refused->getErrors()['contract_version_id'] ?? [],
        );

        $newContract = $this->save([
            'contract_version_id' => $unsigned,
            'purpose' => ProposalPurpose::NewContract->value,
        ]);
        $this->assertArrayNotHasKey(
            'aChangeAmendsASignedVersion',
            $newContract->getErrors()['contract_version_id'] ?? [],
        );

        $standing = $this->Proposals->newEntity($this->proposalData([
            'contract_version_id' => $unsigned,
            'customer_proposal_id' => self::ROUND_ID,
        ]));
        $this->Proposals->saveOrFail($standing, ['checkRules' => false]);
        $standing = $this->Proposals->patchEntity($this->Proposals->get($standing->id), ['note' => 'Still worked on']);
        $this->Proposals->save($standing);

        $this->assertArrayNotHasKey(
            'aChangeAmendsASignedVersion',
            $standing->getErrors()['contract_version_id'] ?? [],
        );
    }

    /**
     * A shape the value objects will not answer for never reaches the column, because the only
     * place it would surface is printing.
     *
     * @return void
     */
    public function testAShapeNobodyCanReadIsRefused(): void
    {
        $badChanges = $this->save(['changes' => ['equipment' => []]]);
        $this->assertArrayHasKey('changes', $badChanges->getErrors());

        $badSnapshot = $this->save(['snapshot' => ['contract' => []]]);
        $this->assertArrayHasKey('snapshot', $badSnapshot->getErrors());

        $badAnswers = $this->save(['confirmations' => ['has_a_ladder' => true]]);
        $this->assertArrayHasKey('confirmations', $badAnswers->getErrors());
    }

    /**
     * Once the papers have gone out, what stood behind them is settled - but recording that they
     * went again, or came back signed, is not rewriting it.
     *
     * @return void
     */
    public function testASentProposalIsNotRewritten(): void
    {
        $proposal = $this->save([
            'sent_date' => '2026-10-01',
            'delivery_type' => DocumentsDeliveryType::Email,
        ]);
        $this->assertEmpty($proposal->getErrors());

        $rewritten = $this->Proposals->patchEntity($proposal, [
            'changes' => ['billings' => [['billing_id' => self::KNOWN_BILLING_ID, 'terminates_only' => true]]],
        ]);
        $this->Proposals->save($rewritten);
        $this->assertArrayHasKey('changes', $rewritten->getErrors());

        $proposal = $this->Proposals->get($proposal->id);
        $signed = $this->Proposals->patchEntity($proposal, ['conclusion_date' => '2026-10-05']);
        $this->assertNotFalse($this->Proposals->save($signed));
        $this->assertEmpty($signed->getErrors());
    }

    /**
     * Applying the changes checks before it writes, but the last word is here, so that no other way in can
     * apply a proposal that nobody has agreed to.
     *
     * @return void
     */
    public function testNothingIsAppliedBeforeItIsConcluded(): void
    {
        $proposal = $this->save();

        $applied = $this->Proposals->patchEntity($proposal, ['applied' => DateTime::now()]);
        $this->Proposals->save($applied);
        $this->assertArrayHasKey('applied', $applied->getErrors());

        // The signature is the envelope's, and it is what opens the way.
        $envelopes = $this->getTableLocator()->get('CustomerProposals');
        $envelopes->saveOrFail(
            $envelopes->patchEntity(
                $envelopes->get($proposal->customer_proposal_id),
                ['conclusion_date' => '2026-10-05'],
            ),
            ['checkRules' => false],
        );

        $proposal = $this->Proposals->get($proposal->id);
        $carried = $this->Proposals->patchEntity($proposal, ['applied' => DateTime::now()]);
        $this->assertNotFalse($this->Proposals->save($carried));
        $this->assertEmpty($carried->getErrors());
    }

    /**
     * What happened and what was given up on are not both true of the same proposal.
     *
     * @return void
     */
    public function testAProposalIsEitherAppliedOrGivenUpOn(): void
    {
        $proposal = $this->save(['conclusion_date' => '2026-10-05']);

        $both = $this->Proposals->patchEntity($proposal, [
            'applied' => DateTime::now(),
            'revoked' => DateTime::now(),
        ]);
        $this->Proposals->save($both);

        $this->assertArrayHasKey('revoked', $both->getErrors());
    }

    /**
     * Sending locks the proposal; applying it or giving up on it settles it.
     *
     * @return void
     */
    public function testWhatMayStillBeChangedAndWhatMayBeTakenBack(): void
    {
        $open = $this->Proposals->get(self::PROPOSAL_ID);
        $this->assertTrue($this->Proposals->mayBeEdited($open));
        $this->assertTrue($this->Proposals->mayBeDeleted($open));

        // The envelope going out is what settles what is inside it.
        $envelopes = $this->getTableLocator()->get('CustomerProposals');
        $envelopes->saveOrFail(
            $envelopes->patchEntity($envelopes->get(self::ROUND_ID), [
                'sent_date' => '2026-10-01',
                'delivery_type' => DocumentsDeliveryType::Email,
            ]),
            ['checkRules' => false],
        );

        $sent = $this->Proposals->get(self::PROPOSAL_ID);
        $this->assertFalse($this->Proposals->mayBeEdited($sent));
        $this->assertFalse($this->Proposals->mayBeDeleted($sent));
    }

    /**
     * A proposal that went nowhere and was given up on is somebody's mistake rather than history,
     * so it may be removed - but not edited into something else.
     *
     * @return void
     */
    public function testARevokedProposalMayGoButNotBeChanged(): void
    {
        $proposal = $this->save();
        $revoked = $this->Proposals->patchEntity($proposal, ['revoked' => DateTime::now()]);
        $this->Proposals->saveOrFail($revoked);

        $this->assertFalse($this->Proposals->mayBeEdited($revoked));
        $this->assertTrue($this->Proposals->mayBeDeleted($revoked));
    }

    /**
     * The finders answer what the checks and the printing offer ask for.
     *
     * @return void
     */
    public function testTheFindersSeparateWhatIsStillWaiting(): void
    {
        $waiting = $this->save(['conclusion_date' => '2026-10-05']);
        $done = $this->save(['conclusion_date' => '2026-10-05', 'applied' => DateTime::now()]);

        $open = $this->Proposals->find('open')->all()->extract('id')->toArray();
        $this->assertContains($waiting->id, $open);
        $this->assertNotContains($done->id, $open);
        $this->assertContains(self::PROPOSAL_ID, $open);

        $pending = $this->Proposals->find('waitingToBeApplied')->all()->extract('id')->toArray();
        $this->assertContains($waiting->id, $pending);
        $this->assertNotContains(self::PROPOSAL_ID, $pending);
    }
}
