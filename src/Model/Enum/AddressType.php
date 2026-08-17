<?php
declare(strict_types=1);

namespace App\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;
use Override;

/**
 * AddressType Enum
 */
enum AddressType: int implements EnumLabelInterface
{
    case Installation = 0;
    case Billing = 1;
    case Delivery = 2;
    case Permanent = 3;

    /**
     * The types an invoice address is looked for in, best first.
     *
     * A customer is not asked to keep a billing address - most keep only the address the
     * service was installed at - so the invoice falls back through these until one of them
     * has an address. Whatever reads this order and whatever checks it holds have to agree,
     * which is why it is stated once here rather than spelled out at each place.
     *
     * @return list<self>
     */
    public static function billingFallback(): array
    {
        return [self::Billing, self::Permanent, self::Installation];
    }

    /**
     * @return string
     */
    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::Installation => __('Installation Address'),
            self::Billing => __('Billing Address'),
            self::Delivery => __('Delivery Address'),
            self::Permanent => __('Permanent Address'),
        };

        //return Inflector::humanize(Inflector::underscore($this->name));
    }
}
