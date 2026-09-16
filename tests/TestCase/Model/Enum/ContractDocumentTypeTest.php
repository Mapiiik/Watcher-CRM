<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Enum;

use App\Model\Enum\ContractDocumentType;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Model\Enum\ContractDocumentType Test Case
 *
 * Which document is being drawn up decides what the operator has to fill in, so these answers are
 * what the print form is validated against. Getting one of them wrong either asks for a version that
 * has nothing to do with the document, or lets a document through without the version it names.
 */
#[UsesClass(ContractDocumentType::class)]
class ContractDocumentTypeTest extends TestCase
{
    /**
     * Two of them are papers the other side writes: the customer's own notice of termination and
     * the certificate where there is nobody left to write one. Everything else is drawn here, and
     * the difference is what keeps them out of the printing.
     *
     * @return void
     * @link \App\Model\Enum\ContractDocumentType::canBeGenerated()
     */
    public function testThePapersTheOtherSideWritesAreNotDrawnHere(): void
    {
        $theirs = array_values(array_filter(
            ContractDocumentType::cases(),
            fn(ContractDocumentType $type): bool => !$type->canBeGenerated(),
        ));

        $this->assertSame(
            [ContractDocumentType::TerminationNotice, ContractDocumentType::DeathCertificate],
            $theirs,
        );
    }

    /**
     * Every document is offered under a name, and the value stored for it is the one the form posts.
     *
     * @return void
     * @link \App\Model\Enum\ContractDocumentType::label()
     * @link \App\Model\Enum\Trait\EnumOptionsTrait::options()
     */
    public function testEveryDocumentIsOfferedUnderAName(): void
    {
        $options = ContractDocumentType::options();

        $this->assertSame(
            array_map(
                fn(ContractDocumentType $type): string => $type->value,
                ContractDocumentType::cases(),
            ),
            array_keys($options),
        );
        $this->assertNotContains('', $options);
    }

    /**
     * The documents that put a version into effect are the ones that ask for it.
     *
     * @return void
     * @link \App\Model\Enum\ContractDocumentType::requiresContractVersionToBeExecuted()
     */
    public function testTheDocumentsThatPutAVersionIntoEffectAskForIt(): void
    {
        $this->assertSame(
            [
                ContractDocumentType::ContractNew,
                ContractDocumentType::ContractNewX,
                ContractDocumentType::ContractAmendment,
                ContractDocumentType::ContractSummary,
                ContractDocumentType::HandoverInstallation,
            ],
            $this->typesWhere(fn(ContractDocumentType $type): bool => $type->requiresContractVersionToBeExecuted()),
        );
    }

    /**
     * The documents that end a contract are the ones that ask which version is being ended, and they
     * ask for its number under the same conditions - the two go together on the form.
     *
     * @return void
     * @link \App\Model\Enum\ContractDocumentType::requiresContractVersionToBeTerminated()
     * @link \App\Model\Enum\ContractDocumentType::requiresContractNumberToBeTerminated()
     */
    public function testTheDocumentsThatEndAContractAskWhichVersionAndUnderWhatNumber(): void
    {
        $ending = [
            ContractDocumentType::ContractNewX,
            ContractDocumentType::ContractTermination,
            ContractDocumentType::HandoverUninstallation,
        ];

        $this->assertSame(
            $ending,
            $this->typesWhere(fn(ContractDocumentType $type): bool => $type->requiresContractVersionToBeTerminated()),
        );
        $this->assertSame(
            $ending,
            $this->typesWhere(fn(ContractDocumentType $type): bool => $type->requiresContractNumberToBeTerminated()),
        );
    }

    /**
     * Only an amendment has a day it takes effect on. It is the one document that changes a contract
     * already running, so the date is what says from when.
     *
     * @return void
     * @link \App\Model\Enum\ContractDocumentType::requiresEffectiveDateOfTheAmendment()
     */
    public function testOnlyAnAmendmentHasADayItTakesEffectOn(): void
    {
        $this->assertSame(
            [ContractDocumentType::ContractAmendment],
            $this->typesWhere(fn(ContractDocumentType $type): bool => $type->requiresEffectiveDateOfTheAmendment()),
        );
    }

    /**
     * The handover protocols are the documents filled in at the customer's, and the only ones carrying
     * what was set up there - the access point and the credentials the connection runs on.
     *
     * @return void
     * @link \App\Model\Enum\ContractDocumentType::isHandoverProtocol()
     */
    public function testTheHandoverProtocolsAreTheOnesCarryingWhatWasSetUp(): void
    {
        $this->assertSame(
            [
                ContractDocumentType::HandoverInstallation,
                ContractDocumentType::HandoverUninstallation,
            ],
            $this->typesWhere(fn(ContractDocumentType $type): bool => $type->isHandoverProtocol()),
        );
    }

    /**
     * The document types the given question is answered yes for, in the order they are declared.
     *
     * @param callable(\App\Model\Enum\ContractDocumentType): bool $question Question to ask of each type.
     * @return array<\App\Model\Enum\ContractDocumentType>
     */
    private function typesWhere(callable $question): array
    {
        return array_values(array_filter(ContractDocumentType::cases(), $question));
    }
}
