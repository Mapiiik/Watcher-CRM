<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Enum;

use App\Model\Enum\ContractPrintType;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Model\Enum\ContractPrintType Test Case
 *
 * Which document is being drawn up decides what the operator has to fill in, so these answers are
 * what the print form is validated against. Getting one of them wrong either asks for a version that
 * has nothing to do with the document, or lets a document through without the version it names.
 */
#[UsesClass(ContractPrintType::class)]
class ContractPrintTypeTest extends TestCase
{
    /**
     * Every document is offered under a name, and the value stored for it is the one the form posts.
     *
     * @return void
     * @link \App\Model\Enum\ContractPrintType::label()
     * @link \App\Model\Enum\Trait\EnumOptionsTrait::options()
     */
    public function testEveryDocumentIsOfferedUnderAName(): void
    {
        $options = ContractPrintType::options();

        $this->assertSame(
            array_map(
                fn(ContractPrintType $type): string => $type->value,
                ContractPrintType::cases(),
            ),
            array_keys($options),
        );
        $this->assertNotContains('', $options);
    }

    /**
     * The documents that put a version into effect are the ones that ask for it.
     *
     * @return void
     * @link \App\Model\Enum\ContractPrintType::requiresContractVersionToBeExecuted()
     */
    public function testTheDocumentsThatPutAVersionIntoEffectAskForIt(): void
    {
        $this->assertSame(
            [
                ContractPrintType::ContractNew,
                ContractPrintType::ContractNewX,
                ContractPrintType::ContractAmendment,
                ContractPrintType::HandoverInstallation,
            ],
            $this->typesWhere(fn(ContractPrintType $type): bool => $type->requiresContractVersionToBeExecuted()),
        );
    }

    /**
     * The documents that end a contract are the ones that ask which version is being ended, and they
     * ask for its number under the same conditions - the two go together on the form.
     *
     * @return void
     * @link \App\Model\Enum\ContractPrintType::requiresContractVersionToBeTerminated()
     * @link \App\Model\Enum\ContractPrintType::requiresContractNumberToBeTerminated()
     */
    public function testTheDocumentsThatEndAContractAskWhichVersionAndUnderWhatNumber(): void
    {
        $ending = [
            ContractPrintType::ContractNewX,
            ContractPrintType::ContractTermination,
            ContractPrintType::HandoverUninstallation,
        ];

        $this->assertSame(
            $ending,
            $this->typesWhere(fn(ContractPrintType $type): bool => $type->requiresContractVersionToBeTerminated()),
        );
        $this->assertSame(
            $ending,
            $this->typesWhere(fn(ContractPrintType $type): bool => $type->requiresContractNumberToBeTerminated()),
        );
    }

    /**
     * Only an amendment has a day it takes effect on. It is the one document that changes a contract
     * already running, so the date is what says from when.
     *
     * @return void
     * @link \App\Model\Enum\ContractPrintType::requiresEffectiveDateOfTheAmendment()
     */
    public function testOnlyAnAmendmentHasADayItTakesEffectOn(): void
    {
        $this->assertSame(
            [ContractPrintType::ContractAmendment],
            $this->typesWhere(fn(ContractPrintType $type): bool => $type->requiresEffectiveDateOfTheAmendment()),
        );
    }

    /**
     * The handover protocols are the documents filled in at the customer's, and the only ones carrying
     * what was set up there - the access point and the credentials the connection runs on.
     *
     * @return void
     * @link \App\Model\Enum\ContractPrintType::isHandoverProtocol()
     */
    public function testTheHandoverProtocolsAreTheOnesCarryingWhatWasSetUp(): void
    {
        $this->assertSame(
            [
                ContractPrintType::HandoverInstallation,
                ContractPrintType::HandoverUninstallation,
            ],
            $this->typesWhere(fn(ContractPrintType $type): bool => $type->isHandoverProtocol()),
        );
    }

    /**
     * The document types the given question is answered yes for, in the order they are declared.
     *
     * @param callable(\App\Model\Enum\ContractPrintType): bool $question Question to ask of each type.
     * @return array<\App\Model\Enum\ContractPrintType>
     */
    private function typesWhere(callable $question): array
    {
        return array_values(array_filter(ContractPrintType::cases(), $question));
    }
}
