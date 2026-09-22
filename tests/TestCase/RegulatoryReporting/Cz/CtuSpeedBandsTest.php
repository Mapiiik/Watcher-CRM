<?php
declare(strict_types=1);

namespace App\Test\TestCase\RegulatoryReporting\Cz;

use App\RegulatoryReporting\Cz\CtuActiveSpeedBand;
use App\RegulatoryReporting\Cz\CtuAdvertisedSpeedBand;
use App\RegulatoryReporting\Cz\CtuSpeedInterval;
use App\RegulatoryReporting\Cz\CtuTechnologyCategory;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The bands ČTÚ counts speeds in, each tried at its edges.
 *
 * A speed exactly at a boundary belongs to the band above it, which is where an off-by-one would
 * move a whole tariff into the wrong column.
 */
#[CoversClass(CtuSpeedInterval::class)]
#[CoversClass(CtuActiveSpeedBand::class)]
#[CoversClass(CtuAdvertisedSpeedBand::class)]
class CtuSpeedBandsTest extends TestCase
{
    /**
     * @return array<string, array{int|null, \App\RegulatoryReporting\Cz\CtuSpeedInterval}>
     */
    public static function wirelessSpeeds(): array
    {
        return [
            'nothing filled in' => [null, CtuSpeedInterval::From30To100],
            'below the lowest interval' => [10240, CtuSpeedInterval::From30To100],
            'just below 100' => [102399, CtuSpeedInterval::From30To100],
            'at 100' => [102400, CtuSpeedInterval::From100To300],
            'at 300' => [307200, CtuSpeedInterval::From300To1000],
            'at a gigabit' => [1024000, CtuSpeedInterval::From1000],
        ];
    }

    /**
     * Wireless is reported by its speed, and nothing slower than the form's lowest interval.
     *
     * @param int|null $kbps The speed.
     * @param \App\RegulatoryReporting\Cz\CtuSpeedInterval $expected Its interval.
     * @return void
     * @link \App\RegulatoryReporting\Cz\CtuSpeedInterval::of()
     */
    #[DataProvider('wirelessSpeeds')]
    public function testWirelessIsReportedByItsSpeed(?int $kbps, CtuSpeedInterval $expected): void
    {
        $this->assertSame($expected, CtuSpeedInterval::of($kbps, CtuTechnologyCategory::Wifi));
    }

    /**
     * Fibre is reported at what the line carries, not at what the tariff sells.
     *
     * @return void
     * @link \App\RegulatoryReporting\Cz\CtuSpeedInterval::of()
     */
    public function testFibreIsReportedAtAGigabit(): void
    {
        $this->assertSame(CtuSpeedInterval::From1000, CtuSpeedInterval::of(30720, CtuTechnologyCategory::Fttb));
        $this->assertSame(CtuSpeedInterval::From1000, CtuSpeedInterval::of(30720, CtuTechnologyCategory::Ftth));
        $this->assertSame(CtuSpeedInterval::Unknown, CtuSpeedInterval::of(30720, CtuTechnologyCategory::Catv));
    }

    /**
     * @return void
     * @link \App\RegulatoryReporting\Cz\CtuActiveSpeedBand::fromKbps()
     */
    public function testTheActiveBandsBreakAt30And100(): void
    {
        $this->assertSame(CtuActiveSpeedBand::Below30, CtuActiveSpeedBand::fromKbps(null));
        $this->assertSame(CtuActiveSpeedBand::Below30, CtuActiveSpeedBand::fromKbps(30719));
        $this->assertSame(CtuActiveSpeedBand::From30To100, CtuActiveSpeedBand::fromKbps(30720));
        $this->assertSame(CtuActiveSpeedBand::From30To100, CtuActiveSpeedBand::fromKbps(102399));
        $this->assertSame(CtuActiveSpeedBand::From100, CtuActiveSpeedBand::fromKbps(102400));
    }

    /**
     * @return void
     * @link \App\RegulatoryReporting\Cz\CtuAdvertisedSpeedBand::fromKbps()
     */
    public function testTheAdvertisedBandsBreakWhereTheFormDoes(): void
    {
        $this->assertSame(CtuAdvertisedSpeedBand::Below2, CtuAdvertisedSpeedBand::fromKbps(null));
        $this->assertSame(CtuAdvertisedSpeedBand::Below2, CtuAdvertisedSpeedBand::fromKbps(2047));
        $this->assertSame(CtuAdvertisedSpeedBand::From2To10, CtuAdvertisedSpeedBand::fromKbps(2048));
        $this->assertSame(CtuAdvertisedSpeedBand::From10To30, CtuAdvertisedSpeedBand::fromKbps(10240));
        $this->assertSame(CtuAdvertisedSpeedBand::From30To100, CtuAdvertisedSpeedBand::fromKbps(30720));
        $this->assertSame(CtuAdvertisedSpeedBand::From100To1000, CtuAdvertisedSpeedBand::fromKbps(102400));
        $this->assertSame(CtuAdvertisedSpeedBand::From1000, CtuAdvertisedSpeedBand::fromKbps(1024000));
    }
}
