<?php
declare(strict_types=1);

namespace App\Test\TestCase\Phones;

use App\Phones\Formatter;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\Phones\Formatter Test Case
 */
#[CoversClass(Formatter::class)]
class FormatterTest extends TestCase
{
    /**
     * The region a deployment names, a development machine names in config/.env and CI names not at
     * all. The tests say for themselves which region they are to be read against.
     *
     * @var string
     */
    private const PHONE_REGION = 'CZ';

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        Configure::write('Phones.defaultRegion', self::PHONE_REGION);
    }

    /**
     * A number from the configured region is dialled without its prefix at home, so that is how the
     * summary carries it.
     *
     * @return void
     * @link \App\Phones\Formatter::toLocal()
     */
    public function testANumberFromTheRegionLosesItsPrefix(): void
    {
        $this->assertSame('601234567', Formatter::toLocal('+420 601 234 567'));
    }

    /**
     * A number written without the spaces the storage format puts in is read just the same. The
     * regular expression this replaced ran past the prefix and swallowed the whole number.
     *
     * @return void
     * @link \App\Phones\Formatter::toLocal()
     */
    public function testANumberWithoutSpacesIsNotSwallowed(): void
    {
        $this->assertSame('601234567', Formatter::toLocal('+420601234567'));
    }

    /**
     * A number from anywhere else keeps the prefix it cannot be dialled without.
     *
     * @return void
     * @link \App\Phones\Formatter::toLocal()
     */
    public function testANumberFromAnotherCountryKeepsItsPrefix(): void
    {
        $this->assertSame('+1 650-253-0000', Formatter::toLocal('+1 650-253-0000'));
    }

    /**
     * Sharing a country code is not being from the same region - a Slovak number is left alone by a
     * Czech deployment even though both prefixes start the same way.
     *
     * @return void
     * @link \App\Phones\Formatter::toLocal()
     */
    public function testANumberFromANeighbouringCountryKeepsItsPrefix(): void
    {
        $this->assertSame('+421 905 123 456', Formatter::toLocal('+421 905 123 456'));
    }

    /**
     * Every number in the list is read on its own, so a foreign one among local ones keeps its
     * prefix while the others lose theirs.
     *
     * @return void
     * @link \App\Phones\Formatter::toLocal()
     */
    public function testEveryNumberInAListIsReadOnItsOwn(): void
    {
        $this->assertSame(
            '601234567, +1 650-253-0000, 602345678',
            Formatter::toLocal('+420 601 234 567, +1 650-253-0000, +420 602 345 678'),
        );
    }

    /**
     * A value that is not a phone number at all is left as it stands rather than being mangled into
     * something that looks like one.
     *
     * @return void
     * @link \App\Phones\Formatter::toLocal()
     */
    public function testAValueThatIsNotANumberIsLeftAsItStands(): void
    {
        $this->assertSame('reception desk', Formatter::toLocal('reception desk'));
    }

    /**
     * With no region named there is no home country to dial from, so nothing is shortened.
     *
     * @return void
     * @link \App\Phones\Formatter::toLocal()
     */
    public function testWithoutARegionNothingIsShortened(): void
    {
        Configure::write('Phones.defaultRegion', null);

        $this->assertSame('+420 601 234 567', Formatter::toLocal('+420 601 234 567'));
    }

    /**
     * Numbers are stored in the international format, whichever way they were typed in.
     *
     * @return void
     * @link \App\Phones\Formatter::toInternational()
     */
    public function testANumberIsStoredInTheInternationalFormat(): void
    {
        $this->assertSame('+420 601 234 567', Formatter::toInternational('601234567'));
    }

    /**
     * A value that cannot be read as a number is reported as such, so that the caller can leave it
     * as it was entered.
     *
     * @return void
     * @link \App\Phones\Formatter::toInternational()
     */
    public function testAValueThatIsNotANumberHasNoInternationalFormat(): void
    {
        $this->assertNull(Formatter::toInternational('reception desk'));
    }

    /**
     * A number that parses but does not exist in the region is not a valid one either.
     *
     * @return void
     * @link \App\Phones\Formatter::isValid()
     */
    public function testANumberThatDoesNotExistIsNotValid(): void
    {
        $this->assertTrue(Formatter::isValid('+420 601 234 567'));
        $this->assertFalse(Formatter::isValid('+420 123'));
    }
}
