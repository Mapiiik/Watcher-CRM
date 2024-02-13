<?php
declare(strict_types=1);

namespace App\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;
use Cake\Utility\Inflector;

/**
 * CustomerMessageDeliveryStatus Enum
 */
enum CustomerMessageDeliveryStatus: int implements EnumLabelInterface
{
    case Pending = 0;
    case Sent = 10;
    case Delivered = 20;
    case Failed = 30;

    /**
     * @return string
     */
    public function label(): string
    {
        return Inflector::humanize(Inflector::underscore($this->name));
    }
}
