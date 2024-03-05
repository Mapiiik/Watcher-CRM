<?php
declare(strict_types=1);

namespace App\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;

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
        return match ($this) {
            self::Email => __('Email'),
            self::EmailContracts => __('Email - Contracts'),
            self::EmailInvoices => __('Email - Invoices'),
            self::EmailSupport => __('Email - Support'),
            self::Sms => __('SMS'),
        };

        //return Inflector::humanize(Inflector::underscore($this->name));
    }
}
