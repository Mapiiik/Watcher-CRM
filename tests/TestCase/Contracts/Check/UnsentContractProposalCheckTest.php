<?php
declare(strict_types=1);

namespace App\Test\TestCase\Contracts\Check;

use App\Check\CheckScope;
use App\Contracts\Check\UnsentContractProposalCheck;
use App\Model\Table\ContractProposalsTable;
use App\Test\Traits\TableTestTrait;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Settings\Utility\Settings;

/**
 * App\Contracts\Check\UnsentContractProposalCheck Test Case
 */
#[CoversClass(UnsentContractProposalCheck::class)]
class UnsentContractProposalCheckTest extends TestCase
{
    use TableTestTrait;

    /**
     * The proposal the fixture carries: open, unsent, changing nothing.
     */
    private const PROPOSAL_ID = 'c9a1f2b3-4d5e-4f60-8a71-9b2c3d4e5f60';

    /**
     * The state both fixture contracts are in.
     */
    private const STATE_ID = '3fc51c92-5dbb-4bd4-9a47-237169c2755c';

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
     * @return void
     */
    private function proposalSays(array $says): void
    {
        $proposals = $this->getTableLocator()->get('ContractProposals');
        $proposal = $proposals->get(self::PROPOSAL_ID);

        // The sending and the signature are the envelope's, so anything said about them is said
        // there - the papers keep the day they take effect and whether they were given up on.
        $envelopes = $this->getTableLocator()->get('CustomerProposals');
        $ofTheProposal = array_intersect_key(
            $says,
            array_flip(['sent_date', 'delivery_type', 'conclusion_date']),
        );

        if ($ofTheProposal !== []) {
            $envelopes->saveOrFail(
                $envelopes->patchEntity($envelopes->get($proposal->customer_proposal_id), $ofTheProposal),
                ['checkRules' => false],
            );
        }

        $ofThePapers = array_diff_key($says, $ofTheProposal);

        if ($ofThePapers !== []) {
            $proposals->saveOrFail(
                $proposals->patchEntity($proposal, $ofThePapers),
                ['checkRules' => false],
            );
        }
    }

    /**
     * Take the service away from the contract the proposal hangs on.
     *
     * @return void
     */
    private function theContractServesNobody(): void
    {
        $states = $this->getTableLocator()->get('ContractStates');

        $states->saveOrFail(
            $states->patchEntity($states->get(self::STATE_ID), ['active_services' => false]),
            ['checkRules' => false],
        );
    }

    /**
     * What the check finds.
     *
     * @param bool $ignore_inactive Whether to count only the proposals whose day has come or is near.
     * @return array<string>
     */
    private function found(bool $ignore_inactive = true): array
    {
        /** @var \App\Model\Table\ContractProposalsTable $proposals */
        $proposals = $this->getTableLocator()->get(ContractProposalsTable::class);

        return (new UnsentContractProposalCheck($proposals, new CheckScope($ignore_inactive)))
            ->find()
            ->all()
            ->extract('id')
            ->toList();
    }

    /**
     * A proposal drawn up, never sent, and due to take effect today is what this is about.
     *
     * @return void
     */
    public function testAProposalNobodyHasSentIsFound(): void
    {
        $this->proposalSays(['sent_date' => null, 'effective_from' => Date::now()]);

        $this->assertContains(self::PROPOSAL_ID, $this->found());
    }

    /**
     * One that has gone out is not: from here on it is the customer who is being waited for.
     *
     * @return void
     */
    public function testASentProposalIsNotFound(): void
    {
        $this->proposalSays(['sent_date' => Date::now()->subDays(2), 'effective_from' => Date::now()]);

        $this->assertNotContains(self::PROPOSAL_ID, $this->found());
        $this->assertNotContains(self::PROPOSAL_ID, $this->found(ignore_inactive: false));
    }

    /**
     * Nor one that came back signed with nobody having written the sending down. The technician
     * takes the papers to the installation and brings them back signed, so that is the usual
     * course of a job that is finished rather than a job nobody started.
     *
     * @return void
     */
    public function testOneThatCameBackSignedIsNotFound(): void
    {
        $this->proposalSays([
            'sent_date' => null,
            'conclusion_date' => Date::now()->subDays(2),
            'effective_from' => Date::now(),
        ]);

        $this->assertNotContains(self::PROPOSAL_ID, $this->found());
        $this->assertNotContains(self::PROPOSAL_ID, $this->found(ignore_inactive: false));
    }

    /**
     * Nor one that has already been applied or given up on.
     *
     * @return void
     */
    public function testASettledProposalIsNotFound(): void
    {
        $this->proposalSays([
            'sent_date' => null,
            'effective_from' => Date::now(),
            'applied' => DateTime::now(),
        ]);
        $this->assertNotContains(self::PROPOSAL_ID, $this->found());

        $this->proposalSays(['applied' => null, 'revoked' => DateTime::now()]);
        $this->assertNotContains(self::PROPOSAL_ID, $this->found());
    }

    /**
     * A proposal whose day is months off is not a finding at all - the papers have until then to
     * go out, and the contract's own card would otherwise report the operator's work in hand back
     * to them as a fault.
     *
     * @return void
     */
    public function testOneWhoseDayIsFarOffIsNotAFinding(): void
    {
        $this->proposalSays(['sent_date' => null, 'effective_from' => Date::now()->addMonths(6)]);

        $this->assertNotContains(self::PROPOSAL_ID, $this->found());
        $this->assertNotContains(self::PROPOSAL_ID, $this->found(ignore_inactive: false));
    }

    /**
     * A proposal from before the day the office watches from is nobody's work, whichever question
     * is asked. The whole family is held to that one day, so this is where it is proved.
     *
     * @return void
     */
    public function testOneFromBeforeTheWatchedDayIsNotAFinding(): void
    {
        $this->proposalSays(['sent_date' => null, 'effective_from' => new Date('2025-11-30')]);
        Settings::set('core.contracts.paperwork.consider_from', '2026-01-01');

        $this->assertNotContains(self::PROPOSAL_ID, $this->found());
        $this->assertNotContains(self::PROPOSAL_ID, $this->found(ignore_inactive: false));

        Settings::set('core.contracts.paperwork.consider_from', '2025-01-01');

        $this->assertContains(self::PROPOSAL_ID, $this->found());
    }

    /**
     * One on a contract that serves nobody is not the day's work either.
     *
     * @return void
     */
    public function testOneOnAContractThatServesNobodyIsLeftAloneUnlessAskedFor(): void
    {
        $this->proposalSays(['sent_date' => null, 'effective_from' => Date::now()]);
        $this->theContractServesNobody();

        $this->assertNotContains(self::PROPOSAL_ID, $this->found());
        $this->assertContains(self::PROPOSAL_ID, $this->found(ignore_inactive: false));
    }
}
