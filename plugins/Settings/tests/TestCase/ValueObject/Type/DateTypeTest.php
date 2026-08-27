<?php
declare(strict_types=1);

namespace Settings\Test\TestCase\ValueObject\Type;

use Cake\I18n\Date;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
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
     * A day is held against the calendar rather than parsed and looked at.
     *
     * Parsing reads the day in the machine's timezone, and before about 1884 a zone's offset was
     * its town's own solar time - which ICU rounds to whole minutes and the timezone database does
     * not. Those few seconds handed back the day before: an installation running in Europe/Zagreb
     * took the whole application down over a default that every other installation accepted.
     *
     * @param string $zone The timezone the machine stands in.
     * @return void
     * @link \Settings\ValueObject\Type\DateType::canonicalDay()
     */
    #[DataProvider('timezones')]
    public function testADayMeansTheSameWhereverTheMachineStands(string $zone): void
    {
        $was = date_default_timezone_get();
        date_default_timezone_set($zone);

        try {
            $this->assertSame('1800-01-01', DateType::of('1800-01-01')->default());
            $this->assertSame('1800-01-01', DateType::of('2000-01-01')->normalize('1800-01-01'));
            $this->assertNull(DateType::canonicalDay('2026-02-30'), 'A day off the calendar still is one.');
        } finally {
            date_default_timezone_set($was);
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function timezones(): array
    {
        return [
            'UTC' => ['UTC'],
            'where the office is' => ['Europe/Prague'],
            'where it broke' => ['Europe/Zagreb'],
            'the far side of the date line' => ['Pacific/Kiritimati'],
        ];
    }

    /**
     * The defaults are declared in a configuration file the service reads on the way up, so a
     * type that threw over one of them took the whole application down - every page, including
     * the settings page where the declaration could have been put right. It goes to the log
     * instead, and what can be kept is kept.
     *
     * What a person submits is another matter: {@see self::testSomethingThatIsNotADayIsRefused()} still refuses it,
     * out loud, on the form it was typed into.
     *
     * @return void
     */
    public function testADefaultThatDoesNotFitIsKeptRatherThanThrown(): void
    {
        $type = DateType::of('the first of January');

        $this->assertSame('the first of January', $type->default());
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
