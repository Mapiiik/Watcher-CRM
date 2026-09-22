<?php
declare(strict_types=1);

namespace App\Test\TestCase\Contracts\Proposal;

use App\Contracts\Proposal\ChangeApplication;
use App\Contracts\Proposal\ChangePlan;
use App\Contracts\Proposal\PlannedChange;
use App\Model\Entity\ContractProposal;
use App\Model\Enum\ProposalPurpose;
use App\Test\Traits\TableTestTrait;
use Cake\I18n\Date;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\Contracts\Proposal\ChangePlan Test Case
 *
 * What is asked of it is the promise it is there for: that the list the preview draws is the list
 * applying the changes writes, down to the fields nobody asked for.
 */
#[CoversClass(ChangePlan::class)]
#[CoversClass(PlannedChange::class)]
class ChangePlanTest extends TestCase
{
    use TableTestTrait;

    /**
     * A concluded version, and the proposal on it.
     */
    private const VERSION_ID = '74824fba-20b2-46fc-806c-df795aa9e429';
    private const PROPOSAL_ID = 'c9a1f2b3-4d5e-4f60-8a71-9b2c3d4e5f60';

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
     * The signature is written onto a version that has none, and the plan says so before it is.
     *
     * @return void
     */
    public function testTheSignatureIsPlannedThoughNobodyAskedForIt(): void
    {
        $this->version(['conclusion_date' => null]);
        $proposal = $this->proposal(['conclusion_date' => '2026-09-15']);

        $write = $this->planned($proposal, 'conclusion_date');

        $this->assertNotNull($write);
        $this->assertFalse($write->asked, 'The signature was put down as something asked for.');
        $this->assertSame(ChangePlan::VERSION, $write->target);
        $this->assertInstanceOf(Date::class, $write->to);
        $this->assertSame('2026-09-15', $write->to->toDateString());
    }

    /**
     * A version that was signed long ago keeps its own day, so nothing is planned for it.
     *
     * @return void
     */
    public function testAVersionThatIsAlreadySignedHasNothingPlannedForIt(): void
    {
        $this->version(['conclusion_date' => '2026-01-01']);
        $proposal = $this->proposal(['conclusion_date' => '2026-09-15']);

        $this->assertNull($this->planned($proposal, 'conclusion_date'));
    }

    /**
     * An amendment counts itself, and the count is on the list before it is written.
     *
     * @return void
     */
    public function testTheAmendmentCountIsPlanned(): void
    {
        $this->version(['conclusion_date' => '2026-01-01', 'number_of_amendments' => 5]);
        $proposal = $this->proposal([
            'purpose' => ProposalPurpose::ServiceChange->value,
            'conclusion_date' => '2026-09-15',
        ]);

        $write = $this->planned($proposal, 'number_of_amendments');

        $this->assertNotNull($write);
        $this->assertFalse($write->asked);
        $this->assertSame(5, $write->from);
    }

    /**
     * Every write says which agenda it lands in, so that a page listing them can be read without
     * knowing what each record is called.
     *
     * @return void
     */
    public function testEachWriteSaysWhichAgendaItIsFor(): void
    {
        $this->version(['conclusion_date' => null]);
        $proposal = $this->proposal([
            'conclusion_date' => '2026-09-15',
            'changes' => ['contract' => ['termination_date' => '2026-12-31']],
        ]);

        $agendas = [];
        foreach ((new ChangePlan())->of($proposal) as $write) {
            $agendas[$write->target] = $write->agenda();
        }

        $this->assertSame('Contract Version', $agendas[ChangePlan::VERSION] ?? null);
        $this->assertSame('Contract', $agendas[ChangePlan::CONTRACT] ?? null);
    }

