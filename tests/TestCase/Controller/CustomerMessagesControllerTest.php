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
