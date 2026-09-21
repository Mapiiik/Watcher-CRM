<?php
declare(strict_types=1);

namespace WorkReports\Service;

use Cake\I18n\Date;
use Settings\Utility\Settings;
use Yasumi\Provider\AbstractProvider;
use Yasumi\Yasumi;

/**
 * Which days are working days, as the public holidays of the country say.
 *
 * The holidays come from Yasumi. Nothing else in the plugin reaches it directly, so that what
 * counts as a working day is decided in one place.
 */
class WorkingCalendar
{
    /**
     * Holiday providers already made, by year.
     *
     * @var array<int, \Yasumi\Provider\AbstractProvider>
     */
    private array $providers = [];

    /**
     * @param string $country Yasumi provider to take the holidays from.
     */
    public function __construct(private string $country)
    {
    }

    /**
     * The calendar of the country the settings name.
     *
     * @return self
     */
    public static function fromSettings(): self
    {
        return new self((string)Settings::get('work_reports.calendar.country', 'CzechRepublic'));
    }

    /**
     * Whether the day is a public holiday.
     *
     * @param \Cake\I18n\Date $day Day asked about.
     * @return bool
     */
    public function isHoliday(Date $day): bool
    {
        return $this->provider($day->year)->isHoliday($day->toNative());
    }

    /**
     * Whether the day is neither a weekend nor a public holiday.
     *
     * @param \Cake\I18n\Date $day Day asked about.
     * @return bool
     */
    public function isWorkingDay(Date $day): bool
    {
        return $this->provider($day->year)->isWorkingDay($day->toNative());
    }

    /**
     * Whether the day falls on a weekend.
     *
     * @param \Cake\I18n\Date $day Day asked about.
     * @return bool
     */
    public function isWeekend(Date $day): bool
    {
        return $day->isWeekend();
    }

    /**
     * The working days of the month the day falls in.
     *
     * @param \Cake\I18n\Date $month Any day of the month.
     * @return list<\Cake\I18n\Date>
     */
    public function workingDays(Date $month): array
    {
        $days = [];
        $day = $month->firstOfMonth();
        $last = $month->lastOfMonth();

        while ($day <= $last) {
            if ($this->isWorkingDay($day)) {
                $days[] = $day;
            }
            $day = $day->addDays(1);
        }

        return $days;
    }

    /**
     * The holidays of the year, made once.
     *
     * @param int $year Year of the holidays.
     * @return \Yasumi\Provider\AbstractProvider
     */
    private function provider(int $year): AbstractProvider
    {
        return $this->providers[$year] ??= Yasumi::create($this->country, $year);
    }
}
