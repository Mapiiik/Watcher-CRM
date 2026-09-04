<?php
declare(strict_types=1);

namespace App\Test\TestCase\Contracts\Check;

use App\Contracts\Check\UntransferredProposalCheck;
use App\Model\Table\ContractVersionProposalsTable;
use App\Test\Traits\TableTestTrait;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\Contracts\Check\UntransferredProposalCheck Test Case
 */
#[CoversClass(UntransferredProposalCheck::class)]
class UntransferredProposalCheckTest extends TestCase
{
    use TableTestTrait;

    /**
     * The proposal the fixture carries: open, unsent, changing nothing.
     */
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
     * What the check finds.
     *
     * @param bool $ignore_inactive Whether to count only proposals whose day has come or is near.
     * @return array<string>
     */
    private function found(bool $ignore_inactive = true): array
    {
        /** @var \App\Model\Table\ContractVersionProposalsTable $proposals */
        $proposals = $this->getTableLocator()->get(ContractVersionProposalsTable::class);

        return (new UntransferredProposalCheck($proposals, $ignore_inactive))
            ->find()
            ->all()
            ->extract('id')
            ->toList();
    }

    /**
     * A proposal the customer has signed and nobody has acted on is what this is about: the service
     * runs and is invoiced on the old terms until somebody carries it over.
     *
     * @return void
     */
    public function testASignedProposalNobodyHasActedOnIsFound(): void
    {
        $this->proposalSays([
            'conclusion_date' => Date::now()->subDays(3),
            'effective_from' => Date::now(),
        ]);

        $this->assertContains(self::PROPOSAL_ID, $this->found());
    }

    /**
     * One nobody has signed is not: there is nothing to carry over, and the unsigned paperwork is
     * chased elsewhere.
     *
     * @return void
     */
    public function testAnUnsignedProposalIsNotFound(): void
    {
        $this->proposalSays([
            'conclusion_date' => null,
            'effective_from' => Date::now(),
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
            'conclusion_date' => Date::now()->subDays(3),
            'effective_from' => Date::now(),
            'applied' => DateTime::now(),
        ]);
        $this->assertNotContains(self::PROPOSAL_ID, $this->found());

        $this->proposalSays(['applied' => null, 'revoked' => DateTime::now()]);
        $this->assertNotContains(self::PROPOSAL_ID, $this->found());
    }

    /**
     * A proposal whose day has not come yet is left alone by default - there is nothing to do about
     * it until it does - but the wider reading shows it, which is what putting the file straight
     * needs.
     *
     * @return void
     */
    public function testOneWhoseDayHasNotComeIsLeftAloneUnlessAskedFor(): void
    {
        $this->proposalSays([
            'conclusion_date' => Date::now(),
            'effective_from' => Date::now()->addMonths(6),
        ]);

        $this->assertNotContains(self::PROPOSAL_ID, $this->found());
        $this->assertContains(self::PROPOSAL_ID, $this->found(ignore_inactive: false));
    }
}
