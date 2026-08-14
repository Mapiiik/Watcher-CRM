<?php
declare(strict_types=1);

namespace App\Test\TestCase\BusinessRegister;

use App\BusinessRegister\IdentityNumber;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\BusinessRegister\IdentityNumber Test Case
 */
#[CoversClass(IdentityNumber::class)]
class IdentityNumberTest extends TestCase
{
    /**
     * A real Czech identification number, the one the CRM carries for its own company.
     *
     * @var string
     */
    private const CZECH = '27496139';

    /**
     * A Croatian identification number whose check digit holds up.
     *
     * @var string
     */
    private const CROATIAN = '80625159724';

    /**
     * A number with the right check digit for its weights holds up.
     *
     * @return void
     * @link \App\BusinessRegister\IdentityNumber::isValidCzech()
     */
    public function testCzechNumberIsValid(): void
    {
        $this->assertTrue(IdentityNumber::isValidCzech(self::CZECH));
    }

    /**
     * The check digit is what makes it a number and not just eight digits.
     *
     * @return void
     * @link \App\BusinessRegister\IdentityNumber::isValidCzech()
     */
    public function testCzechNumberWithWrongCheckDigitIsNotValid(): void
    {
        $this->assertFalse(IdentityNumber::isValidCzech('27496138'));
    }

    /**
     * Eight digits is the whole of it - anything shorter or longer is not one.
     *
     * @return void
     * @link \App\BusinessRegister\IdentityNumber::isValidCzech()
     */
    public function testCzechNumberOfWrongLengthIsNotValid(): void
    {
        $this->assertFalse(IdentityNumber::isValidCzech('2749613'));
        $this->assertFalse(IdentityNumber::isValidCzech('274961390'));
        $this->assertFalse(IdentityNumber::isValidCzech(''));
    }

    /**
     * A number is often written with spaces in it, and that is still the number.
     *
     * @return void
     * @link \App\BusinessRegister\IdentityNumber::isValidCzech()
     */
    public function testCzechNumberIsReadThroughItsSpacing(): void
    {
        $this->assertTrue(IdentityNumber::isValidCzech(' 274 961 39 '));
    }

    /**
     * Anything that is not digits is not a number at all.
     *
     * @return void
     * @link \App\BusinessRegister\IdentityNumber::isValidCzech()
     */
    public function testCzechNumberWithLettersIsNotValid(): void
    {
        $this->assertFalse(IdentityNumber::isValidCzech('CZ274961'));
    }

    /**
     * A Croatian number is checked against ISO 7064 Mod 11,10.
     *
     * @return void
     * @link \App\BusinessRegister\IdentityNumber::isValidCroatian()
     */
    public function testCroatianNumberIsValid(): void
    {
        $this->assertTrue(IdentityNumber::isValidCroatian(self::CROATIAN));
    }

    /**
     * The last digit is the one the other ten are checked against.
     *
     * @return void
     * @link \App\BusinessRegister\IdentityNumber::isValidCroatian()
     */
    public function testCroatianNumberWithWrongCheckDigitIsNotValid(): void
    {
        $this->assertFalse(IdentityNumber::isValidCroatian('80625159725'));
    }

    /**
     * Eleven digits is the whole of it.
     *
     * @return void
     * @link \App\BusinessRegister\IdentityNumber::isValidCroatian()
     */
    public function testCroatianNumberOfWrongLengthIsNotValid(): void
    {
        $this->assertFalse(IdentityNumber::isValidCroatian('8062515972'));
        $this->assertFalse(IdentityNumber::isValidCroatian('806251597240'));
        $this->assertFalse(IdentityNumber::isValidCroatian(''));
    }

    /**
     * Without a country to go by, the number is judged by whichever country its length points at.
     *
     * @return void
     * @link \App\BusinessRegister\IdentityNumber::isValid()
     */
    public function testNumberWithoutCountryIsJudgedByItsLength(): void
    {
        $this->assertTrue(IdentityNumber::isValid(self::CZECH));
        $this->assertTrue(IdentityNumber::isValid(self::CROATIAN));
        $this->assertFalse(IdentityNumber::isValid('12345678'));
    }

    /**
     * A named country is the only one asked, so a number valid elsewhere does not pass for it.
     *
     * @return void
     * @link \App\BusinessRegister\IdentityNumber::isValid()
     */
    public function testNamedCountryIsTheOnlyOneAsked(): void
    {
        $this->assertTrue(IdentityNumber::isValid(self::CZECH, 'CZ'));
        $this->assertFalse(IdentityNumber::isValid(self::CROATIAN, 'CZ'));

        $this->assertTrue(IdentityNumber::isValid(self::CROATIAN, 'HR'));
        $this->assertFalse(IdentityNumber::isValid(self::CZECH, 'HR'));
    }

    /**
     * The country is named however it comes, and a country with no register here falls back to
     * judging the number by its length.
     *
     * @return void
     * @link \App\BusinessRegister\IdentityNumber::isValid()
     */
    public function testCountryIsReadWhicheverCaseItIsGivenIn(): void
    {
        $this->assertTrue(IdentityNumber::isValid(self::CZECH, 'cz'));
        $this->assertTrue(IdentityNumber::isValid(self::CZECH, 'SK'));
    }

    /**
     * A customer without a number has nothing to hold up.
     *
     * @return void
     * @link \App\BusinessRegister\IdentityNumber::isValid()
     */
    public function testMissingNumberIsNotValid(): void
    {
        $this->assertFalse(IdentityNumber::isValid(null));
        $this->assertFalse(IdentityNumber::isValid(null, 'CZ'));
    }
}
