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
        switch ($this->value) {
            case $this::Email->value:
                return __('Email');
            case $this::EmailContracts->value:
                return __('Email - Contracts');
            case $this::EmailInvoices->value:
                return __('Email - Invoices');
            case $this::EmailSupport->value:
                return __('Email - Support');
            case $this::Sms->value:
                return __('SMS');
        }

        //return Inflector::humanize(Inflector::underscore($this->name));
    }
}
