<?php
declare(strict_types=1);

namespace App\Test\TestCase\Utility;

use App\Utility\Strings;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Utility\Strings Test Case
 */
#[UsesClass(Strings::class)]
class StringsTest extends TestCase
{
    /**
     * The marks come off and the letters underneath stay, which is what makes a name written one way
     * findable when it was typed the other.
     *
     * @return void
     * @link \App\Utility\Strings::removeAccents()
     */
    public function testRemoveAccentsLeavesTheLettersUnderneath(): void
    {
        $this->assertSame('Prilis zlutoucky kun', Strings::removeAccents('Příliš žluťoučký kůň'));
    }

    /**
     * Text that carries no marks is handed back as it was.
     *
     * @return void
     * @link \App\Utility\Strings::removeAccents()
     */
    public function testRemoveAccentsLeavesPlainTextAlone(): void
    {
        $this->assertSame('Northern Radio Works', Strings::removeAccents('Northern Radio Works'));
    }

    /**
     * Text that is not valid UTF-8 is handed back as it stands. Nothing can be normalized about it,
     * and the stripping that follows would answer with nothing at all rather than with the text.
     *
     * @return void
     * @link \App\Utility\Strings::removeAccents()
     */
    public function testTextThatIsNotValidUtf8IsHandedBackAsItStands(): void
    {
        $broken = "Pr\xC3\x28li\xC5\xA1";

        $this->assertSame($broken, Strings::removeAccents($broken));
    }

    /**
     * A password is as long as it was asked to be.
     *
     * @return void
     * @link \App\Utility\Strings::generatePassword()
     */
    public function testAPasswordIsAsLongAsItWasAskedToBe(): void
    {
        $this->assertSame(12, strlen(Strings::generatePassword(12)));
    }

    /**
     * No character is used twice. That is what the generator is written around, and it is also what
     * puts a ceiling on the length: a password can be no longer than the set it is drawn from.
     *
     * @return void
     * @link \App\Utility\Strings::generatePassword()
     */
    public function testNoCharacterIsUsedTwice(): void
    {
        $password = Strings::generatePassword(20);

        $this->assertSame(20, count(array_unique(str_split($password))));
    }

    /**
     * The characters come from the set that was asked for, and from nowhere else.
     *
     * @return void
     * @link \App\Utility\Strings::generatePassword()
     */
    public function testTheCharactersComeFromTheSetThatWasAskedFor(): void
    {
        $password = Strings::generatePassword(4, 'abcd');

        $this->assertSame(['a', 'b', 'c', 'd'], $this->sortedCharactersOf($password));
    }

    /**
     * Two passwords are not the same one. Deriving them from the clock or from a counter would have
     * everything set up in the same minute share a password.
     *
     * @return void
     * @link \App\Utility\Strings::generatePassword()
     */
    public function testTwoPasswordsAreNotTheSameOne(): void
    {
        $this->assertNotSame(Strings::generatePassword(16), Strings::generatePassword(16));
    }

    /**
     * The characters a password is made of, sorted.
     *
     * @param string $password Password to take apart.
     * @return array<string>
     */
    private function sortedCharactersOf(string $password): array
    {
        $characters = str_split($password);

        sort($characters);

        return $characters;
    }
}