    /**
     * Whatever the plan says is what the records end up saying. This is the whole of what holding
     * the plan as a value buys, so it is asked of every field it names at once.
     *
     * @return void
     */
    public function testWhatIsPlannedIsWhatIsWritten(): void
    {
        $this->version(['conclusion_date' => null]);
        $proposal = $this->proposal([
            'purpose' => ProposalPurpose::ServiceChange->value,
            'conclusion_date' => '2026-09-15',
        ]);

        $planned = (new ChangePlan())->of($proposal);
        $this->assertNotSame([], $planned, 'The plan had nothing in it to check.');

        (new ChangeApplication())->apply($proposal);

        $versions = $this->getTableLocator()->get('ContractVersions');
        $contracts = $this->getTableLocator()->get('Contracts');

        foreach ($planned as $write) {
            $afterwards = $write->target === ChangePlan::CONTRACT
                ? $contracts->get($write->id)->get($write->field)
                : $versions->get($write->id)->get($write->field);

            $this->assertSame(
                (string)$write->to,
                (string)$afterwards,
                sprintf('%s was not written as planned.', $write->field),
            );
        }
    }

    /**
     * A version the papers bring into being is not a record yet, so nothing planned for it points
     * anywhere - a page drawing the list has to be able to tell that from a record it may open.
     *
     * @return void
     */
    public function testWhatStartsAVersionPointsAtNoRecord(): void
    {
        $proposal = $this->proposal([
            'purpose' => ProposalPurpose::NewContract->value,
            'contract_version_id' => null,
            'conclusion_date' => '2026-09-15',
        ]);

        $planned = (new ChangePlan())->of($proposal);
        $this->assertNotSame([], $planned, 'Starting a version was planned as nothing at all.');

        $about = 0;

        foreach ($planned as $write) {
            if ($write->target !== ChangePlan::VERSION) {
                continue;
            }

            $about++;
            $this->assertNull($write->id, sprintf('%s was written as if the version were there.', $write->field));
        }

        $this->assertGreaterThan(0, $about, 'Nothing was planned for the version at all.');
    }

    /**
     * One of the planned writes, by the field it is for.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @param string $field Which field.
     * @return \App\Contracts\Proposal\PlannedChange|null
     */
    private function planned(ContractProposal $proposal, string $field): ?PlannedChange
    {
        foreach ((new ChangePlan())->of($proposal) as $write) {
            if ($write->field === $field) {
                return $write;
            }
        }

        return null;
    }

    /**
     * The version, with what the test wants it to say.
     *
     * @param array<string, mixed> $says What it says.
     * @return void
     */
    private function version(array $says): void
    {
        $versions = $this->getTableLocator()->get('ContractVersions');
        $versions->saveOrFail(
            $versions->patchEntity($versions->get(self::VERSION_ID), $says),
            ['checkRules' => false],
        );
    }

    /**
     * The proposal, with what the test wants it to say.
     *
     * @param array<string, mixed> $says What it says.
     * @return \App\Model\Entity\ContractProposal
     */
    private function proposal(array $says): ContractProposal
    {
        $proposals = $this->getTableLocator()->get('ContractProposals');
        $papers = $proposals->get(self::PROPOSAL_ID);

        // The signature is the envelope's, so a test that gives the papers one gives it there.
        $ofTheRound = array_intersect_key(
            $says,
            array_flip(['sent_date', 'delivery_type', 'conclusion_date']),
        );

        if ($ofTheRound !== []) {
            $envelopes = $this->getTableLocator()->get('CustomerProposals');
            $envelopes->saveOrFail(
                $envelopes->patchEntity($envelopes->get($papers->customer_proposal_id), $ofTheRound),
                ['checkRules' => false],
            );
        }

        $says = array_diff_key($says, $ofTheRound);

        if ($says !== []) {
            $proposals->saveOrFail(
                $proposals->patchEntity($papers, $says),
                ['checkRules' => false],
            );
        }

        /** @var \App\Model\Entity\ContractProposal $proposal */
        $proposal = $proposals->get(self::PROPOSAL_ID, contain: ['CustomerProposals']);

        return $proposal;
    }
}
