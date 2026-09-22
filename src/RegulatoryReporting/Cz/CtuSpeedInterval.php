<?php
declare(strict_types=1);

namespace App\RegulatoryReporting\Cz;

/**
 * The interval ČTÚ asks the speed an address point can reach to be reported in.
 */
enum CtuSpeedInterval: string
{
    case From30To100 = '30_100';
    case From100To300 = '100_300';
    case From300To1000 = '300_1000';
    case From1000 = '1000';
    case Unknown = 'unknown';

    /**
     * The interval of a speed in kbps, over a category's infrastructure.
     *
     * Fibre is reported at a gigabit whatever the tariff, that being what the line in the building
     * carries. Wireless is reported by the speed itself, and anything below 30 Mbit/s in the lowest
     * interval the form has. The other categories have no rule yet.
     *
     * @param int|null $kbps The speed.
     * @param \App\RegulatoryReporting\Cz\CtuTechnologyCategory $category Over what.
     * @return self
     */
    public static function of(?int $kbps, CtuTechnologyCategory $category): self
    {
        return match ($category) {
            CtuTechnologyCategory::Fttb, CtuTechnologyCategory::Ftth => self::From1000,
            CtuTechnologyCategory::Wifi => match (true) {
                $kbps >= 1024000 => self::From1000,
                $kbps >= 307200 => self::From300To1000,
                $kbps >= 102400 => self::From100To300,
                default => self::From30To100,
            },
            default => self::Unknown,
        };
    }
}
