<?php
declare(strict_types=1);

namespace App\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;

/**
 * CustomerMessageDeliveryStatus Enum
 */
enum CustomerMessageDeliveryStatus: int implements EnumLabelInterface
{
    case Pending = 0;
    case Processed = 1;
    case Sent = 10;
    case Delivered = 20;
    case Failed = 30;

    /**
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Pending'),
            self::Processed => __('Processed'),
            self::Sent => __('Sent'),
            self::Delivered => __('Delivered'),
            self::Failed => __('Failed'),
        };

        //return Inflector::humanize(Inflector::underscore($this->name));
    }
}
