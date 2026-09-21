<?php
declare(strict_types=1);

namespace WorkReports\Test\TestCase\Service;

use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use WorkReports\Model\Entity\WorkCar;
use WorkReports\Model\Entity\WorkLabel;
use WorkReports\Model\Entity\WorkReport;
use WorkReports\Model\Entity\WorkReportItem;
use WorkReports\Model\Entity\WorkReportItemType;
use WorkReports\Model\Entity\WorkReportOnCall;
use WorkReports\Model\Enum\TimeMode;
use WorkReports\Service\WorkingCalendar;
use WorkReports\Service\WorkReportSummary;

/**
 * WorkReports\Service\WorkReportSummary Test Case
 */
class WorkReportSummaryTest extends TestCase
{
    private WorkReportItemType $work;

    private WorkReportItemType $vacation;

    private WorkReportItemType $noWork;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->work = $this->type('work', 'Work', TimeMode::Range, countsAsWorked: true, reducesFund: false);
        $this->vacation = $this->type('vacation', 'Vacation', TimeMode::WholeDay, countsAsWorked: false, reducesFund: true);
        $this->noWork = $this->type('no-work', 'No Work', TimeMode::WholeDay, countsAsWorked: false, reducesFund: false);
    }

    /**
     * June 2026 as it was reported in the spreadsheet: half a workload, ten days of vacation,
     * 68:26 worked - which the spreadsheet made out as 20:26 of overtime.
     *
     * @return void
     */
    public function testMonthOfTheSpreadsheet(): void
    {
        $items = [];
        foreach (['15', '16', '17', '18', '19', '22', '23', '24', '25', '26'] as $day) {
            $items[] = $this->wholeDay($this->vacation, '2026-06-' . $day);
        }
        foreach (['01', '02', '03', '04', '05', '08', '09', '10', '11', '12', '29'] as $day) {
            $items[] = $this->range('2026-06-' . $day, '08:00', '13:40');
        }
        $items[] = $this->range('2026-06-30', '08:00', '12:42');
        // a Sunday, reported on top
        $items[] = $this->range('2026-06-21', '09:50', '11:14');

        $summary = $this->summary($this->report('2026-06-01', '0.5', $items));

        $this->assertSame(22, $summary->workingDays);
        $this->assertSame(12, $summary->fundDays);
        $this->assertSame('48:00', WorkReportSummary::formatMinutes($summary->fundMinutes));
        $this->assertSame('68:26', WorkReportSummary::formatMinutes($summary->workedMinutes));
        $this->assertSame('20:26', WorkReportSummary::formatMinutes($summary->balanceMinutes()));
        $this->assertSame([], $summary->missingDays);
        $this->assertEquals([new Date('2026-06-21')], $summary->extraDays);
        $this->assertSame(['name' => 'Vacation', 'minutes' => 0, 'days' => 10], $summary->types['vacation']);
        $this->assertSame(4106, $summary->types['work']['minutes']);
    }

    /**
     * A day of no work covers the day and leaves the fund whole, so its hours are missing unless
     * made up on another day.
     *
     * @return void
     */
    public function testNoWorkCoversTheDayButKeepsTheFund(): void
    {
        $summary = $this->summary($this->report('2026-06-01', '1', [
            $this->wholeDay($this->noWork, '2026-06-01'),
        ]));

        $this->assertSame(22, $summary->fundDays);
        $this->assertSame(22 * 8 * 60, $summary->fundMinutes);
        $this->assertSame(0, $summary->workedMinutes);
        $this->assertCount(21, $summary->missingDays);
        $this->assertFalse($summary->isMissing(new Date('2026-06-01')));
        $this->assertTrue($summary->isMissing(new Date('2026-06-02')));
    }

    /**
     * @return void
     */
    public function testDistanceCashLabelsAndOnCall(): void
    {
        $car = new WorkCar(['id' => 'car', 'name' => 'Octavia', 'license_plate' => '1AB 2345']);
        $label = new WorkLabel(['id' => 'tv', 'name' => 'New TV customer', 'color' => '#cccccc']);

        $first = $this->range('2026-06-01', '08:00', '09:00');
        $first->patch(['company_car_distance' => 30, 'cash_collected' => '150.50', 'work_labels' => [$label]]);
        $first->set('company_car', $car);
        $second = $this->range('2026-06-02', '08:00', '09:00');
        $second->patch(['company_car_distance' => 12, 'cash_collected' => '49.50', 'work_labels' => [$label]]);
        $second->set('company_car', $car);

        $report = $this->report('2026-06-01', '1', [$first, $second]);
        $report->work_report_on_calls = [
            new WorkReportOnCall(['date' => new Date('2026-06-08'), 'hours' => '3.00']),
            new WorkReportOnCall(['date' => new Date('2026-06-13'), 'hours' => '12.00']),
        ];

        $summary = $this->summary($report);

        $this->assertSame(['car' => ['name' => 'Octavia (1AB 2345)', 'distance' => 42]], $summary->cars);
        $this->assertSame(200.0, $summary->cashCollected);
        $this->assertSame(2, $summary->labels['tv']['count']);
        $this->assertSame(2, $summary->onCallDays);
        $this->assertSame(15.0, $summary->onCallHours);
    }

    /**
     * @return void
     */
    public function testFormatMinutes(): void
    {
        $this->assertSame('0:05', WorkReportSummary::formatMinutes(5));
        $this->assertSame('-1:30', WorkReportSummary::formatMinutes(-90));
        $this->assertSame('108:26', WorkReportSummary::formatMinutes(6506));
    }

    /**
     * @param \WorkReports\Model\Entity\WorkReport $report Report to sum up.
     * @return \WorkReports\Service\WorkReportSummary
     */
    private function summary(WorkReport $report): WorkReportSummary
    {
        return WorkReportSummary::of($report, new WorkingCalendar('CzechRepublic'), 8.0);
    }

    /**
     * @param string $month First day of the month.
     * @param string $workload Workload of the worker.
     * @param list<\WorkReports\Model\Entity\WorkReportItem> $items Items of the report.
     * @return \WorkReports\Model\Entity\WorkReport
     */
    private function report(string $month, string $workload, array $items): WorkReport
    {
        $report = new WorkReport(['month' => new Date($month), 'workload' => $workload]);
        $report->work_report_items = $items;
        $report->work_report_on_calls = [];

        return $report;
    }

    /**
     * @param string $date Day of the item.
     * @param string $from From, as a clock shows it.
     * @param string $until Until, as a clock shows it.
     * @return \WorkReports\Model\Entity\WorkReportItem
     */
    private function range(string $date, string $from, string $until): WorkReportItem
    {
        $item = new WorkReportItem([
            'date' => new Date($date),
            'whole_day' => false,
            'work_from' => new DateTime($date . ' ' . $from),
            'work_until' => new DateTime($date . ' ' . $until),
        ]);
        $item->work_report_item_type = $this->work;

        return $item;
    }

    /**
     * @param \WorkReports\Model\Entity\WorkReportItemType $type Type of the item.
     * @param string $date Day of the item.
     * @return \WorkReports\Model\Entity\WorkReportItem
     */
    private function wholeDay(WorkReportItemType $type, string $date): WorkReportItem
    {
        $item = new WorkReportItem(['date' => new Date($date), 'whole_day' => true]);
        $item->work_report_item_type = $type;

        return $item;
    }

    /**
     * @param string $id Identifier.
     * @param string $name Name.
     * @param \WorkReports\Model\Enum\TimeMode $timeMode How the time is stated.
     * @param bool $countsAsWorked Whether the time counts as worked.
     * @param bool $reducesFund Whether a whole day takes the day out of the fund.
     * @return \WorkReports\Model\Entity\WorkReportItemType
     */
    private function type(
        string $id,
        string $name,
        TimeMode $timeMode,
        bool $countsAsWorked,
        bool $reducesFund,
    ): WorkReportItemType {
        $type = new WorkReportItemType([
            'name' => $name,
            'time_mode' => $timeMode,
            'counts_as_worked' => $countsAsWorked,
            'reduces_fund' => $reducesFund,
        ]);
        $type->id = $id;

        return $type;
    }
}
