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
     * The environment variable the table reads the region off.
     *
     * @var string
     */
    private const PHONE_REGION_VARIABLE = 'APP_DEFAULT_PHONE_REGION';

    /**
     * The region the tests that name one are read against.
     *
     * @var string
     */
    private const PHONE_REGION = 'CZ';

    /**
     * Test subject
     *
     * @var \App\Model\Table\PhonesTable
     */
    protected $Phones;

    /**
     * What the environment said about the region before setUp took it away.
     *
     * @var string|null
     */
    private ?string $phone_region_before = null;

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

        // A deployment sets the region in its environment, a development machine in config/.env and
        // CI in neither. Whichever of those the suite is running on, the tests start from no region
        // at all and the ones that mean to be read against a region say so themselves.
        $before = env(self::PHONE_REGION_VARIABLE);
        $this->phone_region_before = is_string($before) ? $before : null;

        $this->setPhoneRegion(null);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        $this->setPhoneRegion($this->phone_region_before);

        /** @phpstan-ignore unset.possiblyHookedProperty */
        unset($this->Phones);

        parent::tearDown();
    }

    /**
     * Put the region everywhere `env()` looks for it, or take it away again when there was nothing
     * to put back. Setting one of the three places would leave the other two saying something else.
     *
     * @param string|null $region The region to read local numbers against.
     * @return void
     */
    private function setPhoneRegion(?string $region): void
    {
        if ($region === null) {
            unset($_ENV[self::PHONE_REGION_VARIABLE], $_SERVER[self::PHONE_REGION_VARIABLE]);
            putenv(self::PHONE_REGION_VARIABLE);

            return;
        }

        $_ENV[self::PHONE_REGION_VARIABLE] = $region;
        $_SERVER[self::PHONE_REGION_VARIABLE] = $region;
        putenv(self::PHONE_REGION_VARIABLE . '=' . $region);
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
     * A number that carries its prefix says which country it belongs to by itself, so no region has
     * to be configured for it to be read. An installation that never sets one still gets its
     * numbers stored the same way.
     *
     * @return void
     * @link \App\Model\Table\PhonesTable::beforeMarshal()
     */
    public function testBeforeMarshalFormatsAnInternationalNumberWithoutARegion(): void
    {
        $phone = $this->Phones->newEntity(['phone' => '+420601234567']);

        $this->assertSame('+420 601 234 567', $phone->phone);
    }

    /**
     * The same number, on an installation that does configure a region. A region is what local
     * forms are read against and nothing more - it must not change what a number that already
     * carries its prefix is taken to mean.
     *
     * @return void
     * @link \App\Model\Table\PhonesTable::beforeMarshal()
     */
    public function testBeforeMarshalFormatsAnInternationalNumberAgainstTheRegion(): void
    {
        $this->setPhoneRegion(self::PHONE_REGION);

        $phone = $this->Phones->newEntity(['phone' => '+420601234567']);

        $this->assertSame('+420 601 234 567', $phone->phone);
    }

    /**
     * A number belonging to another country, on an installation configured for this one. The region
     * says where local forms come from, not which countries the customers may be reached in - a
     * foreign number has to survive being typed in.
     *
     * It is stored the way its own country writes it, which is where the dashes come from: the
     * international form is the number's, not the configured region's.
     *
     * @return void
     * @link \App\Model\Table\PhonesTable::beforeMarshal()
     */
    public function testBeforeMarshalFormatsANumberFromAnotherCountryAgainstTheRegion(): void
    {
        $this->setPhoneRegion(self::PHONE_REGION);

        $phone = $this->Phones->newEntity(['phone' => '+16502530000']);

        $this->assertSame('+1 650-253-0000', $phone->phone);
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
        $this->setPhoneRegion(self::PHONE_REGION);

        $phone = $this->Phones->newEntity(['phone' => '601 234 567']);

        $this->assertSame('+420 601 234 567', $phone->phone);
    }

    /**
     * The same local number where no region is configured. There is nothing to read it against -
     * the digits alone do not say which country they belong to - so it is left as it was typed
     * rather than guessed at, and what is wrong with it is left for the rules to say.
     *
     * @return void
     * @link \App\Model\Table\PhonesTable::beforeMarshal()
     */
    public function testBeforeMarshalLeavesALocalNumberAloneWithoutARegion(): void
    {
        $phone = $this->Phones->newEntity(['phone' => '601 234 567']);

        $this->assertSame('601 234 567', $phone->phone);
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
