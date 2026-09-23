<?php
declare(strict_types=1);

namespace WorkReports\Test\TestCase\Controller;

use App\Test\Traits\ControllerTestTrait;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;
use WorkReports\Controller\WorkReportItemsController;
use WorkReports\Controller\WorkReportsController;
use WorkReports\Model\Entity\WorkReport;
use WorkReports\Model\Entity\WorkReportWorkerRecipient;
use WorkReports\Service\WorkingCalendar;
use WorkReports\Test\Fixture\WorkReportItemTypesFixture;

/**
 * WorkReports\Controller\WorkReportItemsController Test Case
 *
 * @link \WorkReports\Controller\WorkReportItemsController
 */
#[UsesClass(WorkReportItemsController::class)]
#[UsesClass(WorkReportsController::class)]
class WorkReportItemsControllerTest extends TestCase
{
    use ControllerTestTrait;
    use EmailTrait;
    use IntegrationTestTrait;

    /**
     * The user of the fixture, who reports the work here.
     *
     * @var string
     */
    private const WORKER = '11edb519-be76-4d66-aea0-34188d31eae1';

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.AppUsers',
        'plugin.WorkReports.WorkReportItemTypes',
        'plugin.WorkReports.WorkReports',
        'plugin.WorkReports.WorkReportItems',
        'plugin.WorkReports.WorkReportWorkers',
        'plugin.WorkReports.WorkReportWorkerRecipients',
        'plugin.WorkReports.WorkCars',
        'plugin.WorkReports.WorkRates',
        'plugin.WorkReports.WorkLabels',
        'plugin.WorkReports.WorkReportItemLabels',
        'plugin.WorkReports.WorkReportItemCollaborators',
        'plugin.WorkReports.WorkReportOnCalls',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->loginAs(self::WORKER, 'user', superuser: false);
    }

    /**
     * A month with nothing in it yet is shown all the same, and nothing is stored for it.
     *
     * @return void
     */
    public function testSheetOfAnEmptyMonth(): void
    {
        $this->get('/work-reports/work-reports/sheet?month=2026-06');

        $this->assertResponseOk();
        $this->assertResponseContains('Nothing is reported on');
        $this->assertSame(2, substr_count($this->_getBodyAsString(), 'Start Now'));
        $this->assertSame(0, $this->getTableLocator()->get('WorkReports.WorkReports')->find()->count());
    }

    /**
     * The first item of a month starts its report, and the times are put together with the day.
     *
     * @return void
     */
    public function testAddStartsTheReport(): void
    {
        $this->post('/work-reports/work-report-items/add', [
            'work_report_item_type_id' => WorkReportItemTypesFixture::WORK,
            'date' => '2026-06-01',
            'time_from' => '22:00',
            'time_until' => '01:30',
            'description' => 'Night maintenance',
        ]);

        $this->assertRedirectContains('/work-reports/work-reports/sheet');

        /** @var \WorkReports\Model\Entity\WorkReportItem $item */
        $item = $this->getTableLocator()->get('WorkReports.WorkReportItems')->find()
            ->contain(['WorkReports'])
            ->firstOrFail();
        $this->assertSame(self::WORKER, $item->work_report->user_id);
        $this->assertEquals(new Date('2026-06-01'), $item->work_report->month);
        $this->assertSame(210, $item->minutes);
    }

    /**
     * A type that only takes whole days makes the item one, whatever the form said.
     *
     * @return void
     */
    public function testWholeDayTypeIgnoresTimes(): void
    {
        $this->post('/work-reports/work-report-items/add', [
            'work_report_item_type_id' => WorkReportItemTypesFixture::VACATION,
            'date' => '2026-06-15',
            'time_from' => '08:00',
            'time_until' => '16:00',
        ]);

        $this->assertRedirectContains('/work-reports/work-reports/sheet');

        /** @var \WorkReports\Model\Entity\WorkReportItem $item */
        $item = $this->getTableLocator()->get('WorkReports.WorkReportItems')->find()->firstOrFail();
        $this->assertTrue($item->whole_day);
        $this->assertNull($item->work_from);
    }

    /**
     * Work without a description is not taken, since its type asks for one.
     *
     * @return void
     */
    public function testDescriptionRequiredByType(): void
    {
        $this->post('/work-reports/work-report-items/add', [
            'work_report_item_type_id' => WorkReportItemTypesFixture::WORK,
            'date' => '2026-06-01',
            'time_from' => '08:00',
            'time_until' => '09:00',
        ]);

        $this->assertResponseOk();
        $this->assertSame(0, $this->getTableLocator()->get('WorkReports.WorkReportItems')->find()->count());
    }

    /**
     * Somebody else's month is not for a worker to see, unless they get their reports.
     *
     * @return void
     */
    public function testOthersAreNotToBeSeen(): void
    {
        $other = $this->otherUser();

        $this->get('/work-reports/work-reports/sheet?user_id=' . $other);
        $this->assertResponseCode(403);

        $this->addRecipient($other, self::WORKER, mayEdit: false);

        $this->get('/work-reports/work-reports/sheet?user_id=' . $other);
        $this->assertResponseOk();
    }

    /**
     * A recipient who may only see does not add to the month, one who may edit does.
     *
     * @return void
     */
    public function testRecipientEditsOnlyWhenAllowed(): void
    {
        $other = $this->otherUser();
        $recipient = $this->addRecipient($other, self::WORKER, mayEdit: false);
        $data = [
            'work_report_item_type_id' => WorkReportItemTypesFixture::VACATION,
            'date' => '2026-06-15',
        ];

        $this->post('/work-reports/work-report-items/add?user_id=' . $other, $data);
        $this->assertResponseCode(403);

        $recipients = $this->getTableLocator()->get('WorkReports.WorkReportWorkerRecipients');
        $recipient->set('may_edit', true);
        $recipients->saveOrFail($recipient);

        $this->post('/work-reports/work-report-items/add?user_id=' . $other, $data);
        $this->assertRedirectContains('/work-reports/work-reports/sheet');
    }

    /**
     * Whether an item was invoiced is not the worker's to say, only theirs who invoice.
     *
     * @return void
     */
    public function testOnlyThoseWhoInvoiceMarkInvoiced(): void
    {
        $data = [
            'work_report_item_type_id' => WorkReportItemTypesFixture::VACATION,
            'date' => '2026-06-15',
            'invoiced' => '1',
        ];
        $items = $this->getTableLocator()->get('WorkReports.WorkReportItems');

        $this->get('/work-reports/work-report-items/add?date=2026-06-15');
        $this->assertResponseNotContains('name="invoiced"');

        $this->post('/work-reports/work-report-items/add', $data);
        $this->assertFalse($items->find()->firstOrFail()->get('invoiced'));

        $this->loginAs(self::WORKER, 'bookkeeper', superuser: false);
        $this->post('/work-reports/work-report-items/add', ['date' => '2026-06-16'] + $data);
        $this->assertTrue($items->find()->where(['date' => '2026-06-16'])->firstOrFail()->get('invoiced'));
    }

    /**
     * A day on call is worth what its kind is, and marking it again takes it back.
     *
     * @return void
     */
    public function testOnCall(): void
    {
        $onCalls = $this->getTableLocator()->get('WorkReports.WorkReportOnCalls');
        $toggle = fn(string $date) => $this->post('/work-reports/work-report-on-calls/toggle', ['date' => $date]);

        // a Monday and a Saturday
        $toggle('2026-06-08');
        $toggle('2026-06-13');
        $this->assertRedirectContains('/work-reports/work-reports/sheet');

        $hours = [];
        foreach ($onCalls->find()->orderBy(['date'])->all() as $onCall) {
            $hours[$onCall->get('date')->format('Y-m-d')] = $onCall->get('hours')->toFloat();
        }
        $this->assertSame(['2026-06-08' => 3.0, '2026-06-13' => 12.0], $hours);

        $this->get('/work-reports/work-reports/sheet?month=2026-06');
        $this->assertResponseContains('On Call - Weekend');

        $toggle('2026-06-13');
        $this->assertSame(1, $onCalls->find()->count());
    }

    /**
     * Work begun with no until runs until it is finished, one at a time, and the month is not
     * submitted while it does.
     *
     * @return void
     */
    public function testRunningWork(): void
    {
        $items = $this->getTableLocator()->get('WorkReports.WorkReportItems');
        $reports = $this->getTableLocator()->get('WorkReports.WorkReports');
        $report = $this->monthOfVacation(except: '2026-06-30');
        $start = [
            'work_report_item_type_id' => WorkReportItemTypesFixture::WORK,
            'date' => '2026-06-30',
            'time_from' => '14:05',
            'time_until' => '',
            'description' => 'Router upgrade',
        ];

        $this->post('/work-reports/work-report-items/add', $start);
        $this->assertRedirectContains('/work-reports/work-reports/sheet');
        /** @var \WorkReports\Model\Entity\WorkReportItem $running */
        $running = $items->find()->where(['description' => 'Router upgrade'])->firstOrFail();
        $this->assertTrue($running->isRunning());

        // a second one is not begun while the first goes on
        $this->post('/work-reports/work-report-items/add', ['description' => 'Second'] + $start);
        $this->assertResponseOk();
        $this->assertFalse($items->exists(['description' => 'Second']));

        $this->get('/work-reports/work-reports/sheet?month=2026-06');
        $this->assertResponseContains('Work in progress since');
        $this->assertResponseContains('<td colspan="2" style="background-color: var(--color-message-error-bg);');

        $this->post('/work-reports/work-reports/submit/' . $report->id);
        $this->assertNull($reports->get($report->id)->submitted);
        $this->assertNoMailSent();

        $was = DateTime::getTestNow();
        DateTime::setTestNow(new DateTime('2026-06-30 18:00:00'));
        $this->post('/work-reports/work-report-items/finish/' . $running->id);
        DateTime::setTestNow($was);
        $this->assertRedirectContains('/work-reports/work-reports/sheet');
        /** @var \WorkReports\Model\Entity\WorkReportItem $finished */
        $finished = $items->get($running->id);
        $this->assertFalse($finished->isRunning());
    }

    /**
     * Work may run past midnight within the month, but not over its end, where it is split by hand
     * - finishing running work included.
     *
     * @return void
     */
    public function testNotOverTheEndOfTheMonth(): void
    {
        $items = $this->getTableLocator()->get('WorkReports.WorkReportItems');
        $work = fn(string $date, string $from, string $until, string $description): array => [
            'work_report_item_type_id' => WorkReportItemTypesFixture::WORK,
            'date' => $date,
            'time_from' => $from,
            'time_until' => $until,
            'description' => $description,
        ];

        $this->post('/work-reports/work-report-items/add', $work('2026-06-15', '22:00', '01:30', 'Mid month'));
        $this->post('/work-reports/work-report-items/add', $work('2026-06-30', '22:00', '00:00', 'Up to midnight'));
        $this->post('/work-reports/work-report-items/add', $work('2026-06-30', '23:00', '01:30', 'Over the end'));
        $this->assertTrue($items->exists(['description' => 'Mid month']));
        $this->assertTrue($items->exists(['description' => 'Up to midnight']));
        $this->assertFalse($items->exists(['description' => 'Over the end']));
    }

    /**
     * Work left running is not finished over the end of the month either.
     *
     * @return void
     */
    public function testFinishingDoesNotCrossTheMonthEnd(): void
    {
        $items = $this->getTableLocator()->get('WorkReports.WorkReportItems');

        $this->post('/work-reports/work-report-items/add', [
            'work_report_item_type_id' => WorkReportItemTypesFixture::WORK,
            'date' => '2026-06-30',
            'time_from' => '23:00',
            'time_until' => '',
            'description' => 'Left running',
        ]);
        /** @var \WorkReports\Model\Entity\WorkReportItem $running */
        $running = $items->find()->where(['description' => 'Left running'])->firstOrFail();

        $was = DateTime::getTestNow();
        DateTime::setTestNow(new DateTime('2026-07-01 00:30:00'));
        $this->post('/work-reports/work-report-items/finish/' . $running->id);
        DateTime::setTestNow($was);

        $this->assertTrue($items->get($running->id)->isRunning());
    }

    /**
     * Starting now fills in the day and the minute, and the form asks for no seconds.
     *
     * @return void
     */
    public function testStartNow(): void
    {
        $was = DateTime::getTestNow();
        DateTime::setTestNow(new DateTime('2026-06-30 14:05:37'));

        $this->get('/work-reports/work-report-items/add?start=now');

        DateTime::setTestNow($was);
        $this->assertResponseOk();
        $this->assertResponseContains('name="time_from" step="60" id="time-from" value="14:05"');
        $this->assertResponseContains('value="2026-06-30"');
    }

    /**
     * A month with a working day left empty is not taken.
     *
     * @return void
     */
    public function testSubmitStopsOnMissingDays(): void
    {
        $report = $this->monthOfVacation(except: '2026-06-30');

        $this->post('/work-reports/work-reports/submit/' . $report->id);

        $this->assertRedirectContains('/work-reports/work-reports/sheet');
        $this->assertFlashElement('flash/error');
        $this->assertNull($this->getTableLocator()->get('WorkReports.WorkReports')->get($report->id)->submitted);
        $this->assertNoMailSent();
    }

    /**
     * A full month is submitted and sent to the worker and whoever gets their reports.
     *
     * @return void
     */
    public function testSubmitSendsTheReport(): void
    {
        $other = $this->otherUser();
        $this->addRecipient(self::WORKER, $other, mayEdit: false);
        $report = $this->monthOfVacation();

        $this->post('/work-reports/work-reports/submit/' . $report->id);

        $this->assertRedirectContains('/work-reports/work-reports/sheet');
        $submitted = $this->getTableLocator()->get('WorkReports.WorkReports')->get($report->id);
        $this->assertNotNull($submitted->submitted);
        // the month is over, so submitting closes it whole
        $this->assertEquals(new Date('2026-06-30'), $submitted->closed_until);
        $this->assertMailCount(1);
        $this->assertMailSentTo('operator@example.com');
        $this->assertMailSentTo('other@example.com');
        $this->assertMailContains('Vacation');
    }

    /**
     * The worker does not return their own report, a recipient who may edit does.
     *
     * @return void
     */
    public function testReopen(): void
    {
        $other = $this->otherUser();
        $report = $this->monthOfVacation();
        $reports = $this->getTableLocator()->get('WorkReports.WorkReports');
        $report->set('submitted', DateTime::now());
        $reports->saveOrFail($report);

        $this->post('/work-reports/work-reports/reopen/' . $report->id);
        $this->assertResponseCode(403);

        $this->addRecipient(self::WORKER, $other, mayEdit: true);
        $this->loginAs($other, 'user');

        $this->get('/work-reports/work-reports/reopen/' . $report->id);
        $this->assertResponseOk();
        $this->assertResponseContains('return_reason');

        $this->post('/work-reports/work-reports/reopen/' . $report->id, ['return_reason' => '']);
        $this->assertResponseOk();
        $this->assertNotNull($reports->get($report->id)->submitted);
        $this->assertNoMailSent();

        $this->post('/work-reports/work-reports/reopen/' . $report->id, ['return_reason' => 'The 15th is a workday.']);

        $this->assertRedirectContains('/work-reports/work-reports/sheet');
        $returned = $reports->get($report->id);
        $this->assertNull($returned->submitted);
        $this->assertSame($other, $returned->returned_by);
        $this->assertSame('The 15th is a workday.', $returned->return_reason);
        $this->assertMailCount(1);
        $this->assertMailSentTo('operator@example.com');
        $this->assertMailContains('The 15th is a workday.');

        $this->loginAs(self::WORKER, 'user');
        $this->get('/work-reports/work-reports/sheet?month=2026-06');
        $this->assertResponseContains('The 15th is a workday.');
    }

    /**
     * Once the report is submitted, its items stay as they were - except whether they were invoiced.
     *
     * @return void
     */
    public function testSubmittedReportIsLocked(): void
    {
        $reports = $this->getTableLocator()->get('WorkReports.WorkReports');
        $items = $this->getTableLocator()->get('WorkReports.WorkReportItems');

        $report = $reports->findOrCreateFor(self::WORKER, new Date('2026-06-01'));
        $item = $items->saveOrFail($items->newEntity([
            'work_report_id' => $report->id,
            'work_report_item_type_id' => WorkReportItemTypesFixture::VACATION,
            'date' => '2026-06-15',
        ]));
        $report->set('submitted', DateTime::now());
        $reports->saveOrFail($report);

        $this->post('/work-reports/work-report-items/delete/' . $item->id);
        $this->assertTrue($items->exists(['id' => $item->id]));

        $item = $items->get($item->id);
        $items->patchEntity($item, ['date' => '2026-06-16']);
        $this->assertFalse($items->save($item));

        $item = $items->get($item->id);
        $items->patchEntity($item, ['invoiced' => true]);
        $this->assertNotFalse($items->save($item));
    }

    /**
     * One person is in one place at a time, so work stated by the clock may not run over other
     * work of theirs. A whole day states no clock and stands beside it.
     *
     * @return void
     */
    public function testTimesDoNotOverlap(): void
    {
        $items = $this->getTableLocator()->get('WorkReports.WorkReportItems');
        $work = fn(string $from, string $until, string $description): array => [
            'work_report_item_type_id' => WorkReportItemTypesFixture::WORK,
            'date' => '2026-06-15',
            'time_from' => $from,
            'time_until' => $until,
            'description' => $description,
        ];

        $this->post('/work-reports/work-report-items/add', $work('08:00', '09:00', 'First'));
        $this->post('/work-reports/work-report-items/add', $work('09:00', '10:00', 'Next to it'));
        $this->post('/work-reports/work-report-items/add', $work('08:30', '09:30', 'Over both'));

        $this->assertTrue($items->exists(['description' => 'First']));
        $this->assertTrue($items->exists(['description' => 'Next to it']));
        $this->assertFalse($items->exists(['description' => 'Over both']));

        $this->post('/work-reports/work-report-items/add', [
            'work_report_item_type_id' => WorkReportItemTypesFixture::VACATION,
            'date' => '2026-06-15',
        ]);
        $this->assertSame(3, $items->find()->count());
    }

    /**
     * Work that is going on holds the time after it until it is finished, while what was forgotten
     * before it still goes in.
     *
     * @return void
     */
    public function testRunningWorkHoldsTheTimeAfterIt(): void
    {
        $items = $this->getTableLocator()->get('WorkReports.WorkReportItems');
        $work = fn(string $from, string $until, string $description): array => [
            'work_report_item_type_id' => WorkReportItemTypesFixture::WORK,
            'date' => '2026-06-15',
            'time_from' => $from,
            'time_until' => $until,
            'description' => $description,
        ];

        $this->post('/work-reports/work-report-items/add', $work('08:00', '', 'Still going on'));
        $this->post('/work-reports/work-report-items/add', $work('10:00', '11:00', 'After it'));
        $this->post('/work-reports/work-report-items/add', $work('06:00', '07:00', 'Before it'));

        $this->assertTrue($items->exists(['description' => 'Still going on']));
        $this->assertFalse($items->exists(['description' => 'After it']));
        $this->assertTrue($items->exists(['description' => 'Before it']));
    }

    /**
     * What has not happened yet is not reported, and a month still ahead offers nothing to report
     * it with either.
     *
     * @return void
     */
    public function testNothingIsReportedAhead(): void
    {
        $items = $this->getTableLocator()->get('WorkReports.WorkReportItems');
        $onCalls = $this->getTableLocator()->get('WorkReports.WorkReportOnCalls');
        $today = Date::today();
        $tomorrow = $today->addDays(1);

        $this->post('/work-reports/work-report-items/add', [
            'work_report_item_type_id' => WorkReportItemTypesFixture::VACATION,
            'date' => $tomorrow->format('Y-m-d'),
        ]);
        $this->assertFalse($items->exists(['date' => $tomorrow->format('Y-m-d')]));

        // the clock does not run past this moment either
        $was = DateTime::getTestNow();
        DateTime::setTestNow(new DateTime($today->format('Y-m-d') . ' 10:00:00'));
        $this->post('/work-reports/work-report-items/add', [
            'work_report_item_type_id' => WorkReportItemTypesFixture::WORK,
            'date' => $today->format('Y-m-d'),
            'time_from' => '12:00',
            'time_until' => '13:00',
            'description' => 'Not yet worked',
        ]);
        DateTime::setTestNow($was);
        $this->assertFalse($items->exists(['description' => 'Not yet worked']));

        // a day on call that has not come is not marked, and no month comes to be for it
        $reports = $this->getTableLocator()->get('WorkReports.WorkReports');
        $months = $reports->find()->count();
        $this->post('/work-reports/work-report-on-calls/toggle', ['date' => $tomorrow->format('Y-m-d')]);
        $this->assertFlashElement('flash/error');
        $this->assertSame(0, $onCalls->find()->count());
        $this->assertSame($months, $reports->find()->count());

        // and a month still ahead is read with nothing to write in it
        $this->get('/work-reports/work-reports/sheet?month=' . $today->addMonths(1)->format('Y-m'));
        $this->assertResponseOk();
        $this->assertResponseNotContains('New Work Report Item');
        $this->assertResponseNotContains('>Add<');
        $this->assertResponseNotContains('>Set<');
        $this->assertResponseNotContains('Nothing is reported on');
    }

    /**
     * A report closed up to a day keeps those days as they are written and goes on being filled in
     * above them. The worker moves the day forward, a supervisor also back.
     *
     * @return void
     */
    public function testClosingTheReportUpToADay(): void
    {
        $reports = $this->getTableLocator()->get('WorkReports.WorkReports');
        $items = $this->getTableLocator()->get('WorkReports.WorkReportItems');
        $onCalls = $this->getTableLocator()->get('WorkReports.WorkReportOnCalls');
        $report = $reports->findOrCreateFor(self::WORKER, new Date('2026-06-01'));
        $vacation = fn(string $date): array => [
            'work_report_item_type_id' => WorkReportItemTypesFixture::VACATION,
            'date' => $date,
        ];
        $item = $items->saveOrFail($items->newEntity(
            ['work_report_id' => $report->id] + $vacation('2026-06-10'),
        ));

        // a day outside the month is none of this report's business
        $this->post('/work-reports/work-reports/close/' . $report->id, ['closed_until' => '2026-07-01']);
        $this->assertResponseOk();
        $this->assertNull($reports->get($report->id)->closed_until);

        $this->post('/work-reports/work-reports/close/' . $report->id, ['closed_until' => '2026-06-15']);
        $this->assertRedirectContains('/work-reports/work-reports/sheet');
        $this->assertEquals(new Date('2026-06-15'), $reports->get($report->id)->closed_until);

        // what is under the day stays as it is, what is above it is still written
        $this->post('/work-reports/work-report-items/add', $vacation('2026-06-11'));
        $this->assertFalse($items->exists(['date' => '2026-06-11']));
        $this->post('/work-reports/work-report-items/add', $vacation('2026-06-16'));
        $this->assertTrue($items->exists(['date' => '2026-06-16']));

        $this->post('/work-reports/work-report-items/delete/' . $item->id);
        $this->assertTrue($items->exists(['id' => $item->id]));

        $this->post('/work-reports/work-report-on-calls/toggle', ['date' => '2026-06-08']);
        $this->assertFlashElement('flash/error');
        $this->assertSame(0, $onCalls->find()->count());

        $this->get('/work-reports/work-reports/sheet?month=2026-06');
        $this->assertResponseContains('Closed up to');

        // the worker does not open again what they closed
        $this->post('/work-reports/work-reports/close/' . $report->id, ['closed_until' => '2026-06-10']);
        $this->assertResponseOk();
        $this->assertEquals(new Date('2026-06-15'), $reports->get($report->id)->closed_until);

        $this->loginAs(self::WORKER, 'admin', superuser: false);
        $this->post('/work-reports/work-reports/close/' . $report->id, ['closed_until' => '']);
        $this->assertRedirectContains('/work-reports/work-reports/sheet');
        $this->assertNull($reports->get($report->id)->closed_until);
    }

    /**
     * The months to look at are those of the people who report work, and one's own.
     *
     * @return void
     */
    public function testOnlyWorkersAreOfferedToLookAt(): void
    {
        $other = $this->otherUser();
        $this->loginAs($other, 'admin', superuser: false);

        $this->get('/work-reports/work-reports/sheet?month=2026-06');
        $this->assertResponseOk();
        $this->assertResponseNotContains(self::WORKER);

        $workers = $this->getTableLocator()->get('WorkReports.WorkReportWorkers');
        $workers->saveOrFail($workers->newEntity([
            'user_id' => self::WORKER,
            'workload' => '1',
            'active' => true,
        ]));

        $this->get('/work-reports/work-reports/sheet?month=2026-06');
        $this->assertResponseContains(self::WORKER);
    }

    /**
     * The note of the month is written on the report, and stays put once it is submitted.
     *
     * @return void
     */
    public function testTheNoteOfTheMonth(): void
    {
        $reports = $this->getTableLocator()->get('WorkReports.WorkReports');
        $report = $reports->findOrCreateFor(self::WORKER, new Date('2026-06-01'));

        $this->get('/work-reports/work-reports/note/' . $report->id);
        $this->assertResponseOk();

        $this->post('/work-reports/work-reports/note/' . $report->id, ['note' => 'Two days at the warehouse.']);
        $this->assertRedirectContains('/work-reports/work-reports/sheet');
        $this->assertSame('Two days at the warehouse.', $reports->get($report->id)->note);

        $this->get('/work-reports/work-reports/sheet?month=2026-06');
        $this->assertResponseContains('Two days at the warehouse.');

        $report = $reports->get($report->id);
        $report->set('submitted', DateTime::now());
        $reports->saveOrFail($report);

        $this->post('/work-reports/work-reports/note/' . $report->id, ['note' => 'Written too late.']);
        $this->assertFlashElement('flash/error');
        $this->assertSame('Two days at the warehouse.', $reports->get($report->id)->note);
    }

    /**
     * June 2026 of the worker, a vacation on every working day.
     *
     * @param string|null $except A day to leave out.
     * @return \WorkReports\Model\Entity\WorkReport
     */
    private function monthOfVacation(?string $except = null): WorkReport
    {
        $reports = $this->getTableLocator()->get('WorkReports.WorkReports');
        $items = $this->getTableLocator()->get('WorkReports.WorkReportItems');
        $report = $reports->findOrCreateFor(self::WORKER, new Date('2026-06-01'));

        foreach ((new WorkingCalendar('CzechRepublic'))->workingDays(new Date('2026-06-01')) as $day) {
            if ($day->format('Y-m-d') === $except) {
                continue;
            }
            $items->saveOrFail($items->newEntity([
                'work_report_id' => $report->id,
                'work_report_item_type_id' => WorkReportItemTypesFixture::VACATION,
                'date' => $day->format('Y-m-d'),
            ]));
        }

        return $report;
    }

    /**
     * Have one user get the reports of another.
     *
     * @param string $workerId Worker.
     * @param string $recipientId Who gets the reports.
     * @param bool $mayEdit Whether they may change them.
     * @return \WorkReports\Model\Entity\WorkReportWorkerRecipient
     */
    private function addRecipient(string $workerId, string $recipientId, bool $mayEdit): WorkReportWorkerRecipient
    {
        $workers = $this->getTableLocator()->get('WorkReports.WorkReportWorkers');
        $worker = $workers->find()->where(['user_id' => $workerId])->first()
            ?? $workers->saveOrFail($workers->newEntity(['user_id' => $workerId]));

        $recipients = $this->getTableLocator()->get('WorkReports.WorkReportWorkerRecipients');

        return $recipients->saveOrFail($recipients->newEntity([
            'work_report_worker_id' => $worker->get('id'),
            'user_id' => $recipientId,
            'may_edit' => $mayEdit,
        ]));
    }

    /**
     * Log a user in, as the application knows them.
     *
     * @param string $id User id.
     * @param string $role Role to act as.
     * @param bool $superuser Whether the permissions let them anywhere.
     * @return void
     */
    private function loginAs(string $id, string $role, bool $superuser = true): void
    {
        $user = $this->getTableLocator()->get('AppUsers')->get($id);
        $user->role = $role;
        // the user of the fixture is a superuser, whom the permissions do not stop
        $user->set('is_superuser', $superuser);

        $this->session(['Auth' => $user]);
    }

    /**
     * A second user to report work.
     *
     * @return string
     */
    private function otherUser(): string
    {
        $users = $this->getTableLocator()->get('AppUsers');
        $user = $users->newEmptyEntity();
        $user->username = 'other';
        // the fixture states its number itself, so the sequence would hand out the same one
        $user->set('nid', 2);
        $user->email = 'other@example.com';
        $user->set('password', 'secret', ['setter' => false]);
        $user->role = 'user';
        $user->active = true;
        $users->saveOrFail($user, ['validate' => false, 'checkRules' => false]);

        return $user->id;
    }
}
