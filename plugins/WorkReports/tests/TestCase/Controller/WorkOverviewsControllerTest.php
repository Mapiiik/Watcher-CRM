<?php
declare(strict_types=1);

namespace WorkReports\Test\TestCase\Controller;

use App\Test\Traits\ControllerTestTrait;
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
    use ControllerTestTrait;
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
        $this->enableCsrfToken();
        $this->enableSecurityToken();
    }

    /**
     * The work to invoice is listed with what it comes to, what is invoiced is left out.
     *
     * @return void
     */
    public function testToInvoice(): void
    {
        $this->item('Router replaced', invoiced: false);
        $this->item('Already on an invoice', invoiced: true);

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
     * @return \WorkReports\Model\Entity\WorkReportItem
     */
    private function item(string $description, bool $invoiced): WorkReportItem
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
            'time_from' => '09:50',
            'time_until' => '11:14',
            'description' => $description,
            'to_invoice' => true,
            'invoice_hours' => '1.5',
            'work_rate_id' => $rate->get('id'),
            'rate_multiplier' => '3',
            'invoiced' => $invoiced,
        ]));
    }
}
