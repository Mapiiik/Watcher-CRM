<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Test\Traits\ControllerTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * What a manager is let into beyond the other roles.
 *
 * A manager may do whatever any of the other roles may, and on top of that only what
 * `config/permissions.php` gives it on its own. Each of those is asked about next to a role that
 * has to be refused it - otherwise the test would pass just as well on a page open to everybody.
 */
class ManagerRoleTest extends TestCase
{
    use ControllerTestTrait;
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
        'app.AccountingProfiles',
        'app.Customers',
        'app.Labels',
        'app.CustomerLabels',
        'app.EquipmentTypes',
        'app.TaskStates',
        'app.TaskTypes',
        'plugin.Files.DocumentationTypes',
        'plugin.WorkReports.WorkCars',
        'plugin.WorkReports.WorkLabels',
        'plugin.WorkReports.WorkRates',
        'plugin.WorkReports.WorkReportItemTypes',
        'plugin.WorkReports.WorkReportWorkers',
        'plugin.WorkReports.WorkReportWorkerRecipients',
    ];

    /**
     * The pages given to a manager on their own.
     *
     * @return array<string, array{string}>
     */
    public static function pagesOfTheManager(): array
    {
        return [
            'documentation types' => ['/documentation-types/add'],
            'equipment types' => ['/equipment-types/add'],
            'task states' => ['/task-states/add'],
            'task types' => ['/task-types/add'],
            'work cars' => ['/work-reports/work-cars/add'],
            'work labels' => ['/work-reports/work-labels/add'],
            'work rates' => ['/work-reports/work-rates/add'],
            'work report item types' => ['/work-reports/work-report-item-types/add'],
            'work report workers' => ['/work-reports/work-report-workers/add'],
        ];
    }

    /**
     * @param string $page Address of the page.
     * @return void
     */
    #[DataProvider('pagesOfTheManager')]
    public function testAManagerIsLetInWhereTheOtherRolesAreNot(string $page): void
    {
        $this->login('manager');
        $this->get($page);
        $this->assertResponseOk();

        // the widest of the other roles, which still may not
        $this->login('sales-manager');
        $this->get($page);
        $this->assertRedirect('/');
    }

    /**
     * A manager may have the customers carrying a label worked out again, but neither adds a label
     * nor changes one - the query behind it stays with the administrator.
     *
     * @return void
     */
    public function testAManagerRefreshesTheLabelsButDoesNotWriteThem(): void
    {
        $this->login('manager');

        $this->get('/labels');
        $this->assertResponseOk();
        $this->assertResponseContains('Update Related Customer Labels');
        $this->assertResponseNotContains('New Label');

        $this->get('/labels/add');
        $this->assertRedirect('/');

        $this->get('/labels/edit/' . $this->firstId('Labels'));
        $this->assertRedirect('/');

        $this->login('sales-manager');
        $this->get('/labels');
        $this->assertResponseOk();
        $this->assertResponseNotContains('Update Related Customer Labels');
    }
}
