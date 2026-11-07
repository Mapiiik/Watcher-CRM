<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Enum\CustomerMessageBodyFormat;
use App\Model\Enum\CustomerMessageType;
use App\Model\Table\CustomerMessagesTable;
use App\Test\Traits\TableTestTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * App\Model\Table\CustomerMessagesTable Test Case
 */
class CustomerMessagesTableTest extends TestCase
{
    use TableTestTrait;

    /**
     * Test subject
     *
     * @var \App\Model\Table\CustomerMessagesTable
     */
    protected $CustomerMessages;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
        'app.AccountingProfiles',
        'app.Customers',
        'app.CustomerMessages',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('CustomerMessages') ? [] : ['className' => CustomerMessagesTable::class];
        $this->CustomerMessages = $this->getTableLocator()->get('CustomerMessages', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        /** @phpstan-ignore unset.possiblyHookedProperty */
        unset($this->CustomerMessages);

        parent::tearDown();
    }

    /**
     * A new record with nothing filled in is refused - see the trait for why that is the question
     * worth asking here.
     *
     * @return void
     * @link \App\Model\Table\CustomerMessagesTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->assertEmptyRecordIsRefused($this->CustomerMessages);
    }

    /**
     * The rules refuse a record whose references point nowhere - see the trait for why that is
     * the question worth asking here.
     *
     * @return void
     * @link \App\Model\Table\CustomerMessagesTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->assertDanglingReferencesAreRefused($this->CustomerMessages);
    }

    /**
     * @return array<string, array{mixed, mixed, bool}>
     */
    public static function bodyFormatProvider(): array
    {
        $sms = CustomerMessageType::Sms;
        $email = CustomerMessageType::Email;
        $plain = CustomerMessageBodyFormat::Plaintext;
        $html = CustomerMessageBodyFormat::Html;

        return [
            'an SMS in HTML from a form' => [(string)$sms->value, (string)$html->value, true],
            'an SMS in plain text from a form' => [(string)$sms->value, (string)$plain->value, false],
            'an e-mail in HTML from a form' => [(string)$email->value, (string)$html->value, false],
            'an SMS in HTML from the code' => [$sms, $html, true],
            'an SMS in plain text from the code' => [$sms, $plain, false],
            'an e-mail in HTML from the code' => [$email, $html, false],
            'no type' => [null, (string)$html->value, false],
        ];
    }

    /**
     * Only plain text is sent as an SMS, whichever way the values arrive. Other messages may be
     * in HTML.
     *
     * @param mixed $type The type of the message.
     * @param mixed $format The format of its body.
     * @param bool $refused Whether the format is refused.
     * @return void
     * @link \App\Model\Table\CustomerMessagesTable::validationDefault()
     */
    #[DataProvider('bodyFormatProvider')]
    public function testAnSmsIsPlainText(mixed $type, mixed $format, bool $refused): void
    {
        $errors = $this->CustomerMessages->getValidator()->validate(['type' => $type, 'body_format' => $format]);

        $this->assertSame($refused, isset($errors['body_format']['plainTextSms']));
    }
}
