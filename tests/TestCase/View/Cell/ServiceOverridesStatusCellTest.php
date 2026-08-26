<?php
declare(strict_types=1);

namespace App\Test\TestCase\View\Cell;

use App\Test\Traits\ControllerTestTrait;
use App\View\Cell\ServiceOverridesStatusCell;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\View\Cell\ServiceOverridesStatusCell Test Case
 *
 * The cell tops the customer detail with the service overrides their contracts are under. It is
 * exercised through that page, which is where it gets both a view to render in and the contract
 * ids it works from.
 */
#[UsesClass(ServiceOverridesStatusCell::class)]
class ServiceOverridesStatusCellTest extends TestCase
{
    use ControllerTestTrait;
    use IntegrationTestTrait;

    /**
     * Customer owning the contract from the Contracts fixture.
     *
     * @var string
     */
    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    /**
     * Contract from the Contracts fixture.
     *
     * @var string
     */
    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

    /**
     * Service from the Services fixture.
     *
     * @var string
     */
    private const SERVICE_ID = 'eaacfeb3-1430-43ce-842e-497c5c95d953';

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
        'app.Queues',
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
        'app.ServiceOverrides',
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

        $this->login();
    }

    /**
     * Add an override to the fixture contract over the given period.
     *
     * @param string $validFrom Start of the period.
     * @param string $validUntil End of the period.
     * @param bool $revoked Whether it has since been called off.
     * @return void
     */
    private function addOverride(string $validFrom, string $validUntil, bool $revoked = false): void
    {
        $serviceOverrides = $this->getTableLocator()->get('ServiceOverrides');
        $override = $serviceOverrides->newEntity([
            'contract_id' => self::CONTRACT_ID,
            'service_id' => self::SERVICE_ID,
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
            'reason' => 'Lorem ipsum',
        ]);
        if ($revoked) {
            $override->revoked = DateTime::now();
        }

        $serviceOverrides->saveOrFail($override);
    }

    /**
     * An override covering today is reported as running.
     *
     * @return void
     * @link \App\View\Cell\ServiceOverridesStatusCell::display()
     */
    public function testDisplayReportsARunningOverride(): void
    {
        // the validation only allows a period starting today or within the next few days
        $this->addOverride(
            Date::today()->toDateString(),
            Date::today()->addDays(2)->toDateString(),
        );

        $this->get('/customers/view/' . self::CUSTOMER_ID);

        $this->assertResponseOk();
        $this->assertResponseContains(__('Active Service Overrides'));
    }

    /**
     * One that has not started yet is reported separately - it says something different about the
     * contract than one already in force.
     *
     * @return void
     * @link \App\View\Cell\ServiceOverridesStatusCell::display()
     */
    public function testDisplayKeepsAFutureOverrideApart(): void
    {
        $this->addOverride(
            Date::today()->addDays(3)->toDateString(),
            Date::today()->addDays(5)->toDateString(),
        );

        $this->get('/customers/view/' . self::CUSTOMER_ID);

        $this->assertResponseOk();
        $this->assertResponseContains(__('Future Service Overrides'));
        $this->assertResponseNotContains(__('Active Service Overrides'));
    }

    /**
     * An override that was called off is not reported at all, whatever period it names.
     *
     * @return void
     * @link \App\View\Cell\ServiceOverridesStatusCell::display()
     */
    public function testDisplayLeavesOutARevokedOverride(): void
    {
        $this->addOverride(
            Date::today()->toDateString(),
            Date::today()->addDays(2)->toDateString(),
            revoked: true,
        );

        $this->get('/customers/view/' . self::CUSTOMER_ID);

        $this->assertResponseOk();
        $this->assertResponseNotContains(__('Active Service Overrides'));
        $this->assertResponseNotContains(__('Future Service Overrides'));
    }

    /**
     * With nothing to report the cell renders nothing, rather than an empty heading.
     *
     * @return void
     * @link \App\View\Cell\ServiceOverridesStatusCell::display()
     */
    public function testDisplayWithoutAnyOverrides(): void
    {
        $this->get('/customers/view/' . self::CUSTOMER_ID);

        $this->assertResponseOk();
        $this->assertResponseNotContains('service-overrides-status');
    }
}
