<?php
declare(strict_types=1);

namespace App\Model\Enum;

use App\Model\Enum\Trait\EnumOptionsTrait;
use Cake\Database\Type\EnumLabelInterface;
use Override;
use Settings\ValueObject\SettingChoices;

/**
 * How a business is told from a household, which the regulators count apart.
 *
 * It depends on the country. A Czech identity number belongs only to someone who does business,
 * a Croatian one to every person, so there the company name is what tells.
 */
enum BusinessCustomerDetection: string implements EnumLabelInterface, SettingChoices
{
    use EnumOptionsTrait;

    /**
     * Where the setting is kept.
     */
    public const SETTINGS_PATH = 'core.customers.business_detection';

    case IdentityNumber = 'identity-number';
    case Company = 'company';

    /**
     * @return string
     */
    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::IdentityNumber => __('A customer with an identity number is a business (Czech IČ)'),
            self::Company => __('A customer with a company name is a business (Croatian OIB belongs to everyone)'),
        };
    }

    /**
     * The one the setting names, or the identity number where it names nothing that means anything.
     *
     * @param string|null $value The stored setting value.
     * @return self
     */
    public static function fromSetting(?string $value): self
    {
        return self::tryFrom((string)$value) ?? self::IdentityNumber;
    }
}
