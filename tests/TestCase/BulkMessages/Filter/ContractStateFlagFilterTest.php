<?php
declare(strict_types=1);

namespace App\Test\TestCase\BulkMessages\Filter;

use App\BulkMessages\Filter\AbstractContractStateFlagFilter;
use App\BulkMessages\Filter\ActiveServicesContractFilter;
use App\BulkMessages\Filter\BilledContractFilter;
use App\BulkMessages\Filter\ContractScopedFilterInterface;
use App\Model\Table\CustomerMessagesTable;
use Cake\TestSuite\TestCase;
use Override;

/**
 * Covers the contract-state flag filters: the checked-by-default behaviour,
 * the explicit on/off value storage, and the customer / contained-contract
 * conditions each flavour produces.
 */
class ContractStateFlagFilterTest extends TestCase
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
     * The checkbox is checked on a fresh (unset) state and otherwise mirrors the
     * stored boolean, so an unchecked box does not revert to the default.
     *
     * @return void
     */
    public function testControlsCheckedByDefault(): void
    {
        $filter = new ActiveServicesContractFilter($this->CustomerMessages);

        $default = $filter->controls(null)[0]['options'];
        $this->assertSame('checkbox', $default['type']);
        $this->assertTrue($default['checked'], 'fresh state must render checked');

        $this->assertTrue($filter->controls(true)[0]['options']['checked']);
        $this->assertFalse($filter->controls(false)[0]['options']['checked']);
    }

    /**
     * buildValue always yields an explicit boolean (never null), so both on and
     * off are persisted in the wizard state.
     *
     * @return void
     */
    public function testBuildValueStoresExplicitBoolean(): void
    {
        $filter = new ActiveServicesContractFilter($this->CustomerMessages);

        $this->assertTrue($filter->buildValue(['active_services_contract' => '1']));
        $this->assertFalse($filter->buildValue(['active_services_contract' => '0']));
        // an absent key (checkbox stripped) is treated as off, not default-on
        $this->assertFalse($filter->buildValue([]));
    }

    /**
     * Conditions only apply when the box is on; off / unset never narrow.
     *
     * @return void
     */
    public function testConditionsOnlyWhenOn(): void
    {
        $filter = new ActiveServicesContractFilter($this->CustomerMessages);

        $this->assertNull($filter->containedContractConditions(false));
        $this->assertNull($filter->containedContractConditions(null));

        $contained = $filter->containedContractConditions(true);
        $this->assertIsArray($contained);
        $this->assertArrayHasKey('Contracts.contract_state_id IN', $contained);
    }

    /**
     * Both flavours are contract-scoped and carry the expected wizard keys.
     *
     * @return void
     */
    public function testFlavourIdentity(): void
    {
        $active = new ActiveServicesContractFilter($this->CustomerMessages);
        $billed = new BilledContractFilter($this->CustomerMessages);

        $this->assertInstanceOf(AbstractContractStateFlagFilter::class, $active);
        $this->assertInstanceOf(ContractScopedFilterInterface::class, $active);
        $this->assertInstanceOf(ContractScopedFilterInterface::class, $billed);

        $this->assertSame('active_services_contract', $active->id());
        $this->assertSame('billed_contract', $billed->id());
    }
}
