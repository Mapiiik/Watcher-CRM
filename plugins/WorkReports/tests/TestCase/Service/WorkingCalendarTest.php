<?php
declare(strict_types=1);

namespace WorkReports\Test\TestCase\Service;

use Cake\I18n\Date;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use WorkReports\Model\Enum\DayKind;
use WorkReports\Service\WorkingCalendar;

/**
 * WorkReports\Service\WorkingCalendar Test Case
 */
class WorkingCalendarTest extends TestCase
{
    /**
     * Months whose count of working days is known from the calendars kept by hand.
     *
     * @return array<string, array{string, string, int}>
     */
    public static function months(): array
    {
        return [
            'CZ April with Good Friday and Easter Monday' => ['CzechRepublic', '2026-04-01', 20],
            'CZ May with two Fridays off' => ['CzechRepublic', '2026-05-01', 19],
            'CZ June without a holiday' => ['CzechRepublic', '2026-06-01', 22],
            'CZ December with Christmas' => ['CzechRepublic', '2026-12-01', 21],
            'HR November with Remembrance Day' => ['Croatia', '2026-11-01', 20],
        ];
    }

    /**
     * @param string $country Yasumi provider.
     * @param string $month Any day of the month.
     * @param int $expected Working days of the month.
     * @return void
     */
    #[DataProvider('months')]
    public function testWorkingDays(string $country, string $month, int $expected): void
    {
        $calendar = new WorkingCalendar($country);

        $this->assertCount($expected, $calendar->workingDays(new Date($month)));
    }

    /**
     * @return void
     */
    public function testHolidayIsNotAWorkingDay(): void
    {
        $calendar = new WorkingCalendar('CzechRepublic');
        $goodFriday = new Date('2026-04-03');

        $this->assertTrue($calendar->isHoliday($goodFriday));
        $this->assertFalse($calendar->isWorkingDay($goodFriday));
        $this->assertFalse($calendar->isWeekend($goodFriday));
    }

    /**
     * @return void
     */
    public function testMaundyThursdayIsAWorkingDay(): void
    {
        $calendar = new WorkingCalendar('CzechRepublic');

        $this->assertTrue($calendar->isWorkingDay(new Date('2026-04-02')));
    }

    /**
     * A public holiday on a Saturday is a holiday rather than a weekend.
     *
     * @return void
     */
    public function testDayKind(): void
    {
        $calendar = new WorkingCalendar('CzechRepublic');

        $this->assertSame(DayKind::WorkingDay, $calendar->dayKind(new Date('2026-06-08')));
        $this->assertSame(DayKind::Weekend, $calendar->dayKind(new Date('2026-06-13')));
        $this->assertSame(DayKind::Holiday, $calendar->dayKind(new Date('2026-12-24')));
        $this->assertSame(DayKind::Holiday, $calendar->dayKind(new Date('2027-05-08')));
    }

    /**
     * @return void
     */
    public function testWeekendIsNotAWorkingDay(): void
    {
        $calendar = new WorkingCalendar('CzechRepublic');
        $saturday = new Date('2026-06-13');

        $this->assertTrue($calendar->isWeekend($saturday));
        $this->assertFalse($calendar->isHoliday($saturday));
        $this->assertFalse($calendar->isWorkingDay($saturday));
    }
}
