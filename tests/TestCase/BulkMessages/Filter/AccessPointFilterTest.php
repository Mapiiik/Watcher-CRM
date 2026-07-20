<?php
declare(strict_types=1);

namespace App\Test\TestCase\BulkMessages\Filter;

use App\BulkMessages\Filter\AccessPointFilter;
use App\BulkMessages\Filter\ContractScopedFilterInterface;
use App\Model\Table\CustomerMessagesTable;
use Cake\TestSuite\TestCase;
use Override;

/**
 * Guards the access point filter's contract-scoped preview narrowing: it must
 * restrict the contained contracts to its matched access points (the same set
 * its customer conditions use), so the preview grouping only sees those.
 */
class AccessPointFilterTest extends TestCase
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
            new AccessPointFilter($this->CustomerMessages),
        );
    }

    /**
     * An inactive selection must not narrow the contained contracts.
     *
     * @return void
     */
    public function testContainedContractConditionsInactive(): void
    {
        $filter = new AccessPointFilter($this->CustomerMessages);

        $this->assertNull($filter->containedContractConditions(null));
        $this->assertNull($filter->containedContractConditions(['ids' => [], 'cascade' => false]));
    }

    /**
     * A plain (non-cascade) selection narrows the contained contracts to exactly
     * the selected access points.
     *
     * @return void
     */
    public function testContainedContractConditionsNarrowsToSelection(): void
    {
        $filter = new AccessPointFilter($this->CustomerMessages);
        $id = '11111111-1111-4111-8111-111111111111';

        $conditions = $filter->containedContractConditions(['ids' => [$id], 'cascade' => false]);

        $this->assertSame(['Contracts.access_point_id IN' => [$id]], $conditions);
    }
}
