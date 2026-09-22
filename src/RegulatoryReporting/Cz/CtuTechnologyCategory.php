<?php
declare(strict_types=1);

namespace App\RegulatoryReporting\Cz;

use App\Model\Enum\AccessTechnology;

/**
 * The technology category ČTÚ files an address point under, by the annex that defines it.
 *
 * Only the categories the network actually has. A technology without one here is left out of the
 * report rather than guessed into a category it may not belong to.
 */
enum CtuTechnologyCategory: string
{
    case Wifi = 's2_wifi';
    case Fttb = 's2_fttb';
    case Ftth = 's2_ftth';
    case Catv = 's2_catv';

    /**
     * The category a technology is reported under, null when there is none for it yet.
     *
     * @param \App\Model\Enum\AccessTechnology $technology How the connection reaches the customer.
     * @return self|null
     */
    public static function fromTechnology(AccessTechnology $technology): ?self
    {
        return match ($technology) {
            AccessTechnology::FwaUnlicensed => self::Wifi,
            AccessTechnology::FttbEthernet => self::Fttb,
            AccessTechnology::FtthP2pEthernet, AccessTechnology::FtthP2mpPon => self::Ftth,
            AccessTechnology::CatvDocsis30, AccessTechnology::CatvDocsis31 => self::Catv,
            default => null,
        };
    }

    /**
     * The technologies reported under this category.
     *
     * @return list<\App\Model\Enum\AccessTechnology>
     */
    public function technologies(): array
    {
        return array_values(array_filter(
            AccessTechnology::cases(),
            fn(AccessTechnology $technology): bool => self::fromTechnology($technology) === $this,
        ));
    }
}
