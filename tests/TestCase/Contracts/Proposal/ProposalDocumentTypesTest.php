<?php
declare(strict_types=1);

namespace App\Test\TestCase\Contracts\Proposal;

use App\Contracts\Proposal\ProposalDocumentTypes;
use App\Model\Entity\ContractVersionProposal;
use App\Model\Enum\ContractPrintType;
use App\Model\Enum\ProposalPurpose;
use Cake\I18n\Date;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\Contracts\Proposal\ProposalDocumentTypes Test Case
 */
#[CoversClass(ProposalDocumentTypes::class)]
class ProposalDocumentTypesTest extends TestCase
{
    /**
     * A proposal that says what the test is about and nothing else.
     *
     * @param array<string, mixed> $says What it says.
     * @return \App\Model\Entity\ContractVersionProposal
     */
    private function proposal(array $says = []): ContractVersionProposal
    {
        return new ContractVersionProposal($says + [
            'id' => 'a1b2c3d4-0000-4000-8000-000000000001',
            'purpose' => ProposalPurpose::NewContract,
            'effective_from' => new Date('2026-10-01'),
            'changes' => [],
            'terminates_contract_version_id' => null,
        ]);
    }

    /**
     * What a proposal may be printed as.
     *
     * @param \App\Model\Entity\ContractVersionProposal $proposal The proposal.
     * @param bool $has_equipment Whether the contract has equipment at all.
     * @param bool $concluded Whether the version has been concluded.
     * @return array<string>
     */
    private function offered(
        ContractVersionProposal $proposal,
        bool $has_equipment = true,
        bool $concluded = true,
    ): array {
        return array_map(
            fn(ContractPrintType $type): string => $type->value,
            (new ProposalDocumentTypes())->for($proposal, $has_equipment, $concluded),
        );
    }

    /**
     * What an ending looks like: the version stops and the contract is terminated, on the same day.
     *
     * @return array<string, mixed>
     */
    private static function ending(): array
    {
        return [
            'purpose' => ProposalPurpose::Termination,
            'changes' => [
                'version' => ['valid_until' => '2026-09-30'],
                'contract' => ['termination_date' => '2026-09-30'],
            ],
        ];
    }

    /**
     * The empty proposal behind a new contract's papers offers the contract itself, its summary and
     * the installation protocol - and nothing that would replace or end anything.
     *
     * @return void
     */
    public function testTheEmptyProposalOfANewContract(): void
    {
        $offered = $this->offered($this->proposal(), concluded: false);

        $this->assertContains(ContractPrintType::ContractNew->value, $offered);
        $this->assertContains(ContractPrintType::ContractSummary->value, $offered);
        $this->assertContains(ContractPrintType::HandoverInstallation->value, $offered);

        $this->assertNotContains(ContractPrintType::ContractNewX->value, $offered);
        $this->assertNotContains(ContractPrintType::ContractTermination->value, $offered);
        $this->assertNotContains(ContractPrintType::ContractAmendment->value, $offered);
        $this->assertNotContains(ContractPrintType::HandoverUninstallation->value, $offered);
    }

    /**
     * A contract that replaces an earlier version is offered exactly where there is one to replace.
     *
     * @return void
     */
    public function testReplacingAnEarlierVersion(): void
    {
        $replacing = $this->proposal([
            'terminates_contract_version_id' => 'a1b2c3d4-0000-4000-8000-000000000002',
        ]);

        $offered = $this->offered($replacing);

        $this->assertContains(ContractPrintType::ContractNewX->value, $offered);
        $this->assertContains(ContractPrintType::HandoverUninstallation->value, $offered);
        $this->assertNotContains(ContractPrintType::ContractNew->value, $offered);
    }

    /**
     * An agreement to terminate is offered where the proposal ends the contract, and not otherwise.
     *
     * @return void
     */
    public function testEndingTheContract(): void
    {
        $this->assertContains(
            ContractPrintType::ContractTermination->value,
            $this->offered($this->proposal(self::ending())),
        );
        $this->assertNotContains(
            ContractPrintType::ContractTermination->value,
            $this->offered($this->proposal()),
        );
    }

    /**
     * An amendment wants papers meant as a change, and a contract somebody concluded. Papers meant
     * as a new contract are not an amendment however far along the version is, and there is
     * nothing to amend before anybody signs.
     *
     * @return void
     */
    public function testAmendingWantsAChangeToAConcludedVersion(): void
    {
        $change = ['purpose' => ProposalPurpose::ServiceChange];

        $this->assertContains(
            ContractPrintType::ContractAmendment->value,
            $this->offered($this->proposal($change), concluded: true),
        );
        $this->assertNotContains(
            ContractPrintType::ContractAmendment->value,
            $this->offered($this->proposal($change), concluded: false),
        );
        $this->assertNotContains(
            ContractPrintType::ContractAmendment->value,
            $this->offered($this->proposal(), concluded: true),
        );
    }

    /**
     * The contract is what says which equipment the customer has, so a service without equipment
     * has no protocol to hand over either way.
     *
     * @return void
     */
    public function testAServiceWithoutEquipmentHasNoProtocols(): void
    {
        $offered = $this->offered($this->proposal(self::ending()), has_equipment: false);

        $this->assertNotContains(ContractPrintType::HandoverInstallation->value, $offered);
        $this->assertNotContains(ContractPrintType::HandoverUninstallation->value, $offered);
    }

    /**
     * The uninstallation protocol wants something to end, because it names the contract being
     * terminated. Swapping a box on a running contract is a new version, not a protocol of its own.
     *
     * @return void
     */
    public function testTheUninstallationProtocolWantsAnEnding(): void
    {
        $this->assertNotContains(
            ContractPrintType::HandoverUninstallation->value,
            $this->offered($this->proposal()),
        );
        $this->assertContains(
            ContractPrintType::HandoverUninstallation->value,
            $this->offered($this->proposal(self::ending())),
        );
    }

    /**
     * The summary binds nobody, so it is always on offer.
     *
     * @return void
     */
    public function testTheSummaryIsAlwaysOnOffer(): void
    {
        foreach ([true, false] as $concluded) {
            $this->assertContains(
                ContractPrintType::ContractSummary->value,
                $this->offered($this->proposal(), concluded: $concluded),
            );
        }
    }

    /**
     * What is offered is what may be printed, and nothing else.
     *
     * @return void
     */
    public function testWhatIsNotOfferedIsNotAllowed(): void
    {
        $types = new ProposalDocumentTypes();
        $proposal = $this->proposal();

        $this->assertTrue($types->allows(ContractPrintType::ContractNew, $proposal, true, true));
        $this->assertFalse($types->allows(ContractPrintType::ContractNewX, $proposal, true, true));
    }
}
