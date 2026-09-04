<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Contracts\Proposal\ProposalAcknowledgements;
use App\Model\Entity\ContractVersionProposal;
use App\Model\Enum\ContractDeliveryMethod;
use App\Model\Table\ContractVersionProposalsTable;
use App\Test\Traits\TableTestTrait;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\Model\Table\ContractVersionProposalsTable Test Case
 */
#[CoversClass(ContractVersionProposalsTable::class)]
class ContractVersionProposalsTableTest extends TestCase
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
     * A billing the fixture snapshot knows about.
     */
    private const KNOWN_BILLING_ID = 'b2000000-0000-4000-8000-000000000002';

    /**
     * Test subject
     *
     * @var \App\Model\Table\ContractVersionProposalsTable
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
        'app.ContractVersionProposals',
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
        $config = $this->getTableLocator()->exists('ContractVersionProposals')
            ? []
            : ['className' => ContractVersionProposalsTable::class];
        $this->Proposals = $this->getTableLocator()->get('ContractVersionProposals', $config);
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
            'effective_from' => '2026-10-01',
            'snapshot' => $this->aSnapshot(),
            'snapshot_taken' => DateTime::now(),
            'changes' => [],
            'acknowledgements' => [],
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
     * @return \App\Model\Entity\ContractVersionProposal
     */
    private function save(array $proposal = []): ContractVersionProposal
    {
        $entity = $this->Proposals->newEntity($this->proposalData($proposal));
        $this->Proposals->save($entity);

        return $entity;
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
            'contract_version_id' => $this->aVersion(['conclusion_date' => null]),
            'terminated_contract_number' => 'Lorem ipsum dolor sit amet',
            'changes' => [
                'version' => ['valid_until' => '2026-12-31'],
                'contract' => ['termination_date' => '2026-12-31'],
            ],
            'acknowledgements' => [ProposalAcknowledgements::FIXED_TERM => true],
        ]);

        $this->assertArrayHasKey('changes', $proposal->getErrors());
    }

    /**
     * Ending is one act written in two places, and half of it written alone is not an act.
     *
     * @return void
     */
    public function testEndingTheVersionAloneIsRefused(): void
    {
        $proposal = $this->save([
            'terminated_contract_number' => 'Lorem ipsum dolor sit amet',
            'changes' => ['version' => ['valid_until' => '2026-12-31']],
            'acknowledgements' => [ProposalAcknowledgements::FIXED_TERM => true],
        ]);

        $this->assertArrayHasKey('changes', $proposal->getErrors());
    }

    /**
     * And the two days have to be the same day, or the paper ends on one and the invoicing on
     * another.
     *
     * @return void
     */
    public function testTheTwoEndingDatesHaveToAgree(): void
    {
        $proposal = $this->save([
            'terminated_contract_number' => 'Lorem ipsum dolor sit amet',
            'changes' => [
                'version' => ['valid_until' => '2026-12-31'],
                'contract' => ['termination_date' => '2027-01-31'],
            ],
            'acknowledgements' => [ProposalAcknowledgements::FIXED_TERM => true],
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
            'changes' => [
                'version' => ['valid_until' => '2026-12-31', 'obligation_until' => '2026-12-31'],
                'contract' => ['termination_date' => '2026-12-31'],
            ],
            'acknowledgements' => [ProposalAcknowledgements::FIXED_TERM => true],
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
        $ending = [
            'terminated_contract_number' => 'Lorem ipsum dolor sit amet',
            'changes' => [
                'version' => ['valid_until' => '2026-12-31', 'obligation_until' => '2026-12-31'],
                'contract' => ['termination_date' => '2026-12-31'],
            ],
        ];

        $unacknowledged = $this->save($ending);
        $this->assertArrayHasKey('acknowledgements', $unacknowledged->getErrors());

        $mismatched = $this->save([
            'terminated_contract_number' => 'Lorem ipsum dolor sit amet',
            'changes' => [
                'version' => ['valid_until' => '2026-12-31', 'obligation_until' => '2027-06-30'],
                'contract' => ['termination_date' => '2026-12-31'],
            ],
            'acknowledgements' => [ProposalAcknowledgements::FIXED_TERM => true],
        ]);
        $this->assertArrayHasKey('acknowledgements', $mismatched->getErrors());

        $agreed = $this->save($ending + [
            'acknowledgements' => [ProposalAcknowledgements::FIXED_TERM => true],
        ]);
        $this->assertEmpty($agreed->getErrors());
    }

    /**
     * A line has to act on a billing the snapshot knows, or there is nothing to say what it
     * replaces and nothing to hold the live record up against before carrying it over.
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

        $badAnswers = $this->save(['acknowledgements' => ['has_a_ladder' => true]]);
        $this->assertArrayHasKey('acknowledgements', $badAnswers->getErrors());
    }

    /**
     * A way with no day does not say when, and a day with no way does not say how it could be
     * shown; either on its own reads later as a record when it is half of one.
     *
     * @return void
     */
    public function testSendingIsRecordedWhole(): void
    {
        $dayOnly = $this->save(['sent_date' => '2026-10-01']);
        $this->assertArrayHasKey('sent_by', $dayOnly->getErrors());

        $wayOnly = $this->save(['sent_by' => ContractDeliveryMethod::Email]);
        $this->assertArrayHasKey('sent_by', $wayOnly->getErrors());

        $both = $this->save([
            'sent_date' => '2026-10-01',
            'sent_by' => ContractDeliveryMethod::Email,
        ]);
        $this->assertEmpty($both->getErrors());
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
            'sent_by' => ContractDeliveryMethod::Email,
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
     * The transfer checks before it writes, but the last word is here, so that no other way in can
     * carry a proposal over that nobody has agreed to.
     *
     * @return void
     */
    public function testNothingIsCarriedOverBeforeItIsConcluded(): void
    {
        $proposal = $this->save();

        $applied = $this->Proposals->patchEntity($proposal, ['applied' => DateTime::now()]);
        $this->Proposals->save($applied);
        $this->assertArrayHasKey('applied', $applied->getErrors());

        $proposal = $this->Proposals->get($proposal->id);
        $concluded = $this->Proposals->patchEntity($proposal, [
            'conclusion_date' => '2026-10-05',
            'applied' => DateTime::now(),
        ]);
        $this->assertNotFalse($this->Proposals->save($concluded));
        $this->assertEmpty($concluded->getErrors());
    }

    /**
     * What happened and what was given up on are not both true of the same proposal.
     *
     * @return void
     */
    public function testAProposalIsEitherCarriedOverOrGivenUpOn(): void
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
     * Sending locks the proposal; carrying it over or giving up on it settles it.
     *
     * @return void
     */
    public function testWhatMayStillBeChangedAndWhatMayBeTakenBack(): void
    {
        $open = $this->Proposals->get(self::PROPOSAL_ID);
        $this->assertTrue($this->Proposals->mayBeEdited($open));
        $this->assertTrue($this->Proposals->mayBeDeleted($open));

        $sent = $this->Proposals->patchEntity($open, [
            'sent_date' => '2026-10-01',
            'sent_by' => ContractDeliveryMethod::Email,
        ]);
        $this->Proposals->saveOrFail($sent);
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

        $pending = $this->Proposals->find('pendingTransfer')->all()->extract('id')->toArray();
        $this->assertContains($waiting->id, $pending);
        $this->assertNotContains(self::PROPOSAL_ID, $pending);
    }
}
