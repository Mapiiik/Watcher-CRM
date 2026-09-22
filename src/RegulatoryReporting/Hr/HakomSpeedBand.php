<?php
declare(strict_types=1);

namespace App\RegulatoryReporting\Hr;

/**
 * The bands HAKOM's quarterly form counts contracted speeds in, numbered as the form numbers them.
 *
 * The lower edge belongs to the band, the upper one to the next, as the form writes it.
 */
enum HakomSpeedBand: int
{
    case From2To10 = 1;
    case From10To30 = 2;
    case From30To100 = 3;
    case From100To300 = 4;
    case From300To1000 = 5;
    case From1000To2000 = 6;
    case From2000 = 7;

    /**
     * The band of a contracted download speed, null below the form's lowest.
     *
     * @param int|null $kbps The speed.
     * @return self|null
     */
    public static function fromKbps(?int $kbps): ?self
    {
        return match (true) {
            $kbps === null, $kbps < 2048 => null,
            $kbps < 10240 => self::From2To10,
            $kbps < 30720 => self::From10To30,
            $kbps < 102400 => self::From30To100,
            $kbps < 307200 => self::From100To300,
            $kbps < 1024000 => self::From300To1000,
            $kbps < 2048000 => self::From1000To2000,
            default => self::From2000,
        };
    }

    /**
     * The band as the form words it.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::From2To10 => '2 Mbit/s ≤ brzina < 10 Mbit/s',
            self::From10To30 => '10 Mbit/s ≤ brzina < 30 Mbit/s',
            self::From30To100 => '30 Mbit/s ≤ brzina < 100 Mbit/s',
            self::From100To300 => '100 Mbit/s ≤ brzina < 300 Mbit/s',
            self::From300To1000 => '300 Mbit/s ≤ brzina < 1 Gbit/s',
            self::From1000To2000 => '1 Gbit/s ≤ brzina < 2 Gbit/s',
            self::From2000 => 'brzina ≥2 Gbit/s',
        };
    }
}
