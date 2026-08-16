<?php
declare(strict_types=1);

namespace App\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;
use Override;

/**
 * IpNetworkTypeOfUse Enum
 */
enum IpNetworkTypeOfUse: int implements EnumLabelInterface
{
    case CustomerRADIUS = 00;
    case CustomerManually = 10;
    case TechnologyManually = 20;

    /**
     * Whether this is an network of the customer's rather than of our own equipment.
     *
     * What the automatic blocking has to write into a firewall list is the customer's; a
     * technology network belongs to the device terminating the circuit, and cutting that
     * off would stop our own equipment rather than the service.
     *
     * The cases are written out rather than taken as everything that is not the other, so
     * a type added later has to be placed by hand instead of falling in here by default.
     *
     * @return bool
     */
    public function isCustomer(): bool
    {
        return match ($this) {
            self::CustomerRADIUS, self::CustomerManually => true,
            self::TechnologyManually => false,
        };
    }

    /**
     * Whether this is an address of our own equipment.
     *
     * @return bool
     */
    public function isTechnology(): bool
    {
        return match ($this) {
            self::TechnologyManually => true,
            self::CustomerRADIUS, self::CustomerManually => false,
        };
    }

    /**
     * The cases that are the customer's, for a query to ask by.
     *
     * @return list<self>
     */
    public static function customerCases(): array
    {
        return array_values(array_filter(self::cases(), fn(self $case): bool => $case->isCustomer()));
    }

    /**
     * @return string
     */
    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::CustomerRADIUS => __('Customer network set via RADIUS'),
            self::CustomerManually => __('Customer network set manually'),
            self::TechnologyManually => __('Technology network set manually'),
        };

        //return Inflector::humanize(Inflector::underscore($this->name));
    }
}
