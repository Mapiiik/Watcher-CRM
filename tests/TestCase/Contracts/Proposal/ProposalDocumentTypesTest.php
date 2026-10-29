<?php
declare(strict_types=1);

namespace App\Test\TestCase\Contracts\Proposal;

use App\Contracts\Proposal\ProposalDocumentTypes;
use App\Model\Entity\ContractProposal;
use App\Model\Enum\ContractDocumentType;
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
     * @return \App\Model\Entity\ContractProposal
     */
    private function proposal(array $says = []): ContractProposal
    {
        return new ContractProposal($says + [
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
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @param bool $has_equipment Whether the contract has equipment at all.
     * @param bool $concluded Whether the version has been concluded.
     * @return array<string>
     */
    private function offered(
        ContractProposal $proposal,
        bool $has_equipment = true,
        bool $concluded = true,
    ): array {
        return array_map(
            fn(ContractDocumentType $type): string => $type->value,
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

        $this->assertContains(ContractDocumentType::ContractNew->value, $offered);
        $this->assertContains(ContractDocumentType::ContractSummary->value, $offered);
        $this->assertContains(ContractDocumentType::HandoverInstallation->value, $offered);

        $this->assertNotContains(ContractDocumentType::ContractNewX->value, $offered);
        $this->assertNotContains(ContractDocumentType::ContractTermination->value, $offered);
        $this->assertNotContains(ContractDocumentType::ContractAmendment->value, $offered);
        $this->assertNotContains(ContractDocumentType::HandoverUninstallation->value, $offered);
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

        $this->assertContains(ContractDocumentType::ContractNewX->value, $offered);
        $this->assertContains(ContractDocumentType::HandoverUninstallation->value, $offered);
        $this->assertNotContains(ContractDocumentType::ContractNew->value, $offered);
    }

    /**
     * An agreement to terminate is offered where the proposal ends the contract, and not otherwise.
     *
     * @return void
     */
    public function testEndingTheContract(): void
    {
        $this->assertContains(
            ContractDocumentType::ContractTermination->value,
            $this->offered($this->proposal(self::ending())),
        );
        $this->assertNotContains(
            ContractDocumentType::ContractTermination->value,
            $this->offered($this->proposal()),
        );
    }

    /**
     * The papers the other side writes belong to an ending and to nothing else: the customer's own
     * notice, or the certificate where there is nobody left to write one.
     *
     * @return void
     */
    public function testWhatComesFromTheOtherSideBelongsToAnEnding(): void
    {
        $theirs = [
            ContractDocumentType::TerminationNotice->value,
            ContractDocumentType::DeathCertificate->value,
        ];

        foreach ($theirs as $paper) {
            $this->assertContains($paper, $this->offered($this->proposal(self::ending())));
            $this->assertNotContains($paper, $this->offered($this->proposal()));
        }
    }

    /**
     * They are offered to be filed and nothing more. Nobody here draws them, so asking to print one
     * is refused - and neither is ever owed, because an ending has one of them and which one is not
     * ours to say beforehand.
     *
     * @return void
     */
    public function testWhatComesFromTheOtherSideIsNeitherPrintedNorOwed(): void
    {
        $papers = new ProposalDocumentTypes();

        // What is owed is read off the snapshot, so this one carries the least it may.
        $ending = $this->proposal(self::ending() + [
            'snapshot' => [
                'contract' => ['service_type' => ['have_equipments' => true]],
                'customer' => [],
                'version' => ['conclusion_date' => '2026-01-01'],
                'billings' => [],
            ],
        ]);

        foreach ([ContractDocumentType::TerminationNotice, ContractDocumentType::DeathCertificate] as $paper) {
            $this->assertFalse(
                $papers->allows($paper, $ending, true, true),
                $paper->value . ' was offered to be printed, and nobody here draws it.',
            );
            $this->assertArrayNotHasKey($paper->value, $papers->expectedOf($ending));
        }

        // What we do draw for an ending is still owed, so nothing was swept out with them.
        $this->assertArrayHasKey(
            ContractDocumentType::ContractTermination->value,
            $papers->expectedOf($ending),
        );
    }

    /**
     * The customer's notice or a death certificate is what ends the contract, so with one of them
     * on file the agreement may still be drawn but is not missing.
     *
     * @return void
     */
    public function testAnEndingWithTheOtherSidesPaperOnFileOwesNoAgreement(): void
    {
        $papers = new ProposalDocumentTypes();
        $ending = $this->proposal(self::ending() + [
            'snapshot' => [
                'contract' => ['service_type' => ['have_equipments' => false]],
                'customer' => [],
                'version' => ['conclusion_date' => '2026-01-01'],
                'billings' => [],
            ],
        ]);
        $agreement = ContractDocumentType::ContractTermination->value;

        $this->assertTrue($papers->expectedOf($ending)[$agreement]);
        $this->assertTrue($papers->expectedOf($ending, [ContractDocumentType::Other])[$agreement]);

        foreach ([ContractDocumentType::TerminationNotice, ContractDocumentType::DeathCertificate] as $theirs) {
            $this->assertFalse(
                $papers->expectedOf($ending, [$theirs])[$agreement],
                'The agreement was still owed with ' . $theirs->value . ' on file.',
            );
        }
    }

    /**
     * A proposal drawn up today, whose snapshot says the protocols are owed.
     *
     * @param array<string, mixed> $says What it says.
     * @param list<array<string, mixed>> $borrowed What the snapshot says the contract lends.
     * @return \App\Model\Entity\ContractProposal
     */
    private function drawnUpToday(array $says = [], array $borrowed = []): ContractProposal
    {
        return $this->proposal($says + [
            'snapshot' => [
                'contract' => ['service_type' => ['have_equipments' => true]],
                'customer' => [],
                'version' => ['conclusion_date' => '2026-01-01'],
                'billings' => [],
                'borrowed_equipments' => $borrowed,
                'handover_protocols_owed' => true,
            ],
        ]);
    }

    /**
     * Which handover protocols a proposal owes, by type.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @param array<\App\Model\Enum\ContractDocumentType> $on_file What is on file.
     * @return array<string, bool>
     */
    private function protocolsOf(ContractProposal $proposal, array $on_file = []): array
    {
        return array_intersect_key(
            (new ProposalDocumentTypes())->expectedOf($proposal, $on_file),
            array_flip([
                ContractDocumentType::HandoverInstallation->value,
                ContractDocumentType::HandoverUninstallation->value,
                ContractDocumentType::HandoverExchange->value,
            ]),
        );
    }

    /**
     * A new contract owes the installation protocol where it lends anything, whether the
     * equipment was put on the contract before the papers or is added by their lines.
     *
     * @return void
     */
    public function testANewContractOwesTheInstallationProtocolWhereItLends(): void
    {
        $installation = ContractDocumentType::HandoverInstallation->value;
        $lend = ['id' => 'lend', 'move' => 'lend', 'equipment_type_id' => 'type-1'];

        $this->assertTrue($this->protocolsOf($this->drawnUpToday([], [['id' => 'kept']]))[$installation]);
        $this->assertTrue($this->protocolsOf($this->drawnUpToday(['changes' => ['equipments' => [$lend]]]))[$installation]);
        $this->assertFalse($this->protocolsOf($this->drawnUpToday())[$installation]);
    }

    /**
     * A contract replacing an earlier one owes the protocol of what changes hands: what it adds,
     * what it ends, or one protocol of the exchange where it does both. Where nothing changes
     * hands the installation protocol may still be drawn as a list of what the customer has.
     *
     * @return void
     */
    public function testAReplacingContractOwesTheProtocolOfWhatChangesHands(): void
    {
        $replacing = ['terminates_contract_version_id' => 'a1b2c3d4-0000-4000-8000-000000000002'];
        $lend = ['id' => 'lend', 'move' => 'lend', 'equipment_type_id' => 'type-1'];
        $end = ['id' => 'end', 'move' => 'return', 'borrowed_equipment_id' => 'old'];
        $old = [['id' => 'old']];

        $this->assertSame(
            [
                ContractDocumentType::HandoverInstallation->value => false,
                ContractDocumentType::HandoverUninstallation->value => false,
            ],
            $this->protocolsOf($this->drawnUpToday($replacing, $old)),
        );
        $this->assertSame(
            [
                ContractDocumentType::HandoverInstallation->value => true,
                ContractDocumentType::HandoverUninstallation->value => false,
            ],
            $this->protocolsOf($this->drawnUpToday($replacing + ['changes' => ['equipments' => [$lend]]], $old)),
        );
        $this->assertSame(
            [
                ContractDocumentType::HandoverInstallation->value => false,
                ContractDocumentType::HandoverUninstallation->value => true,
            ],
            $this->protocolsOf($this->drawnUpToday($replacing + ['changes' => ['equipments' => [$end]]], $old)),
        );
        $this->assertSame(
            [ContractDocumentType::HandoverExchange->value => true],
            $this->protocolsOf($this->drawnUpToday(
                $replacing + ['changes' => ['equipments' => [$end, $lend]]],
                $old,
            )),
        );
    }

    /**
     * Ending the contract takes back what it lends, so the uninstallation protocol is owed there.
     * Ending one version while the contract runs on takes nothing back.
     *
     * @return void
     */
    public function testEndingTheContractOwesTheUninstallationProtocolWhereSomethingIsLent(): void
    {
        $uninstallation = ContractDocumentType::HandoverUninstallation->value;
        $versionOnly = [
            'purpose' => ProposalPurpose::Termination,
            'changes' => ['version' => ['valid_until' => '2026-09-30']],
        ];

        $this->assertTrue($this->protocolsOf($this->drawnUpToday(self::ending(), [['id' => 'kept']]))[$uninstallation]);
        $this->assertFalse($this->protocolsOf($this->drawnUpToday(self::ending()))[$uninstallation]);
        $this->assertFalse($this->protocolsOf($this->drawnUpToday($versionOnly, [['id' => 'kept']]))[$uninstallation]);
    }

    /**
     * A proposal drawn up before the protocols were owed keeps the rules it was drawn up under.
     *
     * @return void
     */
    public function testAnOlderProposalOwesNoProtocol(): void
    {
        $older = $this->proposal(self::ending() + [
            'snapshot' => [
                'contract' => ['service_type' => ['have_equipments' => true]],
                'customer' => [],
                'version' => ['conclusion_date' => '2026-01-01'],
                'billings' => [],
                'borrowed_equipments' => [['id' => 'kept']],
            ],
        ]);

        $this->assertSame(
            [ContractDocumentType::HandoverUninstallation->value => false],
            $this->protocolsOf($older),
        );
    }

    /**
     * An amendment changes what is billed for and hands no equipment over, so it offers no
     * handover protocol.
     *
     * @return void
     */
    public function testAnAmendmentHandsNothingOver(): void
    {
        $offered = $this->offered($this->proposal(['purpose' => ProposalPurpose::ServiceChange]));

        $this->assertNotContains(ContractDocumentType::HandoverInstallation->value, $offered);
        $this->assertNotContains(ContractDocumentType::HandoverUninstallation->value, $offered);
    }

    /**
     * Whatever else was handed over - a power of attorney, the owner of the building agreeing to
     * the cabling - belongs to no purpose in particular, so it is offered under every one of them.
     * Filed and nothing more, the same as what the other side writes: never printed, never owed.
     *
     * One type rather than a list of them, because the list has no end. Which paper each one is,
     * its own file name says.
     *
     * @return void
     */
    public function testWhateverElseWasHandedOverIsOfferedUnderEveryPurpose(): void
    {
        $papers = new ProposalDocumentTypes();
        $other = ContractDocumentType::Other;

        // What is owed is read off the snapshot, so each of these carries the least it may.
        $photographed = [
            'snapshot' => [
                'contract' => ['service_type' => ['have_equipments' => true]],
                'customer' => [],
                'version' => ['conclusion_date' => '2026-01-01'],
                'billings' => [],
            ],
        ];

        $anyPurpose = [
            $this->proposal($photographed),
            $this->proposal(self::ending() + $photographed),
            $this->proposal(['purpose' => ProposalPurpose::ServiceChange] + $photographed),
        ];

        foreach ($anyPurpose as $any) {
            $this->assertContains($other->value, $this->offered($any));
            $this->assertFalse(
                $papers->allows($other, $any, true, true),
                'Something nobody here draws was offered to be printed.',
            );
            $this->assertArrayNotHasKey($other->value, $papers->expectedOf($any));
        }
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
            ContractDocumentType::ContractAmendment->value,
            $this->offered($this->proposal($change), concluded: true),
        );
        $this->assertNotContains(
            ContractDocumentType::ContractAmendment->value,
            $this->offered($this->proposal($change), concluded: false),
        );
        $this->assertNotContains(
            ContractDocumentType::ContractAmendment->value,
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

        $this->assertNotContains(ContractDocumentType::HandoverInstallation->value, $offered);
        $this->assertNotContains(ContractDocumentType::HandoverUninstallation->value, $offered);
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
            ContractDocumentType::HandoverUninstallation->value,
            $this->offered($this->proposal()),
        );
        $this->assertContains(
            ContractDocumentType::HandoverUninstallation->value,
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
                ContractDocumentType::ContractSummary->value,
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

        $this->assertTrue($types->allows(ContractDocumentType::ContractNew, $proposal, true, true));
        $this->assertFalse($types->allows(ContractDocumentType::ContractNewX, $proposal, true, true));
    }
}
