<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service\ContractPrint;

use App\Model\Entity\Contract;
use App\Model\Entity\ContractVersion;
use App\Model\Entity\ContractVersionProposal;
use App\Model\Entity\ServiceType;
use App\Model\Enum\ContractPrintType;
use App\Model\Enum\ProposalPurpose;
use App\Service\ContractPrint\ContractPrintData;
use App\Service\ContractPrint\ContractPrintValidator;
use Cake\I18n\Date;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Service\ContractPrint\ContractPrintValidator Test Case
 *
 * Very little is asked here any more, and that is the point. Whether the contract is ready for
 * papers is asked once when the proposal is drawn up, and what a proposal may say about ending
 * things is a rule of the proposals table - so those are tested where they now live, in
 * ContractVersionProposalsTableTest. What is left is that a document is printed from a proposal at
 * all, and that it is one that proposal may be printed as.
 */
#[UsesClass(ContractPrintValidator::class)]
class ContractPrintValidatorTest extends TestCase
{
    /**
     * A contract whose service type has no equipment, so no handover protocol is on offer.
     *
     * @return \App\Model\Entity\Contract
     */
    private static function contract(): Contract
    {
        $contract = new Contract([
            'id' => 'a1b2c3d4-0000-4000-8000-000000000001',
            'number' => 'C-1',
        ]);

        $contract->service_type = new ServiceType([
            'id' => 'a1b2c3d4-0000-4000-8000-000000000002',
            'have_contract_versions' => true,
            'have_equipments' => false,
            'have_ip_addresses' => false,
            'have_radius_accounts' => false,
            'normally_with_borrowed_equipment' => false,
        ]);

        return $contract;
    }

    /**
     * A version, concluded or not.
     *
     * @param bool $concluded Whether anybody signed it.
     * @return \App\Model\Entity\ContractVersion
     */
    private static function version(bool $concluded = true): ContractVersion
    {
        return new ContractVersion([
            'id' => 'a1b2c3d4-0000-4000-8000-000000000003',
            'valid_from' => new Date('2026-01-01'),
            'valid_until' => null,
            'obligation_until' => null,
            'conclusion_date' => $concluded ? new Date('2026-01-01') : null,
        ]);
    }

    /**
     * A proposal that changes nothing, which is the ordinary one.
     *
     * @param array<string, mixed> $says What it says beyond that.
     * @return \App\Model\Entity\ContractVersionProposal
     */
    private static function proposal(array $says = []): ContractVersionProposal
    {
        return new ContractVersionProposal($says + [
            'id' => 'a1b2c3d4-0000-4000-8000-000000000004',
            'purpose' => ProposalPurpose::NewContract,
            'effective_from' => new Date('2026-10-01'),
            'changes' => [],
            'terminates_contract_version_id' => null,
        ]);
    }

    /**
     * What the validator makes of the given document.
     *
     * @param \App\Model\Enum\ContractPrintType $type Which document.
     * @param \App\Model\Entity\ContractVersionProposal|null $proposal The proposal, where there is one.
     * @param bool $concluded Whether the version has been concluded.
     * @return array<string, array<string>>
     */
    private static function errorsFor(
        ContractPrintType $type,
        ?ContractVersionProposal $proposal,
        bool $concluded = true,
    ): array {
        $data = new ContractPrintData($type, self::contract(), self::version($concluded), null);
        $data->proposal = $proposal;

        return (new ContractPrintValidator())->validate($data, []);
    }

    /**
     * Without a proposal there is nothing to print from, because the document would otherwise be
     * assembled from whatever the records happen to say today.
     *
     * @return void
     */
    public function testADocumentWithoutAProposalIsRefused(): void
    {
        $errors = self::errorsFor(ContractPrintType::ContractNew, null);

        $this->assertArrayHasKey('proposal_id', $errors);
    }

    /**
     * With one, an ordinary contract prints.
     *
     * @return void
     */
    public function testAProposalIsEnough(): void
    {
        $this->assertSame([], self::errorsFor(ContractPrintType::ContractNew, self::proposal()));
    }

    /**
     * The offered list of documents cannot be stepped around by typing a URL: a proposal that
     * replaces nothing cannot be printed as a contract that replaces something.
     *
     * @return void
     */
    public function testADocumentTheProposalCannotBePrintedAsIsRefused(): void
    {
        $errors = self::errorsFor(ContractPrintType::ContractNewX, self::proposal());

        $this->assertArrayHasKey('document_type', $errors);
    }

    /**
     * Nor as a termination, when it ends nothing.
     *
     * @return void
     */
    public function testATerminationOfNothingIsRefused(): void
    {
        $errors = self::errorsFor(ContractPrintType::ContractTermination, self::proposal());

        $this->assertArrayHasKey('document_type', $errors);
    }

    /**
     * An amendment wants papers meant as a change, to a contract somebody concluded. There is
     * nothing to amend before anybody signs, and papers meant as a new contract are not an
     * amendment however far along the version is.
     *
     * @return void
     */
    public function testAnAmendmentWantsAChangeToAConcludedContract(): void
    {
        $change = self::proposal(['purpose' => ProposalPurpose::ServiceChange]);

        $this->assertArrayHasKey(
            'document_type',
            self::errorsFor(ContractPrintType::ContractAmendment, $change, concluded: false),
        );
        $this->assertArrayHasKey(
            'document_type',
            self::errorsFor(ContractPrintType::ContractAmendment, self::proposal(), concluded: true),
        );
        $this->assertSame(
            [],
            self::errorsFor(ContractPrintType::ContractAmendment, $change, concluded: true),
        );
    }

    /**
     * The summary describes what is on offer and binds nobody, so every proposal may be printed as
     * one - including the empty proposal behind a new contract's papers.
     *
     * @return void
     */
    public function testTheSummaryIsAlwaysOnOffer(): void
    {
        $this->assertSame(
            [],
            self::errorsFor(ContractPrintType::ContractSummary, self::proposal(), concluded: false),
        );
    }

    /**
     * Asking for a signed copy is a choice about one printing, not a fact about the contract, so it
     * stays a query parameter and reaches the document unchallenged.
     *
     * @return void
     */
    public function testAskingForASignedCopyIsCarriedThrough(): void
    {
        $data = new ContractPrintData(
            ContractPrintType::ContractNew,
            self::contract(),
            self::version(),
            null,
        );
        $data->proposal = self::proposal();

        $this->assertSame([], (new ContractPrintValidator())->validate($data, ['signed' => '1']));
        $this->assertTrue($data->signed);
    }
}
