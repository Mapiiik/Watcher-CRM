<?php
declare(strict_types=1);

namespace WorkReports\Test\TestCase\Controller;

use App\Test\Traits\ControllerTestTrait;
use Cake\I18n\Date;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use WorkReports\Test\Fixture\WorkReportItemTypesFixture;

/**
 * Every page of the plugin is drawn, filled with a record where it shows one.
 */
class PagesRenderTest extends TestCase
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
        $user->role = 'admin';
        $this->session(['Auth' => $user]);
        $this->enableCsrfToken();
        $this->enableSecurityToken();
    }

    /**
     * The lookup tables.
     *
     * @return array<string, array{string}>
     */
    public static function lookups(): array
    {
        return [
            'types' => ['work-report-item-types'],
            'rates' => ['work-rates'],
            'labels' => ['work-labels'],
            'cars' => ['work-cars'],
            'workers' => ['work-report-workers'],
        ];
    }

    /**
     * @param string $path Controller in the address.
     * @return void
     */
    #[DataProvider('lookups')]
    public function testLookupPages(string $path): void
    {
        $this->seed();

        $this->get('/work-reports/' . $path);
        $this->assertResponseOk();

        $this->get('/work-reports/' . $path . '/add');
        $this->assertResponseOk();
    }

    /**
     * @return void
     */
    public function testEditPagesOfLookups(): void
    {
        $seeded = $this->seed();
        unset($seeded['item']);

        foreach ($seeded as $path => $id) {
            $this->get('/work-reports/' . $path . '/edit/' . $id);
            $this->assertResponseOk($path);
        }
    }

    /**
     * @return void
     */
    public function testWorkerAndRecipientPages(): void
    {
        $seeded = $this->seed();
        $workerId = $seeded['work-report-workers'];

        $this->get('/work-reports/work-report-worker-recipients/add?work_report_worker_id=' . $workerId);
        $this->assertResponseOk();

        $this->post('/work-reports/work-report-worker-recipients/add?work_report_worker_id=' . $workerId, [
            'work_report_worker_id' => $workerId,
            'user_id' => self::WORKER,
            'may_edit' => true,
        ]);
        $this->assertRedirectContains('/work-reports/work-report-workers/view/' . $workerId);

        $this->get('/work-reports/work-report-workers/view/' . $workerId);
        $this->assertResponseOk();
        $this->assertResponseContains('May Edit');

        $this->get('/work-reports/work-report-workers');
        $this->assertResponseOk();
    }

    /**
     * @return void
     */
    public function testReportPages(): void
    {
        $seeded = $this->seed();

        $this->get('/work-reports/work-reports');
        $this->assertResponseOk();

        $this->get('/work-reports/work-reports/sheet?month=2026-06');
        $this->assertResponseOk();
        $this->assertResponseContains('Octavia');
        $this->assertResponseContains('New TV customer');

        $this->get('/work-reports/work-report-items/add?date=2026-06-02');
        $this->assertResponseOk();

        $this->get('/work-reports/work-report-items/edit/' . $seeded['item']);
        $this->assertResponseOk();
    }

    /**
     * One record of every table, the item using all of them.
     *
     * @return array<string, string>
     */
    private function seed(): array
    {
        $locator = $this->getTableLocator();

        $car = $locator->get('WorkReports.WorkCars')->saveOrFail(
            $locator->get('WorkReports.WorkCars')->newEntity(['name' => 'Octavia', 'license_plate' => '1AB 2345']),
        );
        $rate = $locator->get('WorkReports.WorkRates')->saveOrFail(
            $locator->get('WorkReports.WorkRates')->newEntity(['code' => 'A', 'name' => 'Administrator', 'price' => '650']),
        );
        $label = $locator->get('WorkReports.WorkLabels')->saveOrFail(
            $locator->get('WorkReports.WorkLabels')->newEntity(['name' => 'New TV customer', 'color' => '#88cc88']),
        );
        $worker = $locator->get('WorkReports.WorkReportWorkers')->saveOrFail(
            $locator->get('WorkReports.WorkReportWorkers')->newEntity([
                'user_id' => self::WORKER,
                'workload' => '0.5',
                'default_company_car_id' => $car->id,
            ]),
        );

        $report = $locator->get('WorkReports.WorkReports')->findOrCreateFor(self::WORKER, new Date('2026-06-01'));
        $item = $locator->get('WorkReports.WorkReportItems')->saveOrFail(
            $locator->get('WorkReports.WorkReportItems')->newEntity([
                'work_report_id' => $report->id,
                'work_report_item_type_id' => WorkReportItemTypesFixture::WORK,
                'date' => '2026-06-01',
                'time_from' => '08:00',
                'time_until' => '12:30',
                'description' => 'Router replaced',
                'company_car_id' => $car->id,
                'company_car_distance' => 42,
                'cash_collected' => '150',
                'to_invoice' => true,
                'invoice_hours' => '1.5',
                'work_rate_id' => $rate->id,
                'rate_multiplier' => '3',
                'work_labels' => ['_ids' => [$label->id]],
            ]),
        );

        return [
            'work-report-item-types' => WorkReportItemTypesFixture::WORK,
            'work-rates' => $rate->id,
            'work-labels' => $label->id,
            'work-cars' => $car->id,
            'work-report-workers' => $worker->id,
            'item' => $item->id,
        ];
    }
}
