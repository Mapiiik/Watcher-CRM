<?php
declare(strict_types=1);

namespace App\Test\TestCase\Contracts\Proposal;

use App\Contracts\Proposal\ProposalDrafting;
use App\Model\Entity\ContractProposal;
use App\Model\Enum\ProposalPurpose;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * App\Contracts\Proposal\ProposalDrafting Test Case
 *
 * What stops papers from being drawn up is said on the field the operator has to look at. The way
 * through is covered with the records it reads, in ContractProposalsControllerTest.
 */
#[CoversClass(ProposalDrafting::class)]
class ProposalDraftingTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
        'app.AccountingProfiles',
        'app.Customers',
        'app.Countries',
        'app.Addresses',
        'app.Commissions',
        'app.ContractStates',
        'app.ServiceTypes',
        'app.Contracts',
        'app.ContractVersions',
    ];

    /**
     * A proposal that has not been saved yet.
     *
     * @return \App\Model\Entity\ContractProposal
     */
    private function proposal(): ContractProposal
    {
        /** @var \App\Model\Entity\ContractProposal */
        return $this->getTableLocator()->get('ContractProposals')->newEmptyEntity();
    }

    /**
     * What is missing is said on the field it is missing from.
     *
     * @param array<string, mixed> $data What the form sent.
     * @param string $field The field that has to say so.
     * @return void
     */
    #[DataProvider('whatIsMissing')]
    public function testWhatIsMissingIsSaidOnItsField(array $data, string $field): void
    {
        $filled = (new ProposalDrafting())->fill($this->proposal(), $data);

        $this->assertArrayHasKey($field, $filled->getErrors());
        $this->assertNull($filled->get('snapshot'), 'No snapshot is taken of a proposal that cannot go on.');
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function whatIsMissing(): array
    {
        $ending = ProposalPurpose::Termination->value;

        return [
            'an ending with no last day' => [['purpose' => $ending], 'ends_on'],
            'an ending with a last day nobody can read' => [
                ['purpose' => $ending, 'ends_on' => 'next spring'],
                'ends_on',
            ],
            'no contract' => [['purpose' => ProposalPurpose::NewContract->value], 'contract_id'],
        ];
    }

    /**
     * Drawn again, a half-filled form is expected rather than wrong.
     *
     * @return void
     */
    public function testNothingIsHeldAgainstAFormBeingDrawnAgain(): void
    {
        $filled = (new ProposalDrafting())->fill(
            $this->proposal(),
            ['purpose' => ProposalPurpose::Termination->value],
            redrawing: true,
        );

        $this->assertSame([], $filled->getErrors());
    }

    /**
     * A form that never said what it is for draws up a new contract, the common case.
     *
     * @return void
     */
    public function testAFormThatSaysNothingDrawsUpANewContract(): void
    {
        $filled = (new ProposalDrafting())->fill($this->proposal(), [], redrawing: true);

        $this->assertSame(ProposalPurpose::NewContract, $filled->get('purpose'));
    }
}
