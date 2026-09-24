<?php
declare(strict_types=1);

namespace WorkReports\Test\TestCase\Controller;

use App\Test\Traits\ConfigureTestTrait;
use App\Test\Traits\ControllerTestTrait;
use Cake\Cache\Cache;
use Cake\Http\TestSuite\HttpClientTrait;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;
use WorkReports\Controller\WorkOverviewsController;
use WorkReports\Model\Entity\WorkReportItem;
use WorkReports\Test\Fixture\WorkReportItemTypesFixture;

/**
 * WorkReports\Controller\WorkOverviewsController Test Case
 *
 * @link \WorkReports\Controller\WorkOverviewsController
 */
#[UsesClass(WorkOverviewsController::class)]
class WorkOverviewsControllerTest extends TestCase
{
    use ConfigureTestTrait;
    use ControllerTestTrait;
    use HttpClientTrait;
    use IntegrationTestTrait;

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

        $user = $this->getTableLocator()->get('AppUsers')->get(self::WORKER);
        $user->role = 'bookkeeper';
        $this->session(['Auth' => $user]);

        // whoever reports work is on the list of workers, which is what says the months are theirs
        $workers = $this->getTableLocator()->get('WorkReports.WorkReportWorkers');
        $workers->saveOrFail($workers->newEntity(['user_id' => self::WORKER, 'workload' => '1', 'active' => true]));

        $this->enableCsrfToken();
        $this->enableSecurityToken();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        Cache::clear('api_client');
        $this->restoreConfigure();

        parent::tearDown();
    }

    /**
     * The work to invoice is listed with what it comes to, what is invoiced is left out.
     *
     * @return void
     */
    public function testToInvoice(): void
    {
        $this->item('Router replaced', invoiced: false);
        $this->item('Already on an invoice', invoiced: true, from: '13:00');

        $this->get('/work-reports/work-overviews/to-invoice');

        $this->assertResponseOk();
        $this->assertResponseContains('Router replaced');
        $this->assertResponseNotContains('Already on an invoice');
        // 1.5 hours by 600 by 3 for a Sunday
        $this->assertResponseContains('2,700');
        // not invoiced yet, so it stands out the way an unpaid invoice does
        $this->assertResponseContains('color: red;');
    }

    /**
     * Marking goes through even on a submitted report, which is locked for everything else.
     *
     * @return void
     */
    public function testMarkInvoiced(): void
    {
        $item = $this->item('Router replaced', invoiced: false);
        $reports = $this->getTableLocator()->get('WorkReports.WorkReports');
        $report = $reports->get($item->work_report_id);
        $report->set('submitted', DateTime::now());
        $reports->saveOrFail($report);

        $this->post('/work-reports/work-overviews/mark-invoiced', ['ids' => [$item->id], 'invoiced' => '1']);

        $this->assertRedirect();
        $this->assertTrue($this->getTableLocator()->get('WorkReports.WorkReportItems')->get($item->id)->invoiced);
    }

    /**
     * The work at access points is listed by access point, the rest is left out.
     *
     * @return void
     */
    public function testByAccessPoint(): void
    {
        $atAccessPoint = $this->item('Antenna realigned', invoiced: false);
        $atAccessPoint->set('access_point_id', '5d1e1f9a-8c1b-4d7a-9f3e-2b6c7d8e9f01');
        $this->getTableLocator()->get('WorkReports.WorkReportItems')->saveOrFail($atAccessPoint);
        $this->item('Office work', invoiced: false, from: '13:00');

        $user = $this->getTableLocator()->get('AppUsers')->get(self::WORKER);
        $user->role = 'network-technician';
        $user->set('is_superuser', false);
        $this->session(['Auth' => $user]);

        $this->get('/work-reports/work-overviews/by-access-point');

        $this->assertResponseOk();
        $this->assertResponseContains('Antenna realigned');
        $this->assertResponseNotContains('Office work');
        $this->assertResponseContains('1:24 h');
    }

    /**
     * With the NMS down, the overview and the month still draw, and the access point says it
     * could not be looked up.
     *
     * @return void
     */
    public function testAccessPointsWithTheNmsDown(): void
    {
        $this->withConfigure(['Nms.url' => 'https://nms.example.com', 'Nms.key' => 'secret']);
        Cache::clear('api_client');
        $this->mockClientGet('https://nms.example.com/*', $this->newClientResponse(500));

        $item = $this->item('Antenna realigned', invoiced: false);
        $item->set('access_point_id', '5d1e1f9a-8c1b-4d7a-9f3e-2b6c7d8e9f01');
        $this->getTableLocator()->get('WorkReports.WorkReportItems')->saveOrFail($item);

        $user = $this->getTableLocator()->get('AppUsers')->get(self::WORKER);
        $user->role = 'admin';
        $this->session(['Auth' => $user]);

        foreach (['/work-reports/work-overviews/by-access-point', '/work-reports/work-reports/sheet?month=2026-06'] as $page) {
            $this->get($page);
            $this->assertResponseOk('The page ' . $page . ' did not draw.');
            $this->assertResponseContains('warning-text');
        }
    }

    /**
     * A worker without the role does not get to the overview.
     *
     * @return void
     */
    public function testOnlyForThoseWhoInvoice(): void
    {
        $user = $this->getTableLocator()->get('AppUsers')->get(self::WORKER);
        $user->role = 'user';
        // the user of the fixture is a superuser, whom the permissions do not stop
        $user->set('is_superuser', false);
        $this->session(['Auth' => $user]);

        $this->get('/work-reports/work-overviews/to-invoice');

        $this->assertResponseNotContains('Work to Invoice');
    }

    /**
     * An item to invoice, 1.5 hours at a rate of 600 on a Sunday.
     *
     * @param string $description What was done.
     * @param bool $invoiced Whether it is invoiced already.
     * @param string $from When it was begun, since one worker does not do two things at once.
     * @return \WorkReports\Model\Entity\WorkReportItem
     */
    private function item(string $description, bool $invoiced, string $from = '09:50'): WorkReportItem
    {
        $locator = $this->getTableLocator();
        $rates = $locator->get('WorkReports.WorkRates');
        $rate = $rates->find()->where(['code' => 'A'])->first()
            ?? $rates->saveOrFail($rates->newEntity(['code' => 'A', 'price' => '600']));
        $report = $locator->get('WorkReports.WorkReports')->findOrCreateFor(self::WORKER, new Date('2026-06-01'));
        $items = $locator->get('WorkReports.WorkReportItems');

        /** @var \WorkReports\Model\Entity\WorkReportItem */
        return $items->saveOrFail($items->newEntity([
            'work_report_id' => $report->id,
            'work_report_item_type_id' => WorkReportItemTypesFixture::WORK,
            'date' => '2026-06-21',
            'time_from' => $from,
            'time_until' => (new DateTime('2026-06-21 ' . $from))->addMinutes(84)->format('H:i'),
            'description' => $description,
            'to_invoice' => true,
            'invoice_hours' => '1.5',
            'work_rate_id' => $rate->get('id'),
            'rate_multiplier' => '3',
            'invoiced' => $invoiced,
        ]));
    }
}
