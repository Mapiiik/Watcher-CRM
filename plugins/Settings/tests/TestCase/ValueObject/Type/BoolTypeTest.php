<?php
declare(strict_types=1);

namespace Settings\Test\TestCase\ValueObject\Type;

use Cake\TestSuite\TestCase;
use Settings\ValueObject\SettingWidget;
use Settings\ValueObject\Type\BoolType;

/**
 * Settings\ValueObject\Type\BoolType Test Case
 *
 * Undeclared, a switch is drawn as a text field, and everything a text field holds that is not
 * empty reads as true - the word "false" included. Declaring it settles what comes back out of the
 * database, and gives it the three answers the rest of the form has: the shipped value, or either
 * one of its own.
 *
 * @link \Settings\ValueObject\Type\BoolType
 */
class BoolTypeTest extends TestCase
{
    /**
     * A switch is edited as a choice of three, because two of them - on and off - are both answers
     * and leave nowhere to say "whatever was shipped".
     *
     * @return void
     * @link \Settings\ValueObject\Type\BoolType::widget()
     */
    public function testASwitchIsEditedAsAChoiceOfThree(): void
    {
        $this->assertSame(SettingWidget::TriState, (new BoolType(true))->widget());
    }

    /**
     * @return void
     * @link \Settings\ValueObject\Type\BoolType::default()
     */
    public function testTheDefaultIsWhatWasDeclared(): void
    {
        $this->assertTrue((new BoolType(true))->default());
        $this->assertFalse((new BoolType(false))->default());
    }

    /**
     * @return void
     * @link \Settings\ValueObject\Type\BoolType::normalize()
     */
    public function testAnsweringOffIsStoredAsOff(): void
    {
        $this->assertFalse((new BoolType(true))->normalize('0'));
    }

    /**
     * @return void
     * @link \Settings\ValueObject\Type\BoolType::normalize()
     */
    public function testAnsweringOnIsStoredAsOn(): void
    {
        $this->assertTrue((new BoolType(false))->normalize('1'));
    }

    /**
     * Leaving the answer unchosen asks for the shipped value, which is what null says to the
     * service. Off is an answer of its own and must not be confused with it.
     *
     * @return void
     * @link \Settings\ValueObject\Type\BoolType::normalize()
     */
    public function testLeavingTheAnswerUnchosenAsksForTheDefault(): void
    {
        $this->assertNull((new BoolType(true))->normalize(''));
        $this->assertFalse((new BoolType(true))->normalize('0'));
    }

    /**
     * A value that reached the database while the setting was still a text field says what it means
     * in words, and meant it.
     *
     * @return void
     * @link \Settings\ValueObject\Type\BoolType::normalize()
     */
    public function testASwitchWrittenInWordsIsReadAsItWasMeant(): void
    {
        $this->assertFalse((new BoolType(true))->normalize('false'));
        $this->assertFalse((new BoolType(true))->normalize('off'));
        $this->assertTrue((new BoolType(false))->normalize('true'));
    }

    /**
     * What is stored decides which of the three answers the form comes up showing.
     *
     * @return void
     * @link \Settings\ValueObject\Type\BoolType::toFormValue()
     */
    public function testWhatIsStoredIsShownAsTheAnswerGiven(): void
    {
        $this->assertSame('1', (new BoolType(false))->toFormValue(true));
        $this->assertSame('0', (new BoolType(true))->toFormValue(false));
    }

    /**
     * With nothing stored, no answer has been given and the form says so rather than showing the
     * shipped value as though somebody had chosen it.
     *
     * @return void
     * @link \Settings\ValueObject\Type\BoolType::toFormValue()
     */
    public function testWithNothingStoredNoAnswerIsShown(): void
    {
        $this->assertSame('', (new BoolType(true))->toFormValue(null));
    }

    /**
     * @return void
     * @link \Settings\ValueObject\Type\BoolType::hint()
     */
    public function testTheHintIsCarriedToTheForm(): void
    {
        $this->assertSame('turns everything off', (new BoolType(true, 'turns everything off'))->hint());
        $this->assertNull((new BoolType(true))->hint());
    }
}
