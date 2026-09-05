<?php
declare(strict_types=1);

namespace App\Test\TestCase\Contracts\Check;

use App\Contracts\Check\UnsignedProposalCheck;
use App\Model\Table\ContractVersionProposalsTable;
use App\Test\Traits\TableTestTrait;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\Contracts\Check\UnsignedProposalCheck Test Case
 */
#[CoversClass(UnsignedProposalCheck::class)]
class UnsignedProposalCheckTest extends TestCase
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
        'app.Queues',
        'app.Services',
        'app.Billings',
        'app.ContractVersionProposals',
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
        $proposals = $this->getTableLocator()->get('ContractVersionProposals');

        $proposals->saveOrFail(
            $proposals->patchEntity($proposals->get(self::PROPOSAL_ID), $says),
            ['checkRules' => false],
        );
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
     * @param bool $ignore_inactive Whether to count only the papers whose wait has run out.
     * @return array<string>
     */
    private function found(bool $ignore_inactive = true): array
    {
        /** @var \App\Model\Table\ContractVersionProposalsTable $proposals */
        $proposals = $this->getTableLocator()->get(ContractVersionProposalsTable::class);

        return (new UnsignedProposalCheck($proposals, $ignore_inactive))
            ->find()
            ->all()
            ->extract('id')
            ->toList();
    }

    /**
     * Papers that went out a month ago and have not come back are what this is about.
     *
     * @return void
     */
    public function testPapersOutTooLongAreFound(): void
    {
        $this->proposalSays(['sent_date' => Date::now()->subDays(30)]);

        $this->assertContains(self::PROPOSAL_ID, $this->found());
    }

    /**
     * Papers that went out yesterday are nobody's fault, and the wider reading does not make them
     * one: what it widens to is the contracts that serve nobody, not the post still in transit.
     *
     * @return void
     */
    public function testPapersOnlyJustSentAreNotAFinding(): void
    {
        $this->proposalSays(['sent_date' => Date::now()->subDays(1)]);

        $this->assertNotContains(self::PROPOSAL_ID, $this->found());
        $this->assertNotContains(self::PROPOSAL_ID, $this->found(ignore_inactive: false));
    }

    /**
     * A proposal nobody has sent belongs to the other check, not this one.
     *
     * @return void
     */
    public function testAProposalThatNeverWentOutIsNotFound(): void
    {
        $this->proposalSays(['sent_date' => null]);

        $this->assertNotContains(self::PROPOSAL_ID, $this->found());
        $this->assertNotContains(self::PROPOSAL_ID, $this->found(ignore_inactive: false));
    }

    /**
     * Nor is one that has come back signed, whatever is left to be done with it.
     *
     * @return void
     */
    public function testASignedProposalIsNotFound(): void
    {
        $this->proposalSays([
            'sent_date' => Date::now()->subDays(30),
            'conclusion_date' => Date::now()->subDays(2),
        ]);

        $this->assertNotContains(self::PROPOSAL_ID, $this->found());
    }

    /**
     * Nor one that has already been carried over or given up on.
     *
     * @return void
     */
    public function testASettledProposalIsNotFound(): void
    {
        $this->proposalSays([
            'sent_date' => Date::now()->subDays(30),
            'applied' => DateTime::now(),
        ]);
        $this->assertNotContains(self::PROPOSAL_ID, $this->found());

        $this->proposalSays(['applied' => null, 'revoked' => DateTime::now()]);
        $this->assertNotContains(self::PROPOSAL_ID, $this->found());
    }

    /**
     * Papers on a contract that serves nobody are not the day's work. They are still on file
     * though, so the wider reading has them.
     *
     * @return void
     */
    public function testPapersOnAContractThatServesNobodyAreLeftAloneUnlessAskedFor(): void
    {
        $this->proposalSays(['sent_date' => Date::now()->subDays(30)]);
        $this->theContractServesNobody();

        $this->assertNotContains(self::PROPOSAL_ID, $this->found());
        $this->assertContains(self::PROPOSAL_ID, $this->found(ignore_inactive: false));
    }
}
