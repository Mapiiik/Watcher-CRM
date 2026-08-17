<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service\ContractPrint;

use App\Model\Entity\Contract;
use App\Model\Entity\ContractVersion;
use App\Model\Entity\ServiceType;
use App\Model\Enum\ContractPrintType;
use App\Service\ContractPrint\ContractPrintData;
use App\Service\ContractPrint\ContractPrintValidator;
use Cake\I18n\Date;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Service\ContractPrint\ContractPrintValidator Test Case
 *
 * An end date on a contract version is not by itself a fixed-term contract - it is also how
 * a superseded or an ended version is recorded. The gate is therefore what tells the two
 * apart, and it also holds the obligation to the end of the contract, because the activation
 * fee and its clause are decided by the obligation alone.
 */
#[UsesClass(ContractPrintValidator::class)]
class ContractPrintValidatorTest extends TestCase
{
    /**
     * A contract whose service type asks for nothing the common validation would complain about.
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
     * A contract version to be executed, to be varied by the tests.
     *
     * @param string|null $validUntil Date the version is valid until.
     * @param string|null $obligationUntil Date the obligation lasts until.
     * @return \App\Model\Entity\ContractVersion
     */
    private static function contractVersion(
        ?string $validUntil = null,
        ?string $obligationUntil = null,
    ): ContractVersion {
        return new ContractVersion([
            'id' => 'a1b2c3d4-0000-4000-8000-000000000003',
            'valid_from' => new Date('2026-01-01'),
            'valid_until' => $validUntil === null ? null : new Date($validUntil),
            'obligation_until' => $obligationUntil === null ? null : new Date($obligationUntil),
            'conclusion_date' => new Date('2026-01-01'),
        ]);
    }

    /**
     * Runs the validator over a document type that executes a contract version.
     *
     * @param array<string, mixed> $query Print form query parameters.
     * @return array<string, array<string>>
     */
    private static function validate(
        ContractVersion $contractVersion,
        array $query = [],
        ContractPrintType $type = ContractPrintType::ContractNew,
    ): array {
        $data = new ContractPrintData(
            type: $type,
            contract: self::contract(),
            contractVersionToBeExecuted: $contractVersion,
            contractVersionToBeTerminated: null,
        );

        return (new ContractPrintValidator())->validate($data, $query);
    }

    /**
     * A version with an end date will not print until the operator says a fixed term is meant.
     *
     * @return void
     */
    public function testEndDateWithoutAcknowledgementIsRefused(): void
    {
        $errors = self::validate(self::contractVersion('2027-12-31', '2027-12-31'));

        $this->assertArrayHasKey('fixed_term', $errors);
        $this->assertStringContainsString('Please confirm', $errors['fixed_term'][0]);
    }

    /**
     * A fixed term is its own minimum period of performance, so an unset obligation is refused.
     *
     * @return void
     */
    public function testFixedTermWithoutObligationIsRefused(): void
    {
        $errors = self::validate(
            self::contractVersion('2027-12-31'),
            ['fixed_term' => '1'],
        );

        $this->assertArrayHasKey('fixed_term', $errors);
        $this->assertStringContainsString('minimum period of performance', $errors['fixed_term'][0]);
    }

    /**
     * An obligation that ends before the contract does is refused for the same reason.
     *
     * @return void
     */
    public function testFixedTermWithShorterObligationIsRefused(): void
    {
        $errors = self::validate(
            self::contractVersion('2027-12-31', '2027-06-30'),
            ['fixed_term' => '1'],
        );

        $this->assertArrayHasKey('fixed_term', $errors);
        $this->assertStringContainsString('minimum period of performance', $errors['fixed_term'][0]);
    }

    /**
     * Acknowledged, with the obligation reaching the end of the contract, the document prints.
     *
     * @return void
     */
    public function testAcknowledgedFixedTermPasses(): void
    {
        $errors = self::validate(
            self::contractVersion('2027-12-31', '2027-12-31'),
            ['fixed_term' => '1'],
        );

        $this->assertSame([], $errors);
    }

    /**
     * A version without an end date is untouched by the gate, acknowledged or not.
     *
     * @return void
     */
    public function testVersionWithoutEndDatePasses(): void
    {
        $this->assertSame([], self::validate(self::contractVersion()));
        $this->assertSame([], self::validate(self::contractVersion(), ['fixed_term' => '1']));
    }

    /**
     * The same gate applies to a new contract that terminates the original one.
     *
     * @return void
     */
    public function testGateAppliesToContractNewX(): void
    {
        $errors = self::validate(
            self::contractVersion('2027-12-31', '2027-12-31'),
            [],
            ContractPrintType::ContractNewX,
        );

        $this->assertArrayHasKey('fixed_term', $errors);
    }

    /**
     * Document types that do not state the duration of the contract are left alone.
     *
     * @return void
     */
    public function testGateDoesNotApplyToOtherDocumentTypes(): void
    {
        $contractVersion = self::contractVersion('2027-12-31');

        $amendment = self::validate(
            $contractVersion,
            ['effective_date_of_the_amendment' => '2027-01-01'],
            ContractPrintType::ContractAmendment,
        );
        $this->assertArrayNotHasKey('fixed_term', $amendment);

        $handover = self::validate(
            $contractVersion,
            [],
            ContractPrintType::HandoverInstallation,
        );
        $this->assertSame([], $handover);
    }
}
