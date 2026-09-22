<?php
declare(strict_types=1);

namespace App\RegulatoryReporting\Cz;

/**
 * The band the commonly available speed of an active connection is counted in.
 */
enum CtuActiveSpeedBand: string
{
    case Below30 = 'speed_0_30';
    case From30To100 = 'speed_30_100';
    case From100 = 'speed_100_plus';

    /**
     * @param int|null $kbps The commonly available download speed.
     * @return self
     */
    public static function fromKbps(?int $kbps): self
    {
        return match (true) {
            $kbps < 30720 => self::Below30,
            $kbps < 102400 => self::From30To100,
            default => self::From100,
        };
    }
}
