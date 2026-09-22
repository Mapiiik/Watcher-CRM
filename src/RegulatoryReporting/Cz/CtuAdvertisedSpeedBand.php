<?php
declare(strict_types=1);

namespace App\RegulatoryReporting\Cz;

/**
 * The band the advertised download speed of a connection is counted in, town by town.
 */
enum CtuAdvertisedSpeedBand: string
{
    case Below2 = 'speed_0_2';
    case From2To10 = 'speed_2_10';
    case From10To30 = 'speed_10_30';
    case From30To100 = 'speed_30_100';
    case From100To1000 = 'speed_100_1000';
    case From1000 = 'speed_1000_plus';

    /**
     * A speed nobody filled in counts in the lowest band rather than in none.
     *
     * @param int|null $kbps The advertised download speed.
     * @return self
     */
    public static function fromKbps(?int $kbps): self
    {
        return match (true) {
            $kbps === null, $kbps < 2048 => self::Below2,
            $kbps < 10240 => self::From2To10,
            $kbps < 30720 => self::From10To30,
            $kbps < 102400 => self::From30To100,
            $kbps < 1024000 => self::From100To1000,
            default => self::From1000,
        };
    }
}
