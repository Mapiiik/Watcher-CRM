<?php
declare(strict_types=1);

namespace App\RegulatoryReporting\Hr;

/**
 * The bands the address listing counts connections in, named by their lower edge in Mbit/s as the
 * old system's listing named its columns.
 */
enum HakomAddressSpeedBand: string
{
    case From0 = '0M';
    case From2 = '2M';
    case From4 = '4M';
    case From10 = '10M';
    case From20 = '20M';
    case From30 = '30M';
    case From50 = '50M';
    case From100 = '100M';
    case From300 = '300M';
    case From500 = '500M';
    case From1000 = '1G';
    case From10000 = '10G';
    case Unknown = 'x';

    /**
     * The band of a contracted download speed, unknown where none is on file.
     *
     * @param int|null $kbps The speed.
     * @return self
     */
    public static function fromKbps(?int $kbps): self
    {
        return match (true) {
            $kbps === null => self::Unknown,
            $kbps >= 10240000 => self::From10000,
            $kbps >= 1024000 => self::From1000,
            $kbps >= 512000 => self::From500,
            $kbps >= 307200 => self::From300,
            $kbps >= 102400 => self::From100,
            $kbps >= 51200 => self::From50,
            $kbps >= 30720 => self::From30,
            $kbps >= 20480 => self::From20,
            $kbps >= 10240 => self::From10,
            $kbps >= 4096 => self::From4,
            $kbps >= 2048 => self::From2,
            default => self::From0,
        };
    }
}
