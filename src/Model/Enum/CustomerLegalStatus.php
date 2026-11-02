<?php
declare(strict_types=1);

namespace App\Model\Enum;

use App\Model\Enum\Trait\EnumOptionsTrait;
use Cake\Database\Type\EnumLabelInterface;
use Override;

/**
 * Who the customer is in law, which decides what a contract may ask of them.
 *
 * A sole trader is a natural person all the same, and the law protects them like a consumer when
 * a contract ends early. Only a legal entity can be held to whatever was agreed.
 */
enum CustomerLegalStatus: string implements EnumLabelInterface
{
    use EnumOptionsTrait;

    case NaturalPerson = 'natural-person';
    case SoleTrader = 'sole-trader';
    case LegalEntity = 'legal-entity';

    /**
     * @return string
     */
    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::NaturalPerson => __('Natural Person'),
            self::SoleTrader => __('Sole Trader'),
            self::LegalEntity => __('Legal Entity'),
        };
    }
}
