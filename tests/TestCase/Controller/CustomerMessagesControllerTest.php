<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\BulkMessages\BulkRecipientFilterRegistry;
use App\Controller\CustomerMessagesController;
use App\Model\Enum\CustomerMessagePurpose;
use Cake\Http\ServerRequest;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionMethod;

/**
 * App\Controller\CustomerMessagesController Test Case
 */
#[UsesClass(CustomerMessagesController::class)]
class CustomerMessagesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Customer with a single contract in the seeded contract state
     * (active_services = billed = true).
     *
     * @var string
     */
    private const CUSTOMER_WITH_CONTRACT = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    /**
     * Customer that consents to mailing but has no contract at all.
     *
     * @var string
     */
    private const CUSTOMER_WITHOUT_CONTRACT = 'ae128a49-82fd-4b80-921f-f11af75fd113';

    /**
     * Service type of the seeded contract.
     *
     * @var string
     */
    private const SERVICE_TYPE = '907cbc5c-af88-43b6-b535-959b4fa2ce3d';

    /**
     * Service the seeded contract only billed historically (billing_until in 2021).
     *
     * @var string
     */
    private const SERVICE_BILLED_HISTORICALLY = 'eaacfeb3-1430-43ce-842e-497c5c95d953';

    /**
     * Service the seeded contract still bills (billing_until IS NULL).
     *
     * @var string
     */
    private const SERVICE_BILLED_OPEN_ENDED = '5f6a2f47-0a4d-4c05-9bcb-2f0dc0a3f0d2';

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
        'app.Queues',
        'app.Services',
        'app.Billings',
        'app.Emails',
        'app.Phones',
        'app.CustomerMessages',
    ];

    /**
     * Test index method
     *
     * @return void
     * @link \App\Controller\CustomerMessagesController::index()
     */
    public function testIndex(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\CustomerMessagesController::view()
     */
    public function testView(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test add method
     *
     * @return void
     * @link \App\Controller\CustomerMessagesController::add()
     */
    public function testAdd(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test edit method
     *
     * @return void
     * @link \App\Controller\CustomerMessagesController::edit()
     */
    public function testEdit(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test delete method
     *
     * @return void
     * @link \App\Controller\CustomerMessagesController::delete()
     */
    public function testDelete(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * With an active contract-scoped filter, a customer with no qualifying
     * contract (here: no contract at all) is not offered as a recipient.
     *
     * @return void
     * @link \App\Controller\CustomerMessagesController::findBulkCustomers()
     */
    public function testContractScopedFilterExcludesCustomersWithoutQualifyingContract(): void
    {
        $ids = $this->resolveBulkCustomers(
            CustomerMessagePurpose::Outages,
            ['active_services_contract' => true],
        );

        $this->assertContains(self::CUSTOMER_WITH_CONTRACT, $ids);
        $this->assertNotContains(self::CUSTOMER_WITHOUT_CONTRACT, $ids);
    }

    /**
     * The billing flavour behaves the same way against the `billed` state flag.
     *
     * @return void
     * @link \App\Controller\CustomerMessagesController::findBulkCustomers()
     */
    public function testBilledFilterExcludesCustomersWithoutBilledContract(): void
    {
        $ids = $this->resolveBulkCustomers(
            CustomerMessagePurpose::Billing,
            ['billed_contract' => true],
        );

        $this->assertContains(self::CUSTOMER_WITH_CONTRACT, $ids);
        $this->assertNotContains(self::CUSTOMER_WITHOUT_CONTRACT, $ids);
    }

    /**
     * Without any contract-scoped filter active, recipient eligibility does not
     * depend on contracts, so a consenting customer with no contract is offered.
     *
     * @return void
     * @link \App\Controller\CustomerMessagesController::findBulkCustomers()
     */
    public function testNoContractFilterIncludesCustomersWithoutContracts(): void
    {
        $ids = $this->resolveBulkCustomers(CustomerMessagePurpose::Outages, []);

        $this->assertContains(self::CUSTOMER_WITH_CONTRACT, $ids);
        $this->assertContains(self::CUSTOMER_WITHOUT_CONTRACT, $ids);
    }

    /**
     * Turning the contract-scoped filter off (unchecked, stored as false) must
     * behave like no filter — contractless customers stay eligible.
     *
     * @return void
     * @link \App\Controller\CustomerMessagesController::findBulkCustomers()
     */
    public function testContractScopedFilterOffIncludesCustomersWithoutContracts(): void
    {
        $ids = $this->resolveBulkCustomers(
            CustomerMessagePurpose::Outages,
            ['active_services_contract' => false],
        );

        $this->assertContains(self::CUSTOMER_WITH_CONTRACT, $ids);
        $this->assertContains(self::CUSTOMER_WITHOUT_CONTRACT, $ids);
    }

    /**
     * The service type filter matches a customer through the service type of one
     * of their contracts, and offers nobody for a type nothing is contracted for.
     *
     * @return void
     * @link \App\BulkMessages\Filter\ServiceTypesFilter::containedContractConditions()
     */
    public function testServiceTypesFilterMatchesContractServiceType(): void
    {
        $matched = $this->resolveBulkCustomers(
            CustomerMessagePurpose::Outages,
            ['service_type_ids' => [self::SERVICE_TYPE]],
        );

        $this->assertContains(self::CUSTOMER_WITH_CONTRACT, $matched);
        // being contract-scoped, it can never match a customer without contracts
        $this->assertNotContains(self::CUSTOMER_WITHOUT_CONTRACT, $matched);

        $unmatched = $this->resolveBulkCustomers(
            CustomerMessagePurpose::Outages,
            ['service_type_ids' => ['8a2a4a6c-0e3f-4b1d-9d59-7e0a1c2b3d4e']],
        );

        $this->assertNotContains(self::CUSTOMER_WITH_CONTRACT, $unmatched);
    }

    /**
     * The services filter looks at what the contract actually bills, and counts
     * only active or future billings — a service billed until 2021 is history and
     * must not make its customer a recipient.
     *
     * @return void
     * @link \App\BulkMessages\Filter\ServicesFilter::containedContractConditions()
     */
    public function testServicesFilterMatchesOnlyActiveOrFutureBillings(): void
    {
        $matched = $this->resolveBulkCustomers(
            CustomerMessagePurpose::Outages,
            ['service_ids' => [self::SERVICE_BILLED_OPEN_ENDED]],
        );

        $this->assertContains(self::CUSTOMER_WITH_CONTRACT, $matched);
        $this->assertNotContains(self::CUSTOMER_WITHOUT_CONTRACT, $matched);

        $historical = $this->resolveBulkCustomers(
            CustomerMessagePurpose::Outages,
            ['service_ids' => [self::SERVICE_BILLED_HISTORICALLY]],
        );

        $this->assertNotContains(self::CUSTOMER_WITH_CONTRACT, $historical);
    }

    /**
     * Both service filters are inactive unless something valid was selected, so a
     * junk or empty submission must not narrow the recipients (nor reach the DB
     * with a malformed uuid).
     *
     * @return void
     * @link \App\BulkMessages\Filter\ServicesFilter::buildValue()
     */
    public function testServiceFiltersIgnoreInvalidSelections(): void
    {
        $controller = new CustomerMessagesController(new ServerRequest());
        $registry = new BulkRecipientFilterRegistry($controller->CustomerMessages);

        foreach (['service_ids', 'service_type_ids'] as $key) {
            $filter = $registry->get($key);
            $this->assertNotNull($filter, $key . ' must be registered');
            $this->assertNull($filter->buildValue([]), $key . ' must be inactive when unsubmitted');
            $this->assertNull(
                $filter->buildValue([$key => ['', 'not-a-uuid', 42]]),
                $key . ' must drop values that are not uuids',
            );
        }
    }

    /**
     * Both filters must offer the seeded records as selectable options, the
     * services one grouped by service type (optgroups in the multiselect).
     *
     * @return void
     * @link \App\BulkMessages\Filter\ServicesFilter::controls()
     */
    public function testServiceFilterControlsOfferSeededOptions(): void
    {
        $controller = new CustomerMessagesController(new ServerRequest());
        $registry = new BulkRecipientFilterRegistry($controller->CustomerMessages);

        $serviceTypeControls = $registry->get('service_type_ids')?->controls(null) ?? [];
        $this->assertCount(1, $serviceTypeControls);
        $this->assertArrayHasKey(self::SERVICE_TYPE, $serviceTypeControls[0]['options']['options']);

        $serviceControls = $registry->get('service_ids')?->controls(null) ?? [];
        $this->assertCount(1, $serviceControls);

        // one optgroup per service type, holding both seeded services
        $grouped = $serviceControls[0]['options']['options'];
        $this->assertCount(1, $grouped);
        $services = reset($grouped);
        $this->assertArrayHasKey(self::SERVICE_BILLED_HISTORICALLY, $services);
        $this->assertArrayHasKey(self::SERVICE_BILLED_OPEN_ENDED, $services);
    }

    /**
     * Resolve the eligible recipient customer ids for a purpose/filter set by
     * invoking the controller's private recipient resolver against the fixtures.
     *
     * @param \App\Model\Enum\CustomerMessagePurpose $purpose Selected purpose.
     * @param array<string, mixed> $filters Stored filter values keyed by filter id.
     * @return array<string> Matched customer ids.
     */
    private function resolveBulkCustomers(CustomerMessagePurpose $purpose, array $filters): array
    {
        $controller = new CustomerMessagesController(new ServerRequest());
        $registry = new BulkRecipientFilterRegistry($controller->CustomerMessages);
        $method = new ReflectionMethod(CustomerMessagesController::class, 'findBulkCustomers');

        /** @var array<\App\Model\Entity\Customer> $customers */
        $customers = $method->invoke($controller, $purpose, $filters, $registry, false, true);

        return array_map(static fn($customer): string => (string)$customer->id, $customers);
    }
}
