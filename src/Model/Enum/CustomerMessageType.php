<?php
declare(strict_types=1);

namespace App\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;
use Cake\Utility\Inflector;

/**
 * CustomerMessageType Enum
 */
enum CustomerMessageType: int implements EnumLabelInterface
{
    case Email = 10;
    case EmailContracts = 11;
    case EmailInvoices = 12;
    case EmailSupport = 13;
    case Sms = 20;

    /**
     * @return string
     */
    public function label(): string
    {
        return Inflector::humanize(Inflector::underscore($this->name));
    }
}
