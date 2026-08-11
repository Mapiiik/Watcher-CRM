<?php
declare(strict_types=1);

namespace Settings\Test\TestCase\ValueObject\Type;

use Cake\TestSuite\TestCase;
use Settings\Exception\SettingValueException;
use Settings\ValueObject\SettingWidget;
use Settings\ValueObject\Type\NumberType;

/**
 * Settings\ValueObject\Type\NumberType Test Case
 *
 * Undeclared, a numeric setting is a text field holding whatever is typed into it, so a count of
 * days can come back out of the database as a word. Declaring it says this one really is a number,
 * and what kind.
 *
 * @link \Settings\ValueObject\Type\NumberType
 */
class NumberTypeTest extends TestCase
{
    /**
     * @return void
     * @link \Settings\ValueObject\Type\NumberType::widget()
     */
    public function testANumberIsEditedAsANumber(): void
    {
        $this->assertSame(SettingWidget::Number, NumberType::ofInt(0)->widget());
    }

    /**
     * @return void
     * @link \Settings\ValueObject\Type\NumberType::default()
     */
    public function testTheDefaultIsWhatWasDeclared(): void
    {
        $this->assertSame(5, NumberType::ofInt(5)->default());
        $this->assertSame(1.5, NumberType::ofDecimal(1.5)->default());
    }

    /**
     * A number typed into a form arrives as text, and is still a number.
     *
     * @return void
     * @link \Settings\ValueObject\Type\NumberType::normalize()
     */
    public function testASubmittedNumberIsStoredAsANumber(): void
    {
        $this->assertSame(10, NumberType::ofInt(0)->normalize('10'));
        $this->assertSame(10.25, NumberType::ofDecimal(0)->normalize('10.25'));
    }

    /**
     * What a plain text field would have taken happily is the whole reason for declaring the type.
     *
     * @return void
     * @link \Settings\ValueObject\Type\NumberType::normalize()
     */
    public function testSomethingThatIsNotANumberIsRefused(): void
    {
        $this->expectException(SettingValueException::class);

        NumberType::ofInt(0)->normalize('soon');
    }

    /**
     * Rounding a submitted value to fit would store a number nobody asked for.
     *
     * @return void
     * @link \Settings\ValueObject\Type\NumberType::normalize()
     */
    public function testAFractionIsRefusedWhereWholeNumbersWereDeclared(): void
    {
        $this->expectException(SettingValueException::class);

        NumberType::ofInt(0)->normalize('5.5');
    }

    /**
     * @return void
     * @link \Settings\ValueObject\Type\NumberType::normalize()
     */
    public function testAValueGivenTooFinelyIsRefused(): void
    {
        $this->expectException(SettingValueException::class);

        NumberType::ofDecimal(0, 2)->normalize('10.005');
    }

    /**
     * A number given to fewer places than allowed was given properly.
     *
     * @return void
     * @link \Settings\ValueObject\Type\NumberType::normalize()
     */
    public function testAValueGivenLessFinelyThanAllowedIsTaken(): void
    {
        $this->assertSame(10.0, NumberType::ofDecimal(0, 2)->normalize('10'));
    }

    /**
     * A default that does not fit the type it is declared with is a mistake in the configuration,
     * and one worth hearing about while it is being written.
     *
     * @return void
     * @link \Settings\ValueObject\Type\NumberType::__construct()
     */
    public function testADefaultThatDoesNotFitIsRefused(): void
    {
        $this->expectException(SettingValueException::class);

        NumberType::ofDecimal(1.005, 2);
    }

    /**
     * Leaving the field blank still means what it means everywhere else in the form.
     *
     * @return void
     * @link \Settings\ValueObject\Type\NumberType::normalize()
     */
    public function testAnEmptyFieldAsksForTheDefault(): void
    {
        $this->assertNull(NumberType::ofInt(5)->normalize('  '));
    }

    /**
     * How finely the number may be given is settled by the type, since the form has no way of
     * knowing it.
     *
     * @return void
     * @link \Settings\ValueObject\Type\NumberType::formOptions()
     */
    public function testTheFormIsToldHowFinelyTheNumberMayBeGiven(): void
    {
        $this->assertSame(['step' => '1'], NumberType::ofInt(0)->formOptions());
        $this->assertSame(['step' => '0.01'], NumberType::ofDecimal(0, 2)->formOptions());
        $this->assertSame(['step' => '0.001'], NumberType::ofDecimal(0, 3)->formOptions());
    }

    /**
     * A refused value comes back as it was typed, so it can be corrected rather than retyped.
     *
     * @return void
     * @link \Settings\ValueObject\Type\NumberType::toFormValue()
     */
    public function testARefusedValueIsHandedBackAsItWasTyped(): void
    {
        $this->assertSame('soon', NumberType::ofInt(0)->toFormValue('soon'));
    }

    /**
     * @return void
     * @link \Settings\ValueObject\Type\NumberType::hint()
     */
    public function testTheHintIsCarriedToTheForm(): void
    {
        $this->assertSame('in seconds', NumberType::ofInt(0, 'in seconds')->hint());
        $this->assertNull(NumberType::ofInt(0)->hint());
    }
}
