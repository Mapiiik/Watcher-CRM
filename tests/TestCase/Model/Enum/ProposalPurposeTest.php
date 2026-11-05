<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Enum;

use App\Model\Enum\ContractDocumentType;
use App\Model\Enum\ProposalPurpose;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * App\Model\Enum\ProposalPurpose Test Case
 */
#[CoversClass(ProposalPurpose::class)]
class ProposalPurposeTest extends TestCase
{
    /**
     * An exchange of borrowed equipments belongs to no version, bills nothing and sets no term. It
     * moves equipment, as a new contract does, and its protocol is what the customer signs.
     *
     * @return void
     */
    public function testAnExchangeHandsEquipmentOverAndNothingElse(): void
    {
        $exchange = ProposalPurpose::EquipmentChange;

        $this->assertFalse($exchange->needsAVersion());
        $this->assertFalse($exchange->changesBillings());
        $this->assertFalse($exchange->mayChangeTheTerm());
        $this->assertFalse($exchange->mayStartAVersion());
        $this->assertFalse($exchange->ends());
        $this->assertTrue($exchange->movesEquipment());
        $this->assertSame(ContractDocumentType::HandoverExchange, $exchange->suggests(false));
        $this->assertTrue($exchange->isSignedBy(ContractDocumentType::HandoverExchange));
        $this->assertFalse($exchange->isSignedBy(ContractDocumentType::HandoverInstallation));
    }

    /**
     * Elsewhere a signed protocol does not stand in for the agreement it goes with.
     *
     * @return void
     */
    public function testElsewhereOnlyTheAgreementIsSigned(): void
    {
        foreach ([ProposalPurpose::NewContract, ProposalPurpose::ServiceChange, ProposalPurpose::Termination] as $purpose) {
            $this->assertTrue($purpose->needsAVersion());
            $this->assertTrue($purpose->changesBillings());
            $this->assertFalse($purpose->isSignedBy(ContractDocumentType::HandoverExchange));
            $this->assertTrue($purpose->isSignedBy(ContractDocumentType::ContractNewX));
        }

        $this->assertTrue(ProposalPurpose::NewContract->movesEquipment());
        $this->assertFalse(ProposalPurpose::ServiceChange->movesEquipment());
        $this->assertFalse(ProposalPurpose::Termination->movesEquipment());
    }

    /**
     * The form asks for the day only where it cannot be worked out.
     *
     * @param \App\Model\Enum\ProposalPurpose $purpose The purpose.
     * @param bool $keepsVersions Whether the contract keeps versions.
     * @param bool $hasVersion Whether a version has been chosen.
     * @param bool $asks Whether the day is asked for.
     * @return void
     */
    #[DataProvider('whereTheDayIsAskedFor')]
    public function testTheDayIsAskedForOnlyWhereItCannotBeWorkedOut(
        ProposalPurpose $purpose,
        bool $keepsVersions,
        bool $hasVersion,
        bool $asks,
    ): void {
        $this->assertSame($asks, $purpose->asksForTheDay($keepsVersions, $hasVersion));
    }

    /**
     * @return array<string, array{\App\Model\Enum\ProposalPurpose, bool, bool, bool}>
     */
    public static function whereTheDayIsAskedFor(): array
    {
        return [
            'a change says its own' => [ProposalPurpose::ServiceChange, true, true, true],
            'a new contract takes its version\'s' => [ProposalPurpose::NewContract, true, true, false],
            'a new contract with no version says its own' => [ProposalPurpose::NewContract, true, false, true],
            'an ending works it out' => [ProposalPurpose::Termination, true, true, false],
            'an ending of a contract without versions too' => [ProposalPurpose::Termination, false, false, false],
            'without versions it is said' => [ProposalPurpose::ServiceChange, false, false, true],
            'a new contract without versions as well' => [ProposalPurpose::NewContract, false, false, true],
            'an exchange never' => [ProposalPurpose::EquipmentChange, true, true, false],
            'an exchange without versions neither' => [ProposalPurpose::EquipmentChange, false, false, false],
        ];
    }
}
