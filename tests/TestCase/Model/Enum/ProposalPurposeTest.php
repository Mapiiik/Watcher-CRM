<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Enum;

use App\Model\Enum\ContractDocumentType;
use App\Model\Enum\ProposalPurpose;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

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
}
