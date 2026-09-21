<?php
declare(strict_types=1);

namespace WorkReports\Test\TestCase\Controller;

use App\Test\Traits\ControllerTestTrait;
use Cake\I18n\Date;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;
use WorkReports\Controller\WorkReportItemsController;
use WorkReports\Controller\WorkReportsController;
use WorkReports\Model\Enum\WorkReportState;
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
        $this->loginAs(self::WORKER, 'user');
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
     * Somebody else's month is not for a worker to see, unless they supervise them.
     *
     * @return void
     */
    public function testOthersAreNotToBeSeen(): void
    {
        $other = $this->otherUser();

        $this->get('/work-reports/work-reports/sheet?user_id=' . $other);
        $this->assertResponseCode(403);

        $workers = $this->getTableLocator()->get('WorkReports.WorkReportWorkers');
        $workers->saveOrFail($workers->newEntity(['user_id' => $other, 'supervisor_id' => self::WORKER]));

        $this->get('/work-reports/work-reports/sheet?user_id=' . $other);
        $this->assertResponseOk();
    }

    /**
     * Once the report is submitted, its items stay as they were - except whether they were charged.
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
        $report->set('state', WorkReportState::Submitted);
        $reports->saveOrFail($report);

        $this->post('/work-reports/work-report-items/delete/' . $item->id);
        $this->assertTrue($items->exists(['id' => $item->id]));

        $item = $items->get($item->id);
        $items->patchEntity($item, ['date' => '2026-06-16']);
        $this->assertFalse($items->save($item));

        $item = $items->get($item->id);
        $items->patchEntity($item, ['charged' => true]);
        $this->assertNotFalse($items->save($item));
    }

    /**
     * Log a user in, as the application knows them.
     *
     * @param string $id User id.
     * @param string $role Role to act as.
     * @return void
     */
    private function loginAs(string $id, string $role): void
    {
        $user = $this->getTableLocator()->get('AppUsers')->get($id);
        $user->role = $role;

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
