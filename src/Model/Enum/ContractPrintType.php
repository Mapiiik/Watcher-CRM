<?php
declare(strict_types=1);

namespace App\Model\Enum;

use App\Model\Enum\Trait\EnumOptionsTrait;
use Cake\Database\Type\EnumLabelInterface;
use Override;

/**
 * ContractPrintType Enum
 */
enum ContractPrintType: string implements EnumLabelInterface
{
    use EnumOptionsTrait;

    case ContractNew = 'contract-new';
    case ContractNewX = 'contract-new-x';
    case ContractAmendment = 'contract-amendment';
    case ContractTermination = 'contract-termination';
    case HandoverInstallation = 'handover-protocol-installation';
    case HandoverUninstallation = 'handover-protocol-uninstallation';

    /**
     * @return string
     */
    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::ContractNew =>
                __('Contract for the provision of services'),

            self::ContractNewX =>
                __('Contract for the provision of services (with termination of the original contract)'),

            self::ContractAmendment =>
                __('Amendment to the contract for the provision of services'),

            self::ContractTermination =>
                __('Agreement to terminate contract for the provision of services'),

            self::HandoverInstallation =>
                __('Handover protocol - Installation of internet connection'),

            self::HandoverUninstallation =>
                __('Handover protocol - Internet connection uninstallation'),
        };
    }

    /**
     * Indicates whether this document type requires a contract version
     * to be explicitly selected by the user.
     *
     * This corresponds to the common validation that checks
     * for the presence of ContractPrintData::$contractVersion.
     *
     * @return bool
     */
    public function requiresContractVersion(): bool
    {
        return true;
    }

    /**
     * Indicates whether this document type requires selecting
     * an existing contract version to be replaced.
     *
     * This corresponds to validation of:
     *  - ContractPrintData::$contractVersionToBeReplaced
     *  - number_of_the_contract_to_be_terminated
     *
     * @return bool
     */
    public function requiresContractVersionToBeReplaced(): bool
    {
        return $this === self::ContractNewX;
    }

    /**
     * Indicates whether this document type requires
     * an effective date of amendment.
     *
     * This corresponds to validation of:
     *  - effective_date_of_the_amendment
     *  - conclusion_date of the selected contract version
     *
     * @return bool
     */
    public function requiresEffectiveDateOfTheAmendment(): bool
    {
        return $this === self::ContractAmendment;
    }

    /**
     * Indicates whether this document type requires
     * the number of the contract to be terminated.
     *
     * This corresponds to validation of:
     *  - number_of_the_contract_to_be_terminated
     *
     * @return bool
     */
    public function requiresNumberOfTheContractToBeTerminated(): bool
    {
        return in_array($this, [
            self::ContractNewX,
            self::ContractTermination,
            self::HandoverUninstallation,
        ], true);
    }

    /**
     * Indicates whether this document type represents
     * a handover protocol and therefore requires
     * technical connection details enrichment.
     *
     * This controls enrichment of:
     *  - access point
     *  - RADIUS username
     *  - RADIUS password
     *
     * @return bool
     */
    public function isHandoverProtocol(): bool
    {
        return in_array($this, [
            self::HandoverInstallation,
            self::HandoverUninstallation,
        ], true);
    }
}
