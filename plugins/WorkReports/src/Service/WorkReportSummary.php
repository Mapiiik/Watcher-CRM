<?php
declare(strict_types=1);

namespace WorkReports\Service;

use Cake\I18n\Date;
use PhpCollective\DecimalObject\Decimal;
use Settings\Utility\Settings;
use WorkReports\Model\Entity\WorkReport;
use WorkReports\Model\Enum\DayKind;

/**
 * What a month adds up to: the fund, what was worked against it, and the rest the head of the
 * spreadsheet used to count by hand.
 *
 * A whole day does not add hours. It takes the day out of the fund when its type says so, and
 * otherwise only covers the day, whose hours are then made up on others.
 *
 * Reads the report with its items, their types, cars and labels, and its on-call days contained.
 */
final class WorkReportSummary
{
    /**
     * @param int $workingDays Working days of the month.
     * @param int $fundDays Working days left after those taken out by whole days.
     * @param int $fundMinutes What is to be worked in the month.
     * @param int $workedMinutes What was worked.
     * @param array<string, array{name: string, minutes: int, days: int}> $types Time and days by type.
     * @param list<\Cake\I18n\Date> $missingDays Working days with nothing reported.
     * @param list<\Cake\I18n\Date> $extraDays Other days with something reported.
     * @param array<string, array{name: string, distance: int}> $cars Distance by car.
     * @param float $cashCollected Cash collected in the month.
     * @param array<string, array{label: \WorkReports\Model\Entity\WorkLabel, count: int}> $labels Items by label.
     * @param int $onCallDays Days on call.
     * @param float $onCallHours What the days on call are worth.
     * @param array<string, array{kind: \WorkReports\Model\Enum\DayKind, days: int, hours: float}> $onCallByKind The days on call by the kind of the day.
     */
    public function __construct(
        public readonly int $workingDays,
        public readonly int $fundDays,
        public readonly int $fundMinutes,
        public readonly int $workedMinutes,
        public readonly array $types,
        public readonly array $missingDays,
        public readonly array $extraDays,
        public readonly array $cars,
        public readonly float $cashCollected,
        public readonly array $labels,
        public readonly int $onCallDays,
        public readonly float $onCallHours,
        public readonly array $onCallByKind = [],
    ) {
    }

    /**
     * Worked minus the fund: overtime when above zero, hours missing when below.
     *
     * @return int
     */
    public function balanceMinutes(): int
    {
        return $this->workedMinutes - $this->fundMinutes;
    }

    /**
     * The summary of the report, by the calendar and the settings of the application.
     *
     * @param \WorkReports\Model\Entity\WorkReport $report Report with its items contained.
     * @return self
     */
    public static function fromSettings(WorkReport $report): self
    {
        return self::of(
            $report,
            WorkingCalendar::fromSettings(),
            (float)Settings::get('work_reports.calendar.daily_hours', 8.0),
        );
    }

