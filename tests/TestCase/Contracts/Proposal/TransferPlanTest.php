<?php
declare(strict_types=1);

namespace App\Test\TestCase\Contracts\Proposal;

use App\Contracts\Proposal\PlannedChange;
use App\Contracts\Proposal\ProposalTransfer;
use App\Contracts\Proposal\TransferPlan;
use App\Model\Entity\ContractVersionProposal;
use App\Model\Enum\ProposalPurpose;
use App\Test\Traits\TableTestTrait;
use Cake\I18n\Date;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\Contracts\Proposal\TransferPlan Test Case
 *
 * What is asked of it is the promise it is there for: that the list the preview draws is the list
 * the transfer writes, down to the fields nobody asked for.
 */
#[CoversClass(TransferPlan::class)]
#[CoversClass(PlannedChange::class)]
class TransferPlanTest extends TestCase
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
        'app.Queues',
        'app.Services',
        'app.Billings',
        'app.ContractVersionProposals',
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
        $this->assertSame(TransferPlan::VERSION, $write->target);
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
        foreach ((new TransferPlan())->of($proposal) as $write) {
            $agendas[$write->target] = $write->agenda();
        }

        $this->assertSame('Contract Version', $agendas[TransferPlan::VERSION] ?? null);
        $this->assertSame('Contract', $agendas[TransferPlan::CONTRACT] ?? null);
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

        $planned = (new TransferPlan())->of($proposal);
        $this->assertNotSame([], $planned, 'The plan had nothing in it to check.');

        (new ProposalTransfer())->carryOver($proposal);

        $versions = $this->getTableLocator()->get('ContractVersions');
        $contracts = $this->getTableLocator()->get('Contracts');

        foreach ($planned as $write) {
            $afterwards = $write->target === TransferPlan::CONTRACT
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
     * One of the planned writes, by the field it is for.
     *
     * @param \App\Model\Entity\ContractVersionProposal $proposal The proposal.
     * @param string $field Which field.
     * @return \App\Contracts\Proposal\PlannedChange|null
     */
    private function planned(ContractVersionProposal $proposal, string $field): ?PlannedChange
    {
        foreach ((new TransferPlan())->of($proposal) as $write) {
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
     * @return \App\Model\Entity\ContractVersionProposal
     */
    private function proposal(array $says): ContractVersionProposal
    {
        $proposals = $this->getTableLocator()->get('ContractVersionProposals');
        $proposals->saveOrFail(
            $proposals->patchEntity($proposals->get(self::PROPOSAL_ID), $says),
            ['checkRules' => false],
        );

        /** @var \App\Model\Entity\ContractVersionProposal $proposal */
        $proposal = $proposals->get(self::PROPOSAL_ID);

        return $proposal;
    }
}
