<?php
declare(strict_types=1);

namespace Settings\Test\TestCase\ValueObject\Type;

use Cake\I18n\Date;
use Cake\TestSuite\TestCase;
use Settings\Exception\SettingValueException;
use Settings\ValueObject\SettingWidget;
use Settings\ValueObject\Type\DateType;

/**
 * Settings\ValueObject\Type\DateType Test Case
 *
 * Undeclared, a date is a text field holding whatever is typed into it, so a boundary a check reads
 * can come back out of the database as a word. Declaring it says this one really is a day.
 *
 * @link \Settings\ValueObject\Type\DateType
 */
class DateTypeTest extends TestCase
{
    /**
     * @return void
     * @link \Settings\ValueObject\Type\DateType::widget()
     */
    public function testADayIsEditedAsADate(): void
    {
        $this->assertSame(SettingWidget::Date, DateType::of('2000-01-01')->widget());
    }

    /**
     * @return void
     * @link \Settings\ValueObject\Type\DateType::default()
     */
    public function testTheDefaultIsWhatWasDeclared(): void
    {
        $this->assertSame('2000-01-01', DateType::of('2000-01-01')->default());
    }

    /**
     * Declaring the default as a date object is the same declaration written another way.
     *
     * @return void
     * @link \Settings\ValueObject\Type\DateType::of()
     */
    public function testADefaultMayBeDeclaredAsADate(): void
    {
        $this->assertSame('2000-01-01', DateType::of(new Date('2000-01-01'))->default());
    }

    /**
     * @return void
     * @link \Settings\ValueObject\Type\DateType::normalize()
     */
    public function testASubmittedDayIsStored(): void
    {
        $this->assertSame('2026-08-27', DateType::of('2000-01-01')->normalize('2026-08-27'));
    }

    /**
     * What a plain text field would have taken happily is the whole reason for declaring the type.
     *
     * @return void
     * @link \Settings\ValueObject\Type\DateType::normalize()
     */
    public function testSomethingThatIsNotADayIsRefused(): void
    {
        $this->expectException(SettingValueException::class);

        DateType::of('2000-01-01')->normalize('soon');
    }

    /**
     * The date control submits a whole `Y-m-d` and nothing else, so anything else was not it.
     *
     * @return void
     * @link \Settings\ValueObject\Type\DateType::normalize()
     */
    public function testADayGivenInAnotherShapeIsRefused(): void
    {
        $this->expectException(SettingValueException::class);

        DateType::of('2000-01-01')->normalize('27. 8. 2026');
    }

    /**
     * Rolling it forward into the next month would store a day nobody asked for.
     *
     * @return void
     * @link \Settings\ValueObject\Type\DateType::normalize()
     */
    public function testADayThatIsNotInTheCalendarIsRefused(): void
    {
        $this->expectException(SettingValueException::class);

        DateType::of('2000-01-01')->normalize('2026-02-30');
    }

    /**
     * A leap day exists in the year that has one.
     *
     * @return void
     * @link \Settings\ValueObject\Type\DateType::normalize()
     */
    public function testALeapDayIsTaken(): void
    {
        $this->assertSame('2024-02-29', DateType::of('2000-01-01')->normalize('2024-02-29'));
    }

    /**
     * A default that does not fit the type it is declared with is a mistake in the configuration,
     * and one worth hearing about while it is being written.
     *
     * @return void
     * @link \Settings\ValueObject\Type\DateType::__construct()
     */
    public function testADefaultThatDoesNotFitIsRefused(): void
    {
        $this->expectException(SettingValueException::class);

        DateType::of('the first of January');
    }

    /**
     * Leaving the field blank still means what it means everywhere else in the form.
     *
     * @return void
     * @link \Settings\ValueObject\Type\DateType::normalize()
     */
    public function testAnEmptyFieldAsksForTheDefault(): void
    {
        $this->assertNull(DateType::of('2000-01-01')->normalize('  '));
    }

    /**
     * A refused value comes back as it was typed, so it can be corrected rather than retyped.
     *
     * @return void
     * @link \Settings\ValueObject\Type\DateType::toFormValue()
     */
    public function testARefusedValueIsHandedBackAsItWasTyped(): void
    {
        $this->assertSame('soon', DateType::of('2000-01-01')->toFormValue('soon'));
    }

    /**
     * The date control wants `Y-m-d`, whatever the operator's language writes days as.
     *
     * @return void
     * @link \Settings\ValueObject\Type\DateType::toFormValue()
     */
    public function testADateIsShownToTheFormAsYmd(): void
    {
        $this->assertSame('2026-08-27', DateType::of('2000-01-01')->toFormValue(new Date('2026-08-27')));
    }

    /**
     * @return void
     * @link \Settings\ValueObject\Type\DateType::hint()
     */
    public function testTheHintIsCarriedToTheForm(): void
    {
        $this->assertSame('the oldest', DateType::of('2000-01-01', 'the oldest')->hint());
        $this->assertNull(DateType::of('2000-01-01')->hint());
    }
}
