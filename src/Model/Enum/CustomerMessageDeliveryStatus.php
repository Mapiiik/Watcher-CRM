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
        switch ($this->value) {
            case $this::Pending->value:
                return __('Pending');
            case $this::Processed->value:
                return __('Processed');
            case $this::Sent->value:
                return __('Sent');
            case $this::Delivered->value:
                return __('Delivered');
            case $this::Failed->value:
                return __('Failed');
        }

        //return Inflector::humanize(Inflector::underscore($this->name));
    }
}
