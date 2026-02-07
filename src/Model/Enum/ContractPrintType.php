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
}
