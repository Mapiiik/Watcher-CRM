<?php
declare(strict_types=1);

namespace Bookkeeping\Test\TestCase\Model\Table;

use App\Test\Traits\TableTestTrait;
use Bookkeeping\Model\Table\InvoicesTable;
use Cake\TestSuite\TestCase;
use Override;

/**
 * Bookkeeping\Model\Table\InvoicesTable Test Case
 */
class InvoicesTableTest extends TestCase
{
    use TableTestTrait;

    /**
     * Test subject
     *
     * @var \Bookkeeping\Model\Table\InvoicesTable
     */
    protected $Invoices;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.AppUsers',
        'app.AccountingProfiles',
        'app.Customers',
        'plugin.Bookkeeping.Invoices',
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
        $config = $this->getTableLocator()->exists('Bookkeeping.Invoices') ? [] : ['className' => InvoicesTable::class];
        $this->Invoices = $this->getTableLocator()->get('Bookkeeping.Invoices', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        /** @phpstan-ignore unset.possiblyHookedProperty */
        unset($this->Invoices);

        parent::tearDown();
    }

    /**
     * A new record with nothing filled in is refused - see the trait for why that is the question
     * worth asking here.
     *
     * @return void
     * @link \Bookkeeping\Model\Table\InvoicesTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->assertEmptyRecordIsRefused($this->Invoices);
    }

    /**
     * The rules refuse a record whose references point nowhere - see the trait for why that is
     * the question worth asking here.
     *
     * @return void
     * @link \Bookkeeping\Model\Table\InvoicesTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->assertDanglingReferencesAreRefused($this->Invoices);
    }
}
