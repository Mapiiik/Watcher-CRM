<?php
declare(strict_types=1);

namespace WorkReports\Test\TestCase\Model\Table;

use App\Test\Traits\ControllerTestTrait;
use Cake\Event\EventManager;
use Cake\I18n\Date;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\TestWith;
use WorkReports\Event\ReferencesListener;
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
    use ControllerTestTrait;
    use IntegrationTestTrait;

    /**
     * The user of the fixture, who reports the work here.
     *
     * @var string
     */
    private const WORKER = '11edb519-be76-4d66-aea0-34188d31eae1';

    /**
     * A customer of the fixtures with nothing else of theirs in the tables loaded here.
     *
     * @var string
     */
    private const FREE_CUSTOMER_ID = 'ae128a49-82fd-4b80-921f-f11af75fd113';

    /**
     * A contract of the fixtures with nothing else of its own in the tables loaded here.
     *
     * @var string
     */
    private const FREE_CONTRACT_ID = '9c0d5e5c-2a6b-4f8e-9a3d-1b7c4e2f6a90';

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
        'app.AccountingProfiles',
        'app.Customers',
        'app.Countries',
        'app.Addresses',
        'app.Commissions',
        'app.ContractStates',
        'app.ServiceTypes',
        'app.Contracts',
        'app.TaskTypes',
        'app.TaskStates',
        'app.Tasks',
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
     * What reported work names stays, with an answer rather than the error page the foreign key
     * would make of it, and goes again once nothing names it.
     *
     * @param string $alias The table of what the work names.
     * @param string $column The column of the work.
     * @param string|null $id What it names, null for the first the fixtures have.
     * @return void
     * @link \WorkReports\Event\ReferencesListener::addRules()
     */
    #[TestWith(['Customers', 'customer_id', self::FREE_CUSTOMER_ID])]
    #[TestWith(['Contracts', 'contract_id', self::FREE_CONTRACT_ID])]
    #[TestWith(['Tasks', 'task_id', null])]
    public function testWhatReportedWorkNamesStays(string $alias, string $column, ?string $id): void
    {
        EventManager::instance()->on(new ReferencesListener());

        $table = $this->getTableLocator()->get($alias);
        $id ??= (string)$table->find()->firstOrFail()->get('id');
        $item = $this->workNaming($column, $id);

        $record = $table->get($id);

        $this->assertFalse($table->delete($record));
        $this->assertArrayHasKey('hasNoReportedWork', $record->getError('id'));

        $this->getTableLocator()->get('WorkReports.WorkReportItems')->deleteAll(['id' => $item]);

        $this->assertTrue($table->delete($table->get($id)));
    }

    /**
     * The application reaches the rule without being told, as the plugin hands it over on loading.
     *
     * @return void
     * @link \WorkReports\WorkReportsPlugin::bootstrap()
     */
    public function testThePluginHandsTheRulesOver(): void
    {
        $this->workNaming('customer_id', self::FREE_CUSTOMER_ID);

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();
        $this->post('/customers/' . self::FREE_CUSTOMER_ID . '/delete');

        $this->assertRedirect();
        $this->assertFlashMessageContains('Reported work names this record, so it cannot be deleted.');
        $this->assertTrue($this->getTableLocator()->get('Customers')->exists(['id' => self::FREE_CUSTOMER_ID]));
    }

    /**
     * An item of a month of the worker, naming what is asked about.
     *
     * @param string $column What it names it in.
     * @param string $id What it names.
     * @return string The item.
     */
    private function workNaming(string $column, string $id): string
    {
        // the fixtures name that customer as the technician of the contracts
        $this->getTableLocator()->get('Contracts')->updateAll(
            ['installation_technician_id' => null, 'uninstallation_technician_id' => null],
            ['1 = 1'],
        );

        $report = $this->reports()->findOrCreateFor(self::WORKER, new Date('2026-06-01'));
        $items = $this->getTableLocator()->get('WorkReports.WorkReportItems');
        $item = $items->newEntity([
            'work_report_id' => $report->id,
            'work_report_item_type_id' => WorkReportItemTypesFixture::VACATION,
            'date' => '2026-06-15',
        ]);
        $item->set($column, $id);
        $items->saveOrFail($item, ['checkRules' => false]);

        return (string)$item->get('id');
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
