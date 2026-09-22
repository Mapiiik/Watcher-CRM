<?php
declare(strict_types=1);

namespace App\Test\TestCase\Contracts\Proposal;

use App\Contracts\Proposal\ChangeApplication;
use App\Model\Entity\ContractProposal;
use App\Model\Enum\ProposalPurpose;
use App\Test\Traits\TableTestTrait;
use Cake\I18n\Date;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;

/**
 * App\Contracts\Proposal\ChangeApplication Test Case
 */
#[CoversClass(ChangeApplication::class)]
class ChangeApplicationTest extends TestCase
{
    use TableTestTrait;

    /**
     * The contract everything hangs off.
     */
    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

    /**
     * A concluded version of it.
     */
    private const VERSION_ID = '74824fba-20b2-46fc-806c-df795aa9e429';

    /**
     * The proposal the fixture carries: open, unsent, changing nothing.
     */
    private const PROPOSAL_ID = 'c9a1f2b3-4d5e-4f60-8a71-9b2c3d4e5f60';

    /**
     * A billing that runs on, which the fixture snapshot knows.
     */
    private const OPEN_BILLING_ID = 'b2000000-0000-4000-8000-000000000002';

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
        'app.ConnectionProfiles',
        'app.Services',
        'app.Billings',
        'app.CustomerProposals',
        'app.ContractProposals',
        'plugin.Settings.Settings',
    ];

    /**
     * The proposal, with what the test wants it to say.
     *
     * @param array<string, mixed> $says What it says.
     * @return \App\Model\Entity\ContractProposal
     */
    private function proposal(array $says = []): ContractProposal
    {
        $proposals = $this->getTableLocator()->get('ContractProposals');
        $proposal = $proposals->get(self::PROPOSAL_ID);

        // The sending and the signature are the envelope's, so that half of what a test says is
        // said there.
        $ofTheRound = array_intersect_key(
            $says,
            array_flip(['sent_date', 'delivery_type', 'conclusion_date']),
        );

        if ($ofTheRound !== []) {
            $envelopes = $this->getTableLocator()->get('CustomerProposals');
            $envelopes->saveOrFail(
                $envelopes->patchEntity($envelopes->get($proposal->customer_proposal_id), $ofTheRound),
                ['checkRules' => false],
            );
        }

        $says = array_diff_key($says, $ofTheRound);

        if ($says !== []) {
            $proposals->saveOrFail(
                $proposals->patchEntity($proposal, $says),
                ['checkRules' => false],
            );
        }

        // What it is read with is what asks about its state: the round it goes out in.
        return $proposals->get(self::PROPOSAL_ID, contain: ['CustomerProposals']);
    }

    /**
     * How many billings the contract has.
     *
     * @return int
     */
    private function billingCount(): int
    {
        return $this->getTableLocator()->get('Billings')
            ->find()
            ->where(['Billings.contract_id' => self::CONTRACT_ID])
            ->count();
    }

    /**
     * The ordinary proposal behind a new contract's papers changes nothing, and is applied all
     * the same - otherwise it would sit in the checks for ever as signed and not dealt with.
     *
     * @return void
     */
    public function testAProposalThatChangesNothingIsStillApplied(): void
    {
        $proposal = $this->proposal(['conclusion_date' => '2026-09-15']);
        $before = $this->billingCount();

        (new ChangeApplication())->apply($proposal);

        $this->assertTrue(
            $this->getTableLocator()->get('ContractProposals')
                ->get(self::PROPOSAL_ID)
                ->hasBeenApplied(),
        );
        $this->assertSame($before, $this->billingCount(), 'A proposal that asks for nothing wrote.');
    }

    /**
     * Applying the changes refuses what nobody has signed. The table refuses it too, but the service is
     * asked first, so it says so in its own words.
     *
     * @return void
     */
    public function testNothingIsAppliedBeforeItIsConcluded(): void
    {
        $this->expectException(RuntimeException::class);

        (new ChangeApplication())->apply($this->proposal());
    }

    /**
     * And what has already been settled one way or the other.
     *
     * @return void
     */
    public function testASettledProposalIsNotAppliedAgain(): void
    {
        $proposal = $this->proposal([
            'conclusion_date' => '2026-09-15',
            'revoked' => '2026-09-16 10:00:00',
        ]);

        $this->expectException(RuntimeException::class);

        (new ChangeApplication())->apply($proposal);
    }

    /**
     * A replaced billing stops the day before its replacement starts - the two halves the preview
     * showed, written as one act.
     *
     * @return void
     */
    public function testAReplacedBillingIsEndedAndItsReplacementStarted(): void
    {
        $proposal = $this->proposal([
            'conclusion_date' => '2026-09-15',
            'effective_from' => '2026-10-01',
            'changes' => ['billings' => [[
                'billing_id' => self::OPEN_BILLING_ID,
                'terminates_only' => false,
                'service_id' => 'eaacfeb3-1430-43ce-842e-497c5c95d953',
                'quantity' => 1,
                'price' => '299.00',
            ]]],
        ]);

        (new ChangeApplication())->apply($proposal);

        $billings = $this->getTableLocator()->get('Billings');

        $ended = $billings->get(self::OPEN_BILLING_ID);
        $this->assertSame('2026-09-30', $ended->billing_until?->toDateString());

        $started = $billings->find()
            ->where([
                'Billings.contract_id' => self::CONTRACT_ID,
                'Billings.billing_from' => '2026-10-01',
            ])
            ->firstOrFail();
        $this->assertSame('299.00', $started->get('price')?->toString());
        $this->assertSame('eaacfeb3-1430-43ce-842e-497c5c95d953', $started->get('service_id'));
    }

    /**
     * A billing that stopped of its own accord is left where it is.
     *
     * An ending only ever shortens. Writing the day before the papers take effect onto something
     * that stopped years ago would stretch it back over everything in between, and the customer
     * would be invoiced for all of it.
     *
     * @return void
     */
    public function testABillingThatAlreadyStoppedIsNotPutBackOnTheInvoice(): void
    {
        $billings = $this->getTableLocator()->get('Billings');
        $billings->saveOrFail(
            $billings->patchEntity($billings->get(self::OPEN_BILLING_ID), ['billing_until' => '2022-06-30']),
            ['checkRules' => false],
        );

        $proposal = $this->proposal([
            'conclusion_date' => '2026-09-15',
            'effective_from' => '2026-10-01',
            'changes' => ['billings' => [[
                'billing_id' => self::OPEN_BILLING_ID,
                'terminates_only' => true,
            ]]],
        ]);

        (new ChangeApplication())->apply($proposal);

        $this->assertSame(
            '2022-06-30',
            $billings->get(self::OPEN_BILLING_ID)->billing_until?->toDateString(),
            'A billing that stopped years ago was stretched back over everything in between.',
        );
    }

    /**
     * What the proposal asks of the version and of the contract is written too, but the state of
     * the contract is left alone - it has its own requirements to satisfy.
     *
     * @return void
     */
    public function testTheVersionAndTheContractTakeWhatIsAskedOfThem(): void
    {
        $contracts = $this->getTableLocator()->get('Contracts');
        $stateBefore = $contracts->get(self::CONTRACT_ID)->get('contract_state_id');

        $proposal = $this->proposal([
            'purpose' => ProposalPurpose::Termination->value,
            'conclusion_date' => '2026-09-15',
            'effective_from' => '2026-10-01',
            'terminated_contract_number' => 'Lorem ipsum dolor sit amet',
            'changes' => [
                'version' => [
                    'valid_until' => '2026-09-30',
                    'obligation_until' => '2026-09-30',
                ],
                'contract' => ['termination_date' => '2026-09-30'],
            ],
        ]);

        (new ChangeApplication())->apply($proposal);

        $version = $this->getTableLocator()->get('ContractVersions')->get(self::VERSION_ID);
        $this->assertSame('2026-09-30', $version->valid_until?->toDateString());
        // an agreement to end a contract adds no amendment to what it ends
        $this->assertSame(1, $version->get('number_of_amendments'));

        $contract = $contracts->get(self::CONTRACT_ID);
        $this->assertSame('2026-09-30', $contract->get('termination_date')?->toDateString());
        $this->assertSame($stateBefore, $contract->get('contract_state_id'));
    }

    /**
     * When one part of applying the changes will not go through, none of it does. A proposal half
     * applied is worse than none: the paper would describe one thing and the records another.
     *
     * @return void
     */
    public function testNothingIsWrittenWhenAnyOfItFails(): void
    {
        // A day inside a period that has been invoiced for, which the billings table refuses.
        $proposal = $this->proposal([
            'conclusion_date' => '2023-01-15',
            'effective_from' => '2023-02-01',
            'changes' => ['billings' => [[
                'billing_id' => self::OPEN_BILLING_ID,
                'terminates_only' => false,
                'service_id' => 'eaacfeb3-1430-43ce-842e-497c5c95d953',
                'quantity' => 1,
            ]]],
        ]);

        $before = $this->billingCount();

        try {
            (new ChangeApplication())->apply($proposal);
            $this->fail('The transfer wrote into an invoiced period.');
        } catch (RuntimeException) {
            // what it says is the billings table's business; that it wrote nothing is ours
        }

        $this->assertSame($before, $this->billingCount());
        $this->assertNull(
            $this->getTableLocator()->get('Billings')->get(self::OPEN_BILLING_ID)->billing_until,
        );
        $this->assertFalse(
            $this->getTableLocator()->get('ContractProposals')
                ->get(self::PROPOSAL_ID)
                ->hasBeenApplied(),
        );
    }

    /**
     * An administrator who says so may write into an invoiced period, the same as on a service
     * change.
     *
     * @return void
     */
    public function testAnInvoicedPeriodMayBeReachedIntoDeliberately(): void
    {
        $proposal = $this->proposal([
            'conclusion_date' => '2023-01-15',
            'effective_from' => '2023-02-01',
            'changes' => ['billings' => [[
                'billing_id' => self::OPEN_BILLING_ID,
                'terminates_only' => true,
            ]]],
        ]);

        (new ChangeApplication())->apply($proposal, null, reach_into_closed_periods: true);

        $this->assertSame(
            '2023-01-31',
            $this->getTableLocator()->get('Billings')
                ->get(self::OPEN_BILLING_ID)
                ->billing_until?->toDateString(),
        );
    }

    /**
     * A line pricing the connection below the contract's minimum is not applied, which is the
     * minimum having been raised after the line was written.
     *
     * @return void
     */
    public function testAConnectionBelowTheMinimumIsNotApplied(): void
    {
        $proposal = $this->connectionBelowTheMinimum(allowed: false);
        $before = $this->billingCount();

        try {
            (new ChangeApplication())->apply($proposal);
            $this->fail('The connection was carried over below the minimum.');
        } catch (RuntimeException $refused) {
            $this->assertStringContainsString('minimum set on the contract', $refused->getMessage());
        }

        $this->assertSame($before, $this->billingCount());
    }

    /**
     * What an administrator allowed on the line is applied by whoever presses the button.
     *
     * @return void
     */
    public function testALineAnAdministratorAllowedIsApplied(): void
    {
        $before = $this->billingCount();

        (new ChangeApplication())->apply($this->connectionBelowTheMinimum(allowed: true));

        $this->assertSame($before + 1, $this->billingCount());
    }

    /**
     * And the administrator may allow it when the changes are applied.
     *
     * @return void
     */
    public function testTheMinimumMayBeGoneBelowDeliberately(): void
    {
        $before = $this->billingCount();

        (new ChangeApplication())->apply(
            $this->connectionBelowTheMinimum(allowed: false),
            null,
            go_below_minimum: true,
        );

        $this->assertSame($before + 1, $this->billingCount());
    }

    /**
     * A signed proposal replacing the connection with a cheaper one than the contract's minimum.
     *
     * @param bool $allowed Whether an administrator allowed it on the line.
     * @return \App\Model\Entity\ContractProposal
     */
    private function connectionBelowTheMinimum(bool $allowed): ContractProposal
    {
        $this->getTableLocator()->get('Contracts')->updateAll(
            ['minimum_connection_price' => '500'],
            ['id' => self::CONTRACT_ID],
        );

        return $this->proposal([
            'conclusion_date' => '2026-09-15',
            'effective_from' => '2026-10-01',
            'changes' => ['billings' => [[
                'billing_id' => self::OPEN_BILLING_ID,
                'terminates_only' => false,
                'service_id' => 'eaacfeb3-1430-43ce-842e-497c5c95d953',
                'quantity' => 1,
                'price' => '299.00',
                'below_minimum_allowed' => $allowed,
            ]]],
        ]);
    }

    /**
     * The signature is recorded on the proposal, and the version takes it from there when it is
     * applied. Without this the version would go on reading as unsigned, and the customer
     * would be chased - and eventually cut off - for a paper that is on file.
     *
     * @return void
     */
    public function testTheVersionTakesTheSignatureFromTheProposal(): void
    {
        $versions = $this->getTableLocator()->get('ContractVersions');
        $versions->saveOrFail(
            $versions->patchEntity($versions->get(self::VERSION_ID), ['conclusion_date' => null]),
            ['checkRules' => false],
        );

        (new ChangeApplication())->apply($this->proposal(['conclusion_date' => '2026-09-15']));

        $this->assertSame(
            '2026-09-15',
            $versions->get(self::VERSION_ID)->conclusion_date?->toDateString(),
        );
    }

    /**
     * The papers number themselves from the count on the version - the one being printed is the
     * next after it - so applying an amendment has to move it on. Left where it was, the
     * second amendment would go out under the same number as the first.
     *
     * @return void
     */
    public function testAnAmendmentAppliedIsCounted(): void
    {
        $versions = $this->getTableLocator()->get('ContractVersions');
        $before = $versions->get(self::VERSION_ID)->get('number_of_amendments');

        (new ChangeApplication())->apply($this->proposal([
            'purpose' => ProposalPurpose::ServiceChange->value,
            'conclusion_date' => '2026-09-15',
        ]));

        $this->assertSame($before + 1, $versions->get(self::VERSION_ID)->get('number_of_amendments'));
    }

    /**
     * And the count it leaves behind is the number printed on that paper, worked out from the
     * snapshot - not one more than whatever the version happens to say when the button is pressed.
     *
     * @return void
     */
    public function testTheCountIsTheNumberThePaperCarries(): void
    {
        $versions = $this->getTableLocator()->get('ContractVersions');
        $versions->saveOrFail(
            $versions->patchEntity($versions->get(self::VERSION_ID), ['number_of_amendments' => 5]),
            ['checkRules' => false],
        );

        $proposal = $this->proposal(['purpose' => ProposalPurpose::ServiceChange->value]);
        $taken = $proposal->get('snapshot');
        $taken['version']['number_of_amendments'] = 1;

        (new ChangeApplication())->apply($this->proposal([
            'snapshot' => $taken,
            'conclusion_date' => '2026-09-15',
        ]));

        // the paper said "amendment no. 2", so that is what the version is left holding
        $this->assertSame(2, $versions->get(self::VERSION_ID)->get('number_of_amendments'));
    }

    /**
     * The proposal as the form draws one up whose version is still to come: no version named, and
     * a snapshot of the version as it will be rather than of one that is already there.
     *
     * @param array<string, mixed> $says What else it says.
     * @return \App\Model\Entity\ContractProposal
     */
    private function proposalWithoutAVersion(array $says = []): ContractProposal
    {
        $proposal = $this->proposal();
        $snapshot = $proposal->snapshot;

        $snapshot['version'] = [
            'id' => null,
            'contract_id' => self::CONTRACT_ID,
            'valid_from' => $says['effective_from'] ?? '2026-11-01',
            'valid_until' => null,
            'obligation_until' => null,
            'conclusion_date' => null,
            'number_of_amendments' => 0,
        ];

        return $this->proposal($says + [
            'contract_version_id' => null,
            'snapshot' => $snapshot,
        ]);
    }

    /**
     * Papers for a new contract may be drawn up before the version they are about exists, and
     * applying them is what brings it into being. It starts on the day the papers take effect
     * and takes the day they were signed, so the record says what the paper said.
     *
     * @return void
     */
    public function testAProposalWithoutAVersionStartsOneWhenItIsApplied(): void
    {
        $versions = $this->getTableLocator()->get('ContractVersions');
        $before = $versions->find()->where(['contract_id' => self::CONTRACT_ID])->count();

        $proposal = $this->proposalWithoutAVersion([
            'purpose' => ProposalPurpose::NewContract->value,
            'effective_from' => '2026-11-01',
            'conclusion_date' => '2026-10-20',
        ]);

        (new ChangeApplication())->apply($proposal);

        $this->assertSame(
            $before + 1,
            $versions->find()->where(['contract_id' => self::CONTRACT_ID])->count(),
        );

        $carried = $this->getTableLocator()->get('ContractProposals')->get(self::PROPOSAL_ID);
        $this->assertNotNull($carried->contract_version_id, 'The papers did not keep their version.');

        $started = $versions->get($carried->contract_version_id);
        $this->assertSame('2026-11-01', $started->valid_from->toDateString());
        $this->assertSame('2026-10-20', $started->conclusion_date?->toDateString());
        $this->assertSame(0, $started->get('number_of_amendments'));
    }

    /**
     * And a paper that starts a version does not amend it, whatever it is for - the version has no
     * signature of its own until this very paper gives it one.
     *
     * @return void
     */
    public function testAVersionBeingStartedIsNeverAnAmendment(): void
    {
        $proposal = $this->proposalWithoutAVersion([
            'purpose' => ProposalPurpose::ServiceChange->value,
            'effective_from' => '2026-11-01',
            'conclusion_date' => '2026-10-20',
        ]);

        (new ChangeApplication())->apply($proposal);

        $carried = $this->getTableLocator()->get('ContractProposals')->get(self::PROPOSAL_ID);
        $started = $this->getTableLocator()->get('ContractVersions')->get($carried->contract_version_id);

        $this->assertSame(0, $started->get('number_of_amendments'));
    }

    /**
     * A new contract is not an amendment of the version it starts - it starts its own count.
     *
     * @return void
     */
    public function testANewContractIsNotCounted(): void
    {
        $versions = $this->getTableLocator()->get('ContractVersions');
        $before = $versions->get(self::VERSION_ID)->get('number_of_amendments');

        (new ChangeApplication())->apply($this->proposal([
            'purpose' => ProposalPurpose::NewContract->value,
            'conclusion_date' => '2026-09-15',
        ]));

        $this->assertSame($before, $versions->get(self::VERSION_ID)->get('number_of_amendments'));
    }

    /**
     * An unsigned version is not being amended, whatever the proposal says it is for - the printing
     * would not have offered an amendment either. It takes the signature and stays at nought.
     *
     * @return void
     */
    public function testAVersionNobodyHadSignedIsNotAmended(): void
    {
        $versions = $this->getTableLocator()->get('ContractVersions');
        $before = $versions->get(self::VERSION_ID)->get('number_of_amendments');
        $versions->saveOrFail(
            $versions->patchEntity($versions->get(self::VERSION_ID), ['conclusion_date' => null]),
            ['checkRules' => false],
        );

        (new ChangeApplication())->apply($this->proposal([
            'purpose' => ProposalPurpose::ServiceChange->value,
            'conclusion_date' => '2026-09-15',
        ]));

        $version = $versions->get(self::VERSION_ID);
        $this->assertSame('2026-09-15', $version->conclusion_date?->toDateString());
        $this->assertSame($before, $version->get('number_of_amendments'));
    }

    /**
     * A version signed long ago and amended today keeps the day it was agreed on. The amendment's
     * day is the proposal's business, and writing it over would say the customer agreed to the
     * version itself later than they did.
     *
     * @return void
     */
    public function testAVersionThatIsAlreadySignedKeepsItsOwnDay(): void
    {
        (new ChangeApplication())->apply($this->proposal(['conclusion_date' => '2026-09-15']));

        $this->assertSame(
            '2022-11-30',
            $this->getTableLocator()->get('ContractVersions')
                ->get(self::VERSION_ID)
                ->conclusion_date?->toDateString(),
        );
    }

    /**
     * The day the replacement starts is the proposal's own, not today's.
     *
     * @return void
     */
    public function testTheDayItTakesEffectIsTheProposals(): void
    {
        $proposal = $this->proposal([
            'conclusion_date' => '2026-09-15',
            'effective_from' => '2026-11-01',
            'changes' => ['billings' => [[
                'billing_id' => self::OPEN_BILLING_ID,
                'terminates_only' => true,
            ]]],
        ]);

        (new ChangeApplication())->apply($proposal);

        $this->assertSame(
            (new Date('2026-10-31'))->toDateString(),
            $this->getTableLocator()->get('Billings')
                ->get(self::OPEN_BILLING_ID)
                ->billing_until?->toDateString(),
        );
    }
}
