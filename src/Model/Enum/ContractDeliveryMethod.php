<?php
declare(strict_types=1);

namespace App\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;
use Override;

/**
 * How the papers for a contract version reached the customer.
 *
 * Kept beside the day they went out, because "we sent it" is only half an answer: post and
 * a data box carry a date somebody else can confirm, an e-mail carries one we can look up,
 * and handing it over in person carries neither. Which it was decides what can be shown if
 * the customer says the papers never came.
 */
enum ContractDeliveryMethod: int implements EnumLabelInterface
{
    case Email = 10;
    case Sms = 20;
    case Post = 30;
    case DataBox = 40;
    case InPerson = 50;

    /**
     * @return string
     */
    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::Email => __('E-mail'),
            self::Sms => __('SMS'),
            self::Post => __('Post'),
            self::DataBox => __('Data box'),
            self::InPerson => __('In person'),
        };
    }

    /**
     * The ways on offer, as stored value to what it is called.
     *
     * Written out rather than taken from EnumOptionsTrait, because that trait promises
     * string keys for the settings plugin and this enum is backed by numbers - PHP turns a
     * numeric string key back into an integer, so the trait cannot keep that promise here.
     *
     * @return array<int, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
