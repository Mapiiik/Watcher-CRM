<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\CustomerMessage;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * App\Model\Entity\CustomerMessage Test Case
 */
class CustomerMessageTest extends TestCase
{
    /**
     * @return array<string, array{string, list<string>}>
     */
    public static function recipientsProvider(): array
    {
        return [
            'a number in groups' => ['+420 777 123 456', ['+420777123456']],
            'a number in groups without a prefix' => ['777 123 456', ['777123456']],
            'two numbers in groups' => ['+420 777 123 456, 777 654 321', ['+420777123456', '777654321']],
            'numbers too long to be one' => ['777123456 777654321', ['777123456', '777654321']],
            'two prefixes' => ['+420777123456 +420777654321', ['+420777123456', '+420777654321']],
            'addresses a space apart' => ['a@example.com b@example.com', ['a@example.com', 'b@example.com']],
            'every separator' => [
                'a@example.com;b@example.com, c@example.com d@example.com',
                ['a@example.com', 'b@example.com', 'c@example.com', 'd@example.com'],
            ],
            'nothing' => ['', []],
        ];
    }

    /**
     * What is typed into the field is divided into recipients, and a number written in groups is
     * one of them.
     *
     * @param string $typed What was typed.
     * @param list<string> $recipients Who it comes to.
     * @return void
     * @link \App\Model\Entity\CustomerMessage::_setRecipients()
     */
    #[DataProvider('recipientsProvider')]
    public function testRecipientsAreDividedFromWhatWasTyped(string $typed, array $recipients): void
    {
        $message = new CustomerMessage();
        $message->set('recipients', $typed);

        $this->assertSame($recipients, $message->recipients);
    }
}
