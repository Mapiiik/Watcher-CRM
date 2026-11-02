<?php
declare(strict_types=1);

namespace App\Model\Enum;

use App\Model\Entity\Customer;
use App\Model\Enum\Trait\EnumOptionsTrait;
use Cake\Database\Type\EnumLabelInterface;
use Override;

/**
 * How the legal status of a customer is guessed where no business register says - the way a
 * business was told from a household before there was a legal status to read.
 *
 * It depends on the country. A Czech identification number belongs only to someone who does
 * business, a Croatian OIB to every person, so there the company name is what tells.
 */
enum LegalStatusGuess: string implements EnumLabelInterface
{
    use EnumOptionsTrait;

    case IdentityNumber = 'identity-number';
    case Company = 'company';

    /**
     * @return string
     */
    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::IdentityNumber => __('by the identification number (Czech IČ)'),
            self::Company => __('by the company name (Croatian OIB)'),
        };
    }

    /**
     * Who the customer is taken for.
     *
     * By the identification number, one with a company is a legal entity and one without a sole
     * trader. By the company name nothing tells a sole trader apart, so a company is a legal
     * entity.
     *
     * @param \App\Model\Entity\Customer $customer The customer to guess.
     * @return \App\Model\Enum\CustomerLegalStatus
     */
    public function legalStatusOf(Customer $customer): CustomerLegalStatus
    {
        $hasCompany = trim((string)$customer->company) !== '';

        return match ($this) {
            self::IdentityNumber => match (true) {
                trim((string)$customer->identity_number) === '' => CustomerLegalStatus::NaturalPerson,
                $hasCompany => CustomerLegalStatus::LegalEntity,
                default => CustomerLegalStatus::SoleTrader,
            },
            self::Company => $hasCompany ? CustomerLegalStatus::LegalEntity : CustomerLegalStatus::NaturalPerson,
        };
    }
}
