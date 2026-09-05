<?php
declare(strict_types=1);

namespace App\Test\TestCase\Contracts\Check;

use App\Contracts\Check\UnsentProposalCheck;
use App\Model\Table\ContractVersionProposalsTable;
use App\Test\Traits\TableTestTrait;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\Contracts\Check\UnsentProposalCheck Test Case
 */
#[CoversClass(UnsentProposalCheck::class)]
class UnsentProposalCheckTest extends TestCase
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
     * @param bool $ignore_inactive Whether to count only the proposals whose day has come or is near.
     * @return array<string>
     */
    private function found(bool $ignore_inactive = true): array
    {
        /** @var \App\Model\Table\ContractVersionProposalsTable $proposals */
        $proposals = $this->getTableLocator()->get(ContractVersionProposalsTable::class);

        return (new UnsentProposalCheck($proposals, $ignore_inactive))
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
     * Nor one that has already been carried over or given up on.
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
