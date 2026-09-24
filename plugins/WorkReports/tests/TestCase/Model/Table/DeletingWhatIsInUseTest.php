<?php
declare(strict_types=1);

namespace WorkReports\Test\TestCase\Model\Table;

use Cake\I18n\Date;
use Cake\TestSuite\TestCase;
use WorkReports\Model\Table\WorkCarsTable;
use WorkReports\Model\Table\WorkReportsTable;
use WorkReports\Model\Table\WorkReportWorkersTable;
use WorkReports\Test\Fixture\WorkReportItemTypesFixture;

/**
 * What is written stays readable, so the records it hangs on are not deleted out from under it.
 *
 * @link \WorkReports\Model\Table\WorkReportWorkersTable
 * @link \WorkReports\Model\Table\WorkCarsTable
 */
class DeletingWhatIsInUseTest extends TestCase
{
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
     * A worker with a month behind them is switched off rather than deleted. Their months hang on
     * the user, so deleting the row would leave them with nobody to read them.
     *
     * @return void
     */
    public function testAWorkerWithReportsStays(): void
    {
        $workers = $this->workers();
        $worker = $workers->saveOrFail($workers->newEntity([
            'user_id' => self::WORKER,
            'workload' => '1',
            'active' => true,
        ]));

        $this->reports()->findOrCreateFor(self::WORKER, new Date('2026-06-01'));

        $this->assertFalse($workers->delete($worker));
        $this->assertTrue($workers->exists(['id' => $worker->get('id')]));
    }

    /**
     * A worker who has reported nothing is deleted like any other mistyped record.
     *
     * @return void
     */
    public function testAWorkerWithoutReportsGoes(): void
    {
        $workers = $this->workers();
        $worker = $workers->saveOrFail($workers->newEntity([
            'user_id' => self::WORKER,
            'workload' => '1',
            'active' => true,
        ]));

        $this->assertTrue($workers->delete($worker));
    }

    /**
     * A car that was driven to reported work stays, and so does one a worker drives by default.
     *
     * @return void
     */
    public function testACarInUseStays(): void
    {
        $cars = $this->cars();
        $driven = $cars->saveOrFail($cars->newEntity(['name' => 'Transit', 'license_plate' => '1AB 2345']));
        $usual = $cars->saveOrFail($cars->newEntity(['name' => 'Caddy', 'license_plate' => '6CD 7890']));
        $spare = $cars->saveOrFail($cars->newEntity(['name' => 'Fabia', 'license_plate' => '2EF 3456']));

        $report = $this->reports()->findOrCreateFor(self::WORKER, new Date('2026-06-01'));
        $items = $this->getTableLocator()->get('WorkReports.WorkReportItems');
        $items->saveOrFail($items->newEntity([
            'work_report_id' => $report->id,
            'work_report_item_type_id' => WorkReportItemTypesFixture::VACATION,
            'date' => '2026-06-15',
            'company_car_id' => $driven->get('id'),
            'company_car_distance' => 42,
        ]));

        $workers = $this->workers();
        $workers->saveOrFail($workers->newEntity([
            'user_id' => self::WORKER,
            'workload' => '1',
            'active' => true,
            'default_company_car_id' => $usual->get('id'),
        ]));

        $this->assertFalse($cars->delete($driven));
        $this->assertFalse($cars->delete($usual));
        $this->assertTrue($cars->delete($spare));
    }

    /**
     * @return \WorkReports\Model\Table\WorkReportWorkersTable
     */
    private function workers(): WorkReportWorkersTable
    {
        /** @var \WorkReports\Model\Table\WorkReportWorkersTable */
        return $this->getTableLocator()->get('WorkReports.WorkReportWorkers');
    }

    /**
     * @return \WorkReports\Model\Table\WorkCarsTable
     */
    private function cars(): WorkCarsTable
    {
        /** @var \WorkReports\Model\Table\WorkCarsTable */
        return $this->getTableLocator()->get('WorkReports.WorkCars');
    }

    /**
     * @return \WorkReports\Model\Table\WorkReportsTable
     */
    private function reports(): WorkReportsTable
    {
        /** @var \WorkReports\Model\Table\WorkReportsTable */
        return $this->getTableLocator()->get('WorkReports.WorkReports');
    }
}
