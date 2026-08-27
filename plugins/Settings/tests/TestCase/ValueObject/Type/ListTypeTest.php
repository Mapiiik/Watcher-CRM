<?php
declare(strict_types=1);

namespace Settings\Test\TestCase\ValueObject\Type;

use Cake\TestSuite\TestCase;
use Settings\Exception\SettingValueException;
use Settings\ValueObject\SettingWidget;
use Settings\ValueObject\Type\ListType;

/**
 * Settings\ValueObject\Type\ListType Test Case
 *
 * A list is edited as JSON because the field it replaces could not say two of the things a list
 * needs to be able to say: that an item is the number 5 rather than the text "5", and that a list
 * has no items at all. A blank field goes on meaning "use what was shipped".
 *
 * @link \Settings\ValueObject\Type\ListType
 */
class ListTypeTest extends TestCase
{
    /**
     * A list is edited as JSON rather than as a line of text.
     *
     * @return void
     * @link \Settings\ValueObject\Type\ListType::widget()
     */
    public function testAListIsEditedAsJson(): void
    {
        $this->assertSame(SettingWidget::Json, ListType::ofStrings([])->widget());
    }

    /**
     * What was shipped is what the type was declared with.
     *
     * @return void
     * @link \Settings\ValueObject\Type\ListType::default()
     */
    public function testTheDefaultIsWhatWasDeclared(): void
    {
        $this->assertSame(['first', 'second'], ListType::ofStrings(['first', 'second'])->default());
    }

    /**
     * A default that does not fit the type it is declared with is a mistake in the configuration,
     * and one worth hearing about while it is being written rather than when somebody saves a form.
     *
     * @return void
     * @link \Settings\ValueObject\Type\ListType::__construct()
     */
    public function testADefaultThatDoesNotFitIsRefused(): void
    {
        $this->expectException(SettingValueException::class);

        ListType::ofInts(['not a number']);
    }

    /**
     * What was submitted is read as JSON.
     *
     * @return void
     * @link \Settings\ValueObject\Type\ListType::normalize()
     */
    public function testASubmittedListIsRead(): void
    {
        $this->assertSame(
            ['first', 'second'],
            ListType::ofStrings([])->normalize('["first", "second"]'),
        );
    }

    /**
     * The point of writing a list as JSON is that a number stays a number. A list of days compared
     * against a count of days would never match if its items came back as text.
     *
     * @return void
     * @link \Settings\ValueObject\Type\ListType::normalize()
     */
    public function testNumbersAreStoredAsNumbers(): void
    {
        $this->assertSame([5, 10], ListType::ofInts([])->normalize('[5, 10]'));
    }

    /**
     * A number typed with quotes around it was still meant as a number.
     *
     * @return void
     * @link \Settings\ValueObject\Type\ListType::normalize()
     */
    public function testANumberWrittenAsTextIsTakenAsANumber(): void
    {
        $this->assertSame([5, 10], ListType::ofInts([])->normalize('["5", "10"]'));
    }

    /**
     * Rounding a submitted value to fit would store something nobody asked for.
     *
     * @return void
     * @link \Settings\ValueObject\Type\ListType::normalize()
     */
    public function testAFractionIsRefusedWhereWholeNumbersWereDeclared(): void
    {
        $this->expectException(SettingValueException::class);

        ListType::ofInts([])->normalize('[5.5]');
    }

    /**
     * @return void
     * @link \Settings\ValueObject\Type\ListType::normalize()
     */
    public function testTextIsRefusedWhereNumbersWereDeclared(): void
    {
        $this->expectException(SettingValueException::class);

        ListType::ofInts([])->normalize('["soon"]');
    }

    /**
     * @return void
     * @link \Settings\ValueObject\Type\ListType::normalize()
     */
    public function testSomethingThatIsNotJsonIsRefused(): void
    {
        $this->expectException(SettingValueException::class);

        ListType::ofStrings([])->normalize('first, second');
    }

