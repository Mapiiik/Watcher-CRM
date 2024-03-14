<?php
declare(strict_types=1);

namespace App\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;

/**
 * CustomerInvoiceDeliveryType Enum
 */
enum CustomerInvoiceDeliveryType: int implements EnumLabelInterface
{
    case None = 0;
    case Email = 1;

    /**
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::None => __('Do not send'),
            self::Email => __('Send by email'),
        };

        //return Inflector::humanize(Inflector::underscore($this->name));
    }
}
