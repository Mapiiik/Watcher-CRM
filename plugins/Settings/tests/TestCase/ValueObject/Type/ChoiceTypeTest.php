<?php
declare(strict_types=1);

namespace Settings\Test\TestCase\ValueObject\Type;

use Cake\TestSuite\TestCase;
use Settings\Exception\SettingValueException;
use Settings\Test\Enum\Colour;
use Settings\ValueObject\SettingWidget;
use Settings\ValueObject\Type\ChoiceType;

/**
 * Settings\ValueObject\Type\ChoiceType Test Case
 *
 * Undeclared, a setting with a few named answers is a text field into which the exact spelling of
 * one has to be typed, and a spelling nobody recognises is only found out about wherever the value
 * is read. Declaring it offers the answers and refuses everything else on the way in.
 *
 * @link \Settings\ValueObject\Type\ChoiceType
 */
class ChoiceTypeTest extends TestCase
{
    /**
     * @return void
     * @link \Settings\ValueObject\Type\ChoiceType::widget()
     */
    public function testTheAnswersAreOfferedToChooseFrom(): void
    {
        $this->assertSame(SettingWidget::Choice, (new ChoiceType(Colour::RED))->widget());
    }

    /**
     * The value rather than the case: nothing below the service is meant to meet a type, and the
     * cache the merged settings are kept in cannot hold one.
     *
     * @return void
     * @link \Settings\ValueObject\Type\ChoiceType::default()
     */
    public function testTheDefaultIsWhatTheAnswerIsStoredAs(): void
    {
        $this->assertSame('red', (new ChoiceType(Colour::RED))->default());
    }

    /**
     * @return void
     * @link \Settings\ValueObject\Type\ChoiceType::formOptions()
     */
    public function testTheAnswersComeFromTheEnumItself(): void
    {
        $this->assertSame(
            ['options' => ['red' => 'Red', 'blue' => 'Blue']],
            (new ChoiceType(Colour::RED))->formOptions(),
        );
    }

    /**
     * @return void
     * @link \Settings\ValueObject\Type\ChoiceType::normalize()
     */
    public function testAnAnswerOnOfferIsStored(): void
    {
        $this->assertSame('blue', (new ChoiceType(Colour::RED))->normalize('blue'));
    }

    /**
     * Which is the point of declaring it: the spelling is caught on the form it was typed into,
     * rather than wherever the value is read months later.
     *
     * @return void
     * @link \Settings\ValueObject\Type\ChoiceType::normalize()
     */
    public function testAnAnswerThatIsNotOnOfferIsRefused(): void
    {
        $this->expectException(SettingValueException::class);

        (new ChoiceType(Colour::RED))->normalize('green');
    }

    /**
     * Left unchosen, which is how the form asks for the shipped value everywhere else.
     *
     * @return void
     * @link \Settings\ValueObject\Type\ChoiceType::normalize()
     */
    public function testNoAnswerAsksForTheShippedOne(): void
    {
        $this->assertNull((new ChoiceType(Colour::RED))->normalize(''));
        $this->assertNull((new ChoiceType(Colour::RED))->normalize(null));
        $this->assertNull((new ChoiceType(Colour::RED))->normalize('   '));
    }

    /**
     * @return void
     * @link \Settings\ValueObject\Type\ChoiceType::toFormValue()
     */
    public function testTheStoredAnswerIsWhatTheControlShows(): void
    {
        $this->assertSame('blue', (new ChoiceType(Colour::RED))->toFormValue('blue'));
        $this->assertSame('', (new ChoiceType(Colour::RED))->toFormValue(null));
    }

    /**
     * @return void
     * @link \Settings\ValueObject\Type\ChoiceType::hint()
     */
    public function testTheHintIsCarriedThrough(): void
    {
        $this->assertNull((new ChoiceType(Colour::RED))->hint());
        $this->assertSame('Pick one', (new ChoiceType(Colour::RED, 'Pick one'))->hint());
    }
}
