<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\BulkMessages\BulkRecipientFilterRegistry;
use App\Controller\CustomerMessagesController;
use App\Model\Enum\CustomerMessagePurpose;
use App\Model\Enum\ServiceCriticalityLevel;
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
     * A preview row carries the two flags the operator must notice: the VIP flag
     * of its contract, and the highest criticality level among the services that
     * contract *currently* bills — a historically billed service says nothing
     * about the customer today, however critical it was.
     *
     * @return void
     * @link \App\Controller\CustomerMessagesController::buildRecipientRow()
     */
    public function testPreviewRowsCarryContractAndServiceFlags(): void
    {
        $rows = $this->resolveBulkPreviewRows(CustomerMessagePurpose::Outages, []);

        $withContract = $rows[self::CUSTOMER_WITH_CONTRACT] ?? null;
        $this->assertNotNull($withContract);
        $this->assertTrue($withContract['vip'], 'the seeded contract is flagged VIP');
        // Important comes from the open-ended billing; the historical billing's
        // Critical service must not win (nor show up at all)
        $this->assertSame(ServiceCriticalityLevel::Important, $withContract['criticality']);
        // and for the same reason the row lists only the service still billed
        $this->assertSame(['Sed do eiusmod tempor'], $withContract['services']);

        $withoutContract = $rows[self::CUSTOMER_WITHOUT_CONTRACT] ?? null;
        $this->assertNotNull($withoutContract);
        $this->assertFalse($withoutContract['vip']);
        $this->assertNull($withoutContract['criticality']);
    }

    /**
     * The warning counts people, not rows — and only those whose flags are set.
     *
     * @return void
     * @link \App\Controller\CustomerMessagesController::countFlaggedCustomers()
     */
    public function testFlaggedRecipientsAreCountedPerCustomer(): void
    {
        $controller = new CustomerMessagesController(new ServerRequest());
        $customers = $this->findBulkCustomers($controller, CustomerMessagePurpose::Outages, []);
        $method = new ReflectionMethod(CustomerMessagesController::class, 'countFlaggedCustomers');

        $this->assertSame(
            ['vip' => 1, 'critical' => 1],
            $method->invoke($controller, $customers),
        );
    }

    /**
     * A filter that restricts by default must say so through defaultValue(), so
     * picking a purpose seeds the restriction into the wizard state. Without it,
     * a wizard that never submits the filter step would send unrestricted.
     *
     * @return void
     * @link \App\BulkMessages\BulkRecipientFilterRegistry::defaultsForPurpose()
     */
    public function testPurposeDefaultsSeedTheRestrictingFilters(): void
    {
        $controller = new CustomerMessagesController(new ServerRequest());
        $registry = new BulkRecipientFilterRegistry($controller->CustomerMessages);

        $this->assertSame(
            ['active_services_contract' => true],
            $registry->defaultsForPurpose(CustomerMessagePurpose::Outages),
        );
        $this->assertSame(
            ['billed_contract' => true],
            $registry->defaultsForPurpose(CustomerMessagePurpose::Billing),
        );

        // and the seeded defaults must resolve the same way an explicit
        // submission of the same values does
        $this->assertSame(
            $this->resolveBulkCustomers(
                CustomerMessagePurpose::Outages,
                $registry->defaultsForPurpose(CustomerMessagePurpose::Outages),
            ),
            $this->resolveBulkCustomers(
                CustomerMessagePurpose::Outages,
                ['active_services_contract' => true],
            ),
        );
    }

    /**
     * The post-send report has to explain how the recipients were picked, so
     * every active filter contributes a readable line, in the order the purpose
     * offers them. Inactive filters stay silent.
     *
     * @return void
     * @link \App\BulkMessages\BulkRecipientFilterRegistry::describeFilters()
     */
    public function testActiveFiltersDescribeThemselvesForTheReport(): void
    {
        $controller = new CustomerMessagesController(new ServerRequest());
        $registry = new BulkRecipientFilterRegistry($controller->CustomerMessages);

        // the locale is not fixed for tests, so the expectation goes through the
        // same translation the filters do
        $this->assertSame(
            [
                __('Service types') . ': Lorem ipsum dolor sit amet',
                __('Only customers with an active contract (provides active services)'),
            ],
            $registry->describeFilters(CustomerMessagePurpose::Outages, [
                'service_type_ids' => [self::SERVICE_TYPE],
                'active_services_contract' => true,
            ]),
        );

        // an unchecked flag narrowed nothing, so it must not claim to have
        $this->assertSame(
            [],
            $registry->describeFilters(CustomerMessagePurpose::Outages, [
                'active_services_contract' => false,
                'service_type_ids' => [],
            ]),
        );
    }

    /**
     * Recipients the operator approved but that no longer resolve at send time
     * must be reported by name, not silently left out.
     *
     * @return void
     * @link \App\Controller\CustomerMessagesController::findDroppedRecipients()
     */
    public function testRecipientsLostBetweenPreviewAndSendAreReported(): void
    {
        $controller = new CustomerMessagesController(new ServerRequest());
        // only the contracted customer still resolves; the other one was
        // approved in the preview and has since fallen out
        $customers = $this->findBulkCustomers(
            $controller,
            CustomerMessagePurpose::Outages,
            ['active_services_contract' => true],
        );
        $method = new ReflectionMethod(CustomerMessagesController::class, 'findDroppedRecipients');

        /** @var list<array{number: string|null, name: string}> $dropped */
        $dropped = $method->invoke(
            $controller,
            [self::CUSTOMER_WITH_CONTRACT, self::CUSTOMER_WITHOUT_CONTRACT],
            $customers,
        );

        $this->assertCount(1, $dropped);
        $this->assertNotSame('', $dropped[0]['name']);

        // nothing was lost when every selected customer still resolves
        $this->assertSame(
            [],
            $method->invoke($controller, [self::CUSTOMER_WITH_CONTRACT], $customers),
        );
    }

    /**
     * The summary e-mail body must carry everything the send is judged by: the
     * filters, every recipient with the addresses it went to, and the text.
     *
     * @return void
     * @link \App\Controller\CustomerMessagesController::renderBulkSendReport()
     */
    public function testSummaryEmailBodyCarriesTheWholeSend(): void
    {
        $controller = new CustomerMessagesController(new ServerRequest());
        $method = new ReflectionMethod(CustomerMessagesController::class, 'renderBulkSendReport');

        $body = $method->invoke($controller, [
            'sent' => 1,
            'channel' => 'E-mail (support)',
            'is_sms' => false,
            'purpose' => 'Outage notification',
            'subject' => 'Planned outage',
            'body' => 'We are replacing a radio unit.',
            'filters' => ['Access Points: Hilltop (including sub access points)'],
            'ignored_customer_consent' => false,
            'ignored_contact_use' => true,
            'groups' => [
                [
                    'ap_name' => 'Hilltop',
                    'customers' => [
                        [
                            'number' => 'C-1',
                            'name' => 'Acme s.r.o.',
                            'contract_number' => 'S-42',
                            'services' => ['Internet 100/100'],
                            'vip' => true,
                            'criticality' => 'Critical',
                            'recipients' => ['it@acme.example'],
                        ],
                    ],
                ],
            ],
            'skipped' => [['id' => 'x', 'number' => 'C-2', 'name' => 'No Contact Ltd.']],
            'dropped' => [['number' => 'C-3', 'name' => 'Gone Away Ltd.']],
            'flagged' => ['vip' => 1, 'critical' => 1],
        ]);

        $this->assertIsString($body);
        foreach (
            [
                'Outage notification',
                'Access Points: Hilltop (including sub access points)',
                // rendered through __(), unlike the values above
                __('Per-contact routing flag was ignored.'),
                __('VIP'),
                'Hilltop',
                'C-1 Acme s.r.o.',
                'S-42',
                'Internet 100/100',
                'it@acme.example',
                'Critical',
                'No Contact Ltd.',
                'Gone Away Ltd.',
                'Planned outage',
                'We are replacing a radio unit.',
            ] as $expected
        ) {
            $this->assertStringContainsString($expected, $body);
        }
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
        $customers = $this->findBulkCustomers(
            new CustomerMessagesController(new ServerRequest()),
            $purpose,
            $filters,
        );

        return array_map(static fn($customer): string => (string)$customer->id, $customers);
    }

    /**
     * Resolve the preview rows for a purpose/filter set, flattened and keyed by
     * customer id (the fixtures give every customer at most one row).
     *
     * @param \App\Model\Enum\CustomerMessagePurpose $purpose Selected purpose.
     * @param array<string, mixed> $filters Stored filter values keyed by filter id.
     * @return array<string, array<string, mixed>> Rows keyed by customer id.
     */
    private function resolveBulkPreviewRows(CustomerMessagePurpose $purpose, array $filters): array
    {
        $controller = new CustomerMessagesController(new ServerRequest());
        $customers = $this->findBulkCustomers($controller, $purpose, $filters);
        $method = new ReflectionMethod(CustomerMessagesController::class, 'groupCustomersByAccessPoint');

        /** @var list<array{rows: list<array<string, mixed>>}> $groups */
        $groups = $method->invoke($controller, $customers, []);

        $rows = [];
        foreach ($groups as $group) {
            foreach ($group['rows'] as $row) {
                $rows[(string)$row['customer']->id] = $row;
            }
        }

        return $rows;
    }

    /**
     * Invoke the controller's private recipient resolver against the fixtures.
     *
     * @param \App\Controller\CustomerMessagesController $controller Controller to invoke on.
     * @param \App\Model\Enum\CustomerMessagePurpose $purpose Selected purpose.
     * @param array<string, mixed> $filters Stored filter values keyed by filter id.
     * @return array<\App\Model\Entity\Customer> Matched customers.
     */
    private function findBulkCustomers(
        CustomerMessagesController $controller,
        CustomerMessagePurpose $purpose,
        array $filters,
    ): array {
        $registry = new BulkRecipientFilterRegistry($controller->CustomerMessages);
        $method = new ReflectionMethod(CustomerMessagesController::class, 'findBulkCustomers');

        /** @var array<\App\Model\Entity\Customer> $customers */
        $customers = $method->invoke($controller, $purpose, $filters, $registry, false, true);

        return $customers;
    }
}