    /**
     * The summary of the report.
     *
     * @param \WorkReports\Model\Entity\WorkReport $report Report with its items contained.
     * @param \WorkReports\Service\WorkingCalendar $calendar Calendar the working days come from.
     * @param float $dailyHours Hours of a working day at a full workload.
     * @return self
     */
    public static function of(WorkReport $report, WorkingCalendar $calendar, float $dailyHours): self
    {
        $workingDays = [];
        foreach ($calendar->workingDays($report->month) as $day) {
            $workingDays[$day->format('Y-m-d')] = $day;
        }

        $covered = [];
        $takenOut = [];
        $workedMinutes = 0;
        $types = [];
        $typeDays = [];
        $cars = [];
        $cash = 0.0;
        $labels = [];

        foreach ($report->work_report_items ?? [] as $item) {
            $key = $item->date->format('Y-m-d');
            $covered[$key] = $item->date;

            $type = $item->work_report_item_type;
            $types[$type->id] ??= ['name' => $type->name, 'minutes' => 0];
            $types[$type->id]['minutes'] += $item->minutes;

            if ($item->whole_day) {
                $typeDays[$type->id][$key] = true;
                if ($type->reduces_fund) {
                    $takenOut[$key] = true;
                }
            }

            if ($type->counts_as_worked) {
                $workedMinutes += $item->minutes;
            }

            foreach (['private_car', 'company_car'] as $car) {
                $distance = (int)$item->get($car . '_distance');
                $entity = $item->get($car);
                if ($distance > 0 && $entity !== null) {
                    $cars[$entity->id] ??= ['name' => $entity->name_for_lists, 'distance' => 0];
                    $cars[$entity->id]['distance'] += $distance;
                }
            }

            $cash += self::number($item->cash_collected);

            foreach ($item->work_labels ?? [] as $label) {
                $labels[$label->id] ??= ['label' => $label, 'count' => 0];
                $labels[$label->id]['count']++;
            }
        }

        $byType = [];
        foreach ($types as $id => $type) {
            $byType[$id] = [
                'name' => $type['name'],
                'minutes' => $type['minutes'],
                'days' => count($typeDays[$id] ?? []),
            ];
        }

        $fundDays = count(array_diff_key($workingDays, $takenOut));
        $onCalls = $report->work_report_on_calls ?? [];
        $onCallByKind = [];
        foreach ($onCalls as $onCall) {
            $kind = $calendar->dayKind($onCall->date);
            $onCallByKind[$kind->value] ??= ['kind' => $kind, 'days' => 0, 'hours' => 0.0];
            $onCallByKind[$kind->value]['days']++;
            $onCallByKind[$kind->value]['hours'] += self::number($onCall->hours);
        }
        // in the order of the spreadsheet: working days, weekends, holidays
        $onCallByKind = array_filter(array_merge(
            array_fill_keys(array_map(fn(DayKind $kind): string => $kind->value, DayKind::cases()), null),
            $onCallByKind,
        ));

        return new self(
            workingDays: count($workingDays),
            fundDays: $fundDays,
            fundMinutes: (int)round($fundDays * $dailyHours * self::number($report->workload) * 60),
            workedMinutes: $workedMinutes,
            types: $byType,
            missingDays: array_values(array_diff_key($workingDays, $covered)),
            extraDays: array_values(self::sorted(array_diff_key($covered, $workingDays))),
            cars: $cars,
            cashCollected: $cash,
            labels: $labels,
            onCallDays: count($onCalls),
            onCallHours: array_sum(array_map(fn($onCall): float => self::number($onCall->hours), $onCalls)),
            onCallByKind: $onCallByKind,
        );
    }

    /**
     * A decimal column as a number, whether it came from the database or was set by hand.
     *
     * @param \PhpCollective\DecimalObject\Decimal|string|float|int|null $value Value to read.
     * @return float
     */
    private static function number(Decimal|string|float|int|null $value): float
    {
        return $value instanceof Decimal ? $value->toFloat() : (float)$value;
    }

    /**
     * Days in the order of the calendar.
     *
     * @param array<string, \Cake\I18n\Date> $days Days keyed by their date.
     * @return array<string, \Cake\I18n\Date>
     */
    private static function sorted(array $days): array
    {
        ksort($days);

        return $days;
    }

    /**
     * Minutes written as hours and minutes, the way the spreadsheet showed them.
     *
     * @param int $minutes Minutes, possibly negative.
     * @return string
     */
    public static function formatMinutes(int $minutes): string
    {
        $sign = $minutes < 0 ? '-' : '';
        $minutes = abs($minutes);

        return sprintf('%s%d:%02d', $sign, intdiv($minutes, 60), $minutes % 60);
    }

    /**
     * The missing days that have already come. What the month still has ahead of it is nothing to
     * point out, while submitting asks about the month whole.
     *
     * @return list<\Cake\I18n\Date>
     */
    public function missingDaysSoFar(): array
    {
        $today = Date::today();

        return array_values(array_filter($this->missingDays, fn(Date $day): bool => $day <= $today));
    }

    /**
     * Whether the day is one of the missing ones.
     *
     * @param \Cake\I18n\Date $day Day asked about.
     * @return bool
     */
    public function isMissing(Date $day): bool
    {
        foreach ($this->missingDays as $missing) {
            if ($missing->equals($day)) {
                return true;
            }
        }

        return false;
    }
}
