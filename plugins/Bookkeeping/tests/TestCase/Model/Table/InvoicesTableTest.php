<?php
declare(strict_types=1);

namespace Bookkeeping\Test\TestCase\Model\Table;

use App\Test\Traits\TableTestTrait;
use Bookkeeping\Model\Table\InvoicesTable;
use Cake\I18n\Date;
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
        'app.Users',
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

    /**
     * A number is taken once a year, so the same one is refused in that year and free in another.
     *
     * @return void
     * @link \Bookkeeping\Model\Table\InvoicesTable::buildRules()
     */
    public function testANumberIsTakenOnceAYear(): void
    {
        $taken = $this->Invoices->newEntity($this->invoiceOf('2021-03-01'));

        $this->assertFalse($this->Invoices->save($taken));
        $this->assertArrayHasKey('numberOnceAYear', $taken->getError('number'));

        $this->Invoices->saveOrFail($this->Invoices->newEntity($this->invoiceOf('2022-03-01')));
    }

    /**
     * An invoice does not stand in its own way, but it cannot be moved into a year that holds its
     * number on another invoice.
     *
     * @return void
     * @link \Bookkeeping\Model\Table\InvoicesTable::buildRules()
     */
    public function testAnInvoiceIsNotMovedOntoATakenNumber(): void
    {
        $invoice = $this->Invoices->find()->where(['number' => '123/TEST/2026'])->firstOrFail();
        $invoice->set('creation_date', new Date('2021-06-01'));
        $this->Invoices->saveOrFail($invoice);

        $other = $this->Invoices->newEntity($this->invoiceOf('2022-03-01'));
        $this->Invoices->saveOrFail($other);

        $other->creation_date = new Date('2021-03-01');
        $this->assertFalse($this->Invoices->save($other));
        $this->assertArrayHasKey('numberOnceAYear', $other->getError('number'));
    }

    /**
     * Saving what has nothing to do with the number does not ask about it. Sending an invoice is
     * such a save.
     *
     * @return void
     * @link \Bookkeeping\Model\Table\InvoicesTable::buildRules()
     */
    public function testASaveOfSomethingElseDoesNotAskAboutTheNumber(): void
    {
        $invoice = $this->Invoices->find()->select(['id', 'send_by_email'])->firstOrFail();
        $invoice->set('send_by_email', false);

        $this->Invoices->saveOrFail($invoice);

        $this->assertFalse($this->Invoices->find()->firstOrFail()->get('send_by_email'));
    }

    /**
     * An invoice under the number of the fixture.
     *
     * @param string $date The day it was created.
     * @return array<string, mixed>
     */
    private function invoiceOf(string $date): array
    {
        return [
            'customer_id' => '403bab0e-52cd-4a8e-83f8-43c2457d0481',
            'number' => '123/TEST/2026',
            'creation_date' => $date,
            'due_date' => $date,
            'total' => 100,
            'debt' => 100,
        ];
    }
}
