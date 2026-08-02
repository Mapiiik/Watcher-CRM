<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\PhonesTable;
use App\Test\Traits\TableTestTrait;
use Cake\TestSuite\TestCase;
use Override;

/**
 * App\Model\Table\PhonesTable Test Case
 */
class PhonesTableTest extends TestCase
{
    use TableTestTrait;

    /**
     * Test subject
     *
     * @var \App\Model\Table\PhonesTable
     */
    protected $Phones;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.AppUsers',
        'app.AccountingProfiles',
        'app.Customers',
        'app.Phones',
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
        $config = $this->getTableLocator()->exists('Phones') ? [] : ['className' => PhonesTable::class];
        $this->Phones = $this->getTableLocator()->get('Phones', $config);
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
        unset($this->Phones);

        parent::tearDown();
    }

    /**
     * A new record with nothing filled in is refused - see the trait for why that is the question
     * worth asking here.
     *
     * @return void
     * @link \App\Model\Table\PhonesTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->assertEmptyRecordIsRefused($this->Phones);
    }

    /**
     * The rules refuse a record whose references point nowhere - see the trait for why that is
     * the question worth asking here.
     *
     * @return void
     * @link \App\Model\Table\PhonesTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->assertDanglingReferencesAreRefused($this->Phones);
    }

    /**
     * A number is stored the way the international format writes it, whichever way it was typed -
     * the same number written two ways would otherwise not be recognised as the same one.
     *
     * The region the local forms are read against comes from the environment, so the test says
     * which one it means rather than relying on what is configured.
     *
     * @return void
     * @link \App\Model\Table\PhonesTable::beforeMarshal()
     */
    public function testBeforeMarshalFormatsANumber(): void
    {
        $phone = $this->Phones->newEntity(['phone' => '+420601234567']);

        $this->assertSame('+420 601 234 567', $phone->phone);
    }

    /**
     * A number typed the local way is read against the configured region and stored in the same
     * international form, so it matches the one typed with the prefix.
     *
     * @return void
     * @link \App\Model\Table\PhonesTable::beforeMarshal()
     */
    public function testBeforeMarshalReadsALocalNumberAgainstTheRegion(): void
    {
        $phone = $this->Phones->newEntity(['phone' => '601 234 567']);

        $this->assertSame('+420 601 234 567', $phone->phone);
    }

    /**
     * Something that cannot be read as a number is left as it was typed. Silently keeping whatever
     * could be made of it would hide from the operator what they got wrong.
     *
     * @return void
     * @link \App\Model\Table\PhonesTable::beforeMarshal()
     */
    public function testBeforeMarshalLeavesAnUnparseableNumberAlone(): void
    {
        $phone = $this->Phones->newEntity(['phone' => 'not a phone number']);

        $this->assertSame('not a phone number', $phone->phone);
    }

    /**
     * An empty field is nothing to format.
     *
     * @return void
     * @link \App\Model\Table\PhonesTable::beforeMarshal()
     */
    public function testBeforeMarshalLeavesAnEmptyFieldAlone(): void
    {
        $phone = $this->Phones->newEntity(['phone' => '']);

        $this->assertNull($phone->phone);
    }
}
