<?php
declare(strict_types=1);

namespace App\Test\TestCase\BulkMessages\Filter;

use App\BulkMessages\Filter\ContractScopedFilterInterface;
use App\BulkMessages\Filter\RegistryAddressFilter;
use App\Model\Table\CustomerMessagesTable;
use Cake\ORM\Query\SelectQuery;
use Cake\TestSuite\TestCase;
use Override;

/**
 * Guards the registry-address filter's contract-scoped preview narrowing: it
 * matches customers by a contract's installation address, so the preview must
 * hide their contracts at other addresses.
 */
class RegistryAddressFilterTest extends TestCase
{
    /**
     * @var \App\Model\Table\CustomerMessagesTable
     */
    protected CustomerMessagesTable $CustomerMessages;

    /**
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('CustomerMessages')
            ? []
            : ['className' => CustomerMessagesTable::class];
        $this->CustomerMessages = $this->getTableLocator()->get('CustomerMessages', $config);
    }

    /**
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        $this->getTableLocator()->clear();

        parent::tearDown();
    }

    /**
     * @return void
     */
    public function testIsContractScoped(): void
    {
        $this->assertInstanceOf(
            ContractScopedFilterInterface::class,
            new RegistryAddressFilter($this->CustomerMessages),
        );
    }

    /**
     * An empty / non-string value never narrows customers or contracts.
     *
     * @return void
     */
    public function testInactiveValue(): void
    {
        $filter = new RegistryAddressFilter($this->CustomerMessages);

        $this->assertNull($filter->conditions(''));
        $this->assertNull($filter->conditions(null));
        $this->assertNull($filter->containedContractConditions(''));
        $this->assertNull($filter->containedContractConditions(null));
    }

    /**
     * A selected registry address narrows both the matched customers and the
     * contained contracts, each via its own subquery projection.
     *
     * @return void
     */
    public function testActiveValueNarrowsCustomersAndContracts(): void
    {
        $filter = new RegistryAddressFilter($this->CustomerMessages);

        $conditions = $filter->conditions('cz|12345678');
        $this->assertIsArray($conditions);
        $this->assertArrayHasKey('Customers.id IN', $conditions);
        $this->assertInstanceOf(SelectQuery::class, $conditions['Customers.id IN']);

        $contained = $filter->containedContractConditions('cz|12345678');
        $this->assertIsArray($contained);
        $this->assertArrayHasKey('Contracts.id IN', $contained);
        $this->assertInstanceOf(SelectQuery::class, $contained['Contracts.id IN']);
    }
}
