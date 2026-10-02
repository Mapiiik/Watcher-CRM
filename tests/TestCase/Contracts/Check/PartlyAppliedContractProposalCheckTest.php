<?php
declare(strict_types=1);

namespace App\Test\TestCase\Contracts\Check;

use App\Check\CheckScope;
use App\Contracts\Check\PartlyAppliedContractProposalCheck;
use App\Model\Table\ContractProposalsTable;
use App\Test\Traits\TableTestTrait;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\Contracts\Check\PartlyAppliedContractProposalCheck Test Case
 */
#[CoversClass(PartlyAppliedContractProposalCheck::class)]
class PartlyAppliedContractProposalCheckTest extends TestCase
{
    use TableTestTrait;

    /**
     * The proposal the fixture carries.
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
        'app.ConnectionProfiles',
        'app.Services',
        'app.Billings',
        'app.CustomerProposals',
        'app.ContractProposals',
        'plugin.Settings.Settings',
    ];

    /**
     * A proposal applied with nothing left out says nothing here.
     *
     * @return void
     */
    public function testAProposalAppliedInFullIsNotAFinding(): void
    {
        $this->proposalSays(['applied' => DateTime::now()->subDays(2)]);

        $this->assertSame([], $this->found());
    }

    /**
     * One applied with a line passed over is, and stays one until somebody has settled it - there
     * is no day it stops being worth saying.
     *
     * @return void
     */
    public function testAProposalAppliedWithALineLeftOutIsFound(): void
    {
        $this->proposalSays([
            'applied' => DateTime::now()->subDays(2),
            'left_out' => ['7db8e6a3-e8e9-47c3-8683-473b77c56664' => 'The billing is no longer there.'],
        ]);

        $this->assertSame([self::PROPOSAL_ID], $this->found());
        $this->assertSame([self::PROPOSAL_ID], $this->found(false));
    }

    /**
     * And once somebody has said it is done, it stops being one. A job nobody can close is a
     * listing nobody goes on reading.
     *
     * @return void
     */
    public function testWhatSomebodyHasSeenToIsNoLongerAFinding(): void
    {
        $this->proposalSays([
            'applied' => DateTime::now()->subDays(2),
            'left_out' => ['7db8e6a3-e8e9-47c3-8683-473b77c56664' => 'The billing is no longer there.'],
            'left_out_settled' => DateTime::now()->subDays(1),
        ]);

        $this->assertSame([], $this->found());
    }

    /**
     * One that has not been applied at all is somebody else's business - the check that watches
     * for a signed proposal nobody acted on has it.
     *
     * @return void
     */
    public function testAProposalNobodyAppliedIsNotAFinding(): void
    {
        $this->proposalSays([
            'left_out' => ['7db8e6a3-e8e9-47c3-8683-473b77c56664' => 'The billing is no longer there.'],
        ]);

        $this->assertSame([], $this->found());
    }

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

        foreach ($says as $field => $value) {
            $proposal->set($field, $value);
        }

        $proposals->saveOrFail($proposal, ['checkRules' => false]);
    }

    /**
     * What the check finds.
     *
     * @param bool $ignore_inactive Whether to keep to what is running.
     * @return array<string>
     */
    private function found(bool $ignore_inactive = true): array
    {
        /** @var \App\Model\Table\ContractProposalsTable $proposals */
        $proposals = $this->getTableLocator()->get(ContractProposalsTable::class);

        return (new PartlyAppliedContractProposalCheck($proposals, new CheckScope($ignore_inactive)))
            ->find()
            ->all()
            ->extract('id')
            ->toList();
    }
}
