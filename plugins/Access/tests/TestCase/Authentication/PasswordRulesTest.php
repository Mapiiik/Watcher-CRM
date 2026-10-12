<?php
declare(strict_types=1);

namespace Access\Test\TestCase\Authentication;

use Access\Authentication\PasswordRules;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Access\Authentication\PasswordRules Test Case
 */
#[UsesClass(PasswordRules::class)]
class PasswordRulesTest extends TestCase
{
    /**
     * A password long enough and hard to guess passes.
     *
     * @return void
     */
    public function testAGoodPasswordPasses(): void
    {
        $rules = new PasswordRules(12);

        $this->assertSame([], $rules->problems('Kopr na zahrade 7x', 'jnovak', 'jan.novak@example.com'));
    }

    /**
     * A password shorter than asked for is refused, and says how long it has to be.
     *
     * @return void
     */
    public function testAShortPasswordIsRefused(): void
    {
        $problems = (new PasswordRules(17))->problems('Kopr na zahrade');

        $this->assertSame(['The password has to have at least 17 characters.'], $problems);
    }

    /**
     * Passwords that are easy to guess are refused, however long they are.
     *
     * @param string $password The password.
     * @return void
     */
    #[DataProvider('guessableProvider')]
    public function testAPasswordEasyToGuessIsRefused(string $password): void
    {
        $problems = (new PasswordRules(12))->problems($password, 'jnovak', 'jan.novak@example.com');

        $this->assertNotSame([], $problems);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function guessableProvider(): array
    {
        return [
            'the username' => ['jnovak-is-the-best'],
            'the e-mail' => ['Jan.Novak@example.com'],
            'one character' => ['aaaaaaaaaaaaaaa'],
            'a short piece repeated' => ['abcabcabcabcabc'],
            'a run of letters' => ['abcdefghijklmn'],
            'a run of digits down' => ['9876543210'],
            'a common one' => ['password'],
            'a common one with digits after it' => ['password2026!!'],
            'a common one in capitals' => ['PASSWORD123456'],
        ];
    }

    /**
     * Where the settings say so, only the length is asked about.
     *
     * @return void
     */
    public function testOnlyTheLengthIsAskedAboutWhenSimplePasswordsAreAllowed(): void
    {
        $rules = new PasswordRules(12, false);

        $this->assertSame([], $rules->problems('password123456', 'jnovak'));
    }
}