    /**
     * A list is a run of items, so something written with keys is not one however well it parses.
     *
     * @return void
     * @link \Settings\ValueObject\Type\ListType::normalize()
     */
    public function testAKeyedValueIsRefused(): void
    {
        $this->expectException(SettingValueException::class);

        ListType::ofStrings([])->normalize('{"first": "second"}');
    }

    /**
     * Nothing submitted means the default should answer, which is what null says to the service.
     *
     * @return void
     * @link \Settings\ValueObject\Type\ListType::normalize()
     */
    public function testAnEmptyFieldAsksForTheDefault(): void
    {
        $this->assertNull(ListType::ofStrings(['shipped'])->normalize('   '));
    }

    /**
     * A list with no items in it is a thing an operator may well want, and is why the field is
     * edited as JSON rather than as a blank line that could only ever mean the default.
     *
     * @return void
     * @link \Settings\ValueObject\Type\ListType::normalize()
     */
    public function testAListWithNoItemsIsNotTheSameAsAnEmptyField(): void
    {
        $this->assertSame([], ListType::ofStrings(['shipped'])->normalize('[]'));
    }

    /**
     * A caller writing a setting from code hands over the list itself rather than JSON.
     *
     * @return void
     * @link \Settings\ValueObject\Type\ListType::normalize()
     */
    public function testAListPassedFromCodeIsCheckedTheSameWay(): void
    {
        $this->assertSame([5], ListType::ofInts([])->normalize(['5']));
    }

    /**
     * A list of days is held to the same rule as a single day, so the two agree on what one is.
     *
     * @return void
     * @link \Settings\ValueObject\Type\ListType::ofDates()
     */
    public function testDaysAreStoredAsDays(): void
    {
        $this->assertSame(
            ['1800-01-01', '2026-08-27'],
            ListType::ofDates([])->normalize('["1800-01-01", "2026-08-27"]'),
        );
    }

    /**
     * @return void
     * @link \Settings\ValueObject\Type\ListType::ofDates()
     */
    public function testSomethingThatIsNotADayIsRefusedWhereDaysWereDeclared(): void
    {
        $this->expectException(SettingValueException::class);

        ListType::ofDates([])->normalize('["1800-01-01", "soon"]');
    }

    /**
     * A day outside its month would otherwise roll forward into the next one, which is not the day
     * that was written.
     *
     * @return void
     * @link \Settings\ValueObject\Type\ListType::ofDates()
     */
    public function testADayThatIsNotInTheCalendarIsRefused(): void
    {
        $this->expectException(SettingValueException::class);

        ListType::ofDates([])->normalize('["2026-02-30"]');
    }

    /**
     * The JSON is put in front of a person to edit, so it is written to be read: laid out over
     * several lines and with accented characters left as themselves rather than as escapes.
     *
     * @return void
     * @link \Settings\ValueObject\Type\ListType::toFormValue()
     */
    public function testWhatIsShownIsWrittenToBeRead(): void
    {
        $shown = (string)ListType::ofStrings([])->toFormValue(['žebřík 8m', 'skládačka 3,6m']);

        $this->assertStringContainsString('žebřík 8m', $shown);
        $this->assertStringContainsString("\n", $shown);
    }

    /**
     * A refused value comes back as it was typed. Showing the stored list instead would throw away
     * the edit that was being made and leave nothing to correct.
     *
     * @return void
     * @link \Settings\ValueObject\Type\ListType::toFormValue()
     */
    public function testARefusedValueIsHandedBackAsItWasTyped(): void
    {
        $this->assertSame('["first",', ListType::ofStrings([])->toFormValue('["first",'));
    }

    /**
     * @return void
     * @link \Settings\ValueObject\Type\ListType::hint()
     */
    public function testTheHintIsCarriedToTheForm(): void
    {
        $this->assertSame('one per line', ListType::ofStrings([], 'one per line')->hint());
        $this->assertNull(ListType::ofStrings([])->hint());
    }
}
