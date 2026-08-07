<?php
declare(strict_types=1);

namespace App\Test\TestCase\Command;

use App\Command\ProcessSmsCommand;
use App\Model\Enum\CustomerMessageBodyFormat;
use App\Model\Enum\CustomerMessageDeliveryStatus;
use App\Model\Enum\CustomerMessageDirection;
use App\Model\Enum\CustomerMessageType;
use App\Test\Traits\EnvironmentTestTrait;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Command\ProcessSmsCommand Test Case
 *
 * The run hands its messages to an Android phone acting as a gateway, and it builds the client to
 * do it with itself - so a test cannot stand in for the phone, and one that let a message through
 * would be waiting on somebody's handset to answer. What is asked here is the other half: which
 * messages a run picks up, and that the ones it should not touch it does not.
 */
#[UsesClass(ProcessSmsCommand::class)]
class ProcessSmsCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;
    use EnvironmentTestTrait;

    /**
     * The customer the fixtures carry.
     *
     * @var string
     */
    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.AppUsers',
        'app.AccountingProfiles',
        'app.Customers',
        'app.Countries',
        'app.Addresses',
        'app.Commissions',
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

        // The run stops before it looks at anything when the gateway has no password, so a test
        // of which messages it picks up needs one said. Left to the environment it is whatever
        // the developer's `.env` holds and nothing at all on CI.
        $this->withEnvironment(['ANDROID_SMS_GATEWAY_PASSWORD' => 'not a real password']);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        $this->restoreEnvironment();

        parent::tearDown();
    }

    /**
     * Write an SMS in the state asked for.
     *
     * @param array<string, mixed> $data What this one is to say for itself.
     * @return string Id of the message.
     */
    private function sms(array $data = []): string
    {
        $messages = $this->getTableLocator()->get('CustomerMessages');
        $message = $messages->saveOrFail($messages->newEntity($data + [
            'customer_id' => self::CUSTOMER_ID,
            'type' => CustomerMessageType::Sms,
            'direction' => CustomerMessageDirection::Outgoing,
            'delivery_status' => CustomerMessageDeliveryStatus::Sent,
            'body_format' => CustomerMessageBodyFormat::Plaintext,
            'recipients' => ['+420000000000'],
            'subject' => 'A message already gone',
            'body' => 'Something worth saying.',
            'created' => DateTime::now(),
        ]));

        return (string)$message->get('id');
    }

    /**
     * The options a cron entry would name are there.
     *
     * @return void
     * @link \App\Command\ProcessSmsCommand::buildOptionParser()
     */
    public function testBuildOptionParser(): void
    {
        $this->exec('process_sms --help');

        $this->assertExitSuccess();
        $this->assertOutputContains('--limit');
        $this->assertOutputContains('--maximum_message_age');
    }

    /**
     * Nothing waiting is a quiet run rather than a failed one - and one that reaches for no phone.
     *
     * @return void
     * @link \App\Command\ProcessSmsCommand::execute()
     */
    public function testExecuteWithNothingWaiting(): void
    {
        $this->exec('process_sms');

        $this->assertExitSuccess();
    }

    /**
     * A message already gone is left where it is, rather than sent a second time.
     *
     * @return void
     * @link \App\Command\ProcessSmsCommand::execute()
     */
    public function testExecuteLeavesAMessageAlreadySent(): void
    {
        $id = $this->sms();

        $this->exec('process_sms');

        $this->assertExitSuccess();
        $this->assertSame(
            CustomerMessageDeliveryStatus::Sent,
            $this->getTableLocator()->get('CustomerMessages')->get($id)->get('delivery_status'),
        );
    }

    /**
     * A message older than the run will look back is left where it is, waiting or not.
     *
     * @return void
     * @link \App\Command\ProcessSmsCommand::execute()
     */
    public function testExecuteLeavesAMessageOlderThanItLooksBack(): void
    {
        $id = $this->sms([
            'delivery_status' => CustomerMessageDeliveryStatus::Pending,
            'created' => DateTime::now()->subDays(30),
        ]);

        $this->exec('process_sms');

        $this->assertExitSuccess();
        $this->assertSame(
            CustomerMessageDeliveryStatus::Pending,
            $this->getTableLocator()->get('CustomerMessages')->get($id)->get('delivery_status'),
        );
    }
}
