<?php
declare(strict_types=1);

namespace WorkReports\Test\TestCase\View\Cell;

use App\Test\Traits\ControllerTestTrait;
use Cake\I18n\Date;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;
use WorkReports\Test\Fixture\WorkReportItemTypesFixture;
use WorkReports\View\Cell\WorkReportItemsCell;

/**
 * WorkReports\View\Cell\WorkReportItemsCell Test Case
 *
 * The cell lists the work done at a customer or on a contract, and is rendered on their pages. It
 * is exercised through them: its links go through `AuthLink`, which asks the authorization service
 * in the request, and outside a request there is nothing to ask.
 */
#[UsesClass(WorkReportItemsCell::class)]
class WorkReportItemsCellTest extends TestCase
{
    use ControllerTestTrait;
    use IntegrationTestTrait;

    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

    private const WORKER = '11edb519-be76-4d66-aea0-34188d31eae1';

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.AppUsers',
        'app.AccountingProfiles',
        'app.Customers',
        'app.Countries',
        'app.Addresses',
        'app.Commissions',
        'app.ContractStates',
        'app.ServiceTypes',
        'app.Contracts',
        'app.ContractVersions',
        'app.CustomerProposals',
        'app.ContractProposals',
        'app.ConnectionProfiles',
        'app.Services',
        'app.Billings',
        'app.EquipmentTypes',
        'app.BorrowedEquipments',
        'app.Emails',
        'app.Labels',
        'app.CustomerLabels',
        'app.Logins',
        'app.Phones',
        'app.SoldEquipments',
        'app.IpAddresses',
        'app.RemovedIpAddresses',
        'app.IpNetworks',
        'app.RemovedIpNetworks',
        'app.TaskStates',
        'app.TaskTypes',
        'app.Tasks',
        'app.TaskCollaborators',
        'app.DealerCommissions',
        'plugin.WorkReports.WorkReportItemTypes',
        'plugin.WorkReports.WorkReports',
        'plugin.WorkReports.WorkReportItems',
        'plugin.WorkReports.WorkRates',
        'plugin.WorkReports.WorkLabels',
        'plugin.WorkReports.WorkReportItemLabels',
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

        $locator = $this->getTableLocator();
        $report = $locator->get('WorkReports.WorkReports')->findOrCreateFor(self::WORKER, new Date('2026-06-01'));
        $items = $locator->get('WorkReports.WorkReportItems');
        $items->saveOrFail($items->newEntity([
            'work_report_id' => $report->id,
            'work_report_item_type_id' => WorkReportItemTypesFixture::WORK,
            'date' => '2026-06-03',
            'time_from' => '14:27',
            'time_until' => '16:13',
            'description' => 'Banking apps on two phones',
            'customer_id' => self::CUSTOMER_ID,
            'contract_id' => self::CONTRACT_ID,
        ]));
    }

    /**
     * The customer's page lists the work, with its contract.
     *
     * @return void
     */
    public function testOnCustomer(): void
    {
        $this->get('/customers/view/' . self::CUSTOMER_ID);

        $this->assertResponseOk();
        $this->assertResponseContains('id="work-report-items"');
        $this->assertResponseContains('Banking apps on two phones');
    }

    /**
     * An item added from the customer's page belongs to the customer, and the page is where the
     * form leads back to.
     *
     * @return void
     */
    public function testAddFromCustomer(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $url = '/work-reports/customers/' . self::CUSTOMER_ID . '/work-report-items/add';

        $this->get($url);
        $this->assertResponseOk();

        // the customer's tasks are offered by their number and subject, not by their ids
        $task = $this->getTableLocator()->get('Tasks')->find()->where(['customer_id' => self::CUSTOMER_ID])->firstOrFail();
        $this->assertResponseContains('#' . $task->get('number') . ' - ');
        $this->assertResponseContains('Lorem ipsum dolor sit amet</option>');
        $this->assertResponseNotContains('>' . $task->get('id') . '</option>');

        $this->post($url, [
            'work_report_item_type_id' => WorkReportItemTypesFixture::WORK,
            'date' => '2026-06-04',
            'time_from' => '10:16',
            'time_until' => '11:08',
            'description' => 'Network drive full',
        ]);

        $this->assertRedirectContains('/customers/' . self::CUSTOMER_ID);
        $item = $this->getTableLocator()->get('WorkReports.WorkReportItems')
            ->find()
            ->where(['description' => 'Network drive full'])
            ->firstOrFail();
        $this->assertSame(self::CUSTOMER_ID, $item->get('customer_id'));
    }

    /**
     * The contract's page lists the work as well.
     *
     * @return void
     */
    public function testOnContract(): void
    {
        $this->get('/customers/' . self::CUSTOMER_ID . '/contracts/view/' . self::CONTRACT_ID);

        $this->assertResponseOk();
        $this->assertResponseContains('Banking apps on two phones');
    }
}
