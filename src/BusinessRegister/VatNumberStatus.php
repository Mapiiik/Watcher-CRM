<?php
declare(strict_types=1);

namespace App\BusinessRegister;

use Cake\Database\Type\EnumLabelInterface;
use Override;

/**
 * What a register says about a VAT number.
 *
 * Holding a tax identification number and being registered for VAT are two different things -
 * in the Czech Republic a company is given one on registering for income tax, whether or not it
 * ever becomes a VAT payer - so a number that is not a payer's is told apart from one nobody
 * holds at all.
 *
 * Not every register can make that distinction: VIES only knows VAT registrations, so a number
 * it does not confirm is Invalid as far as it can say.
 */
enum VatNumberStatus: string implements EnumLabelInterface
{
    case Registered = 'registered';
    case NotRegistered = 'not_registered';
    case Invalid = 'invalid';

    /**
     * @return string
     */
    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::Registered => __('OK') . ' - ' . __('VAT payer'),
            self::NotRegistered => __('OK') . ' - ' . __('not a VAT payer'),
            self::Invalid => __('Invalid'),
        };
    }
}
