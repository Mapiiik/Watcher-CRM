<?php
declare(strict_types=1);

namespace App\Test\TestCase\Command;

use App\Command\ProcessEmailsCommand;
use App\Model\Enum\CustomerMessageBodyFormat;
use App\Model\Enum\CustomerMessageDeliveryStatus;
use App\Model\Enum\CustomerMessageDirection;
use App\Model\Enum\CustomerMessageType;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\I18n\DateTime;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Command\ProcessEmailsCommand Test Case
 *
 * Messages are written to the table by whoever wants one sent, and this run is what actually sends
 * them. What matters is that it takes the ones waiting, leaves the rest, and writes down that it
 * sent them - a message sent twice is worse than one sent late.
 */
#[UsesClass(ProcessEmailsCommand::class)]
class ProcessEmailsCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;
    use EmailTrait;

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
     * Write a message waiting to be sent.
     *
     * @param array<string, mixed> $data What this one is to say for itself.
     * @return string Id of the message.
     */
    private function message(array $data = []): string
    {
        $messages = $this->getTableLocator()->get('CustomerMessages');
        $message = $messages->saveOrFail($messages->newEntity($data + [
            'customer_id' => self::CUSTOMER_ID,
            'type' => CustomerMessageType::Email,
            'direction' => CustomerMessageDirection::Outgoing,
            'delivery_status' => CustomerMessageDeliveryStatus::Pending,
            'body_format' => CustomerMessageBodyFormat::Plaintext,
            'recipients' => ['customer@example.com'],
            'subject' => 'A message to send',
            'body' => 'Something worth saying.',
            'created' => DateTime::now(),
        ]));

        return (string)$message->get('id');
    }

    /**
     * The options a cron entry would name are there.
     *
     * @return void
     * @link \App\Command\ProcessEmailsCommand::buildOptionParser()
     */
    public function testBuildOptionParser(): void
    {
        $this->exec('process_emails --help');

        $this->assertExitSuccess();
        $this->assertOutputContains('--limit');
        $this->assertOutputContains('--maximum_message_age');
    }

    /**
     * A message waiting to go is sent, and set down as sent.
     *
     * @return void
     * @link \App\Command\ProcessEmailsCommand::execute()
     */
    public function testExecuteSendsAMessageThatIsWaiting(): void
    {
        $id = $this->message();

        $this->exec('process_emails');

        $this->assertExitSuccess();
        $this->assertMailSentTo('customer@example.com');
        $this->assertNotSame(
            CustomerMessageDeliveryStatus::Pending,
            $this->getTableLocator()->get('CustomerMessages')->get($id)->get('delivery_status'),
        );
    }

    /**
     * A message with nobody to send it to is put down as failed rather than handed to the
     * transport, which may take an empty recipient list and answer that it went.
     *
     * @return void
     * @link \App\Command\ProcessEmailsCommand::execute()
     */
    public function testExecuteFailsAMessageWithNobodyToSendItTo(): void
    {
        $unreachable = $this->message(['recipients' => []]);
        $waiting = $this->message();

        $this->exec('process_emails');

        $this->assertExitSuccess();

        $messages = $this->getTableLocator()->get('CustomerMessages');
        $this->assertSame(
            CustomerMessageDeliveryStatus::Failed,
            $messages->get($unreachable)->get('delivery_status'),
            'A message nobody could receive was not put down as failed.',
        );
        $this->assertNull($messages->get($unreachable)->get('identifier'), 'It was recorded as sent.');

        // and the one behind it went, which is the whole point of not stopping on it
        $this->assertMailSentTo('customer@example.com');
        $this->assertNotSame(
            CustomerMessageDeliveryStatus::Pending,
            $messages->get($waiting)->get('delivery_status'),
        );
    }

    /**
     * A message already sent is not sent again.
     *
     * @return void
     * @link \App\Command\ProcessEmailsCommand::execute()
     */
    public function testExecuteLeavesAMessageAlreadySent(): void
    {
        $this->message(['delivery_status' => CustomerMessageDeliveryStatus::Sent]);

        $this->exec('process_emails');

        $this->assertExitSuccess();
        $this->assertNoMailSent();
    }

    /**
     * A message older than the run will look back is left where it is.
     *
     * Something that has waited a fortnight is not something to send now without being asked: by
     * then whoever it was for has usually been told another way.
     *
     * @return void
     * @link \App\Command\ProcessEmailsCommand::execute()
     */
    public function testExecuteLeavesAMessageOlderThanItLooksBack(): void
    {
        $this->message(['created' => DateTime::now()->subDays(30)]);

        $this->exec('process_emails');

        $this->assertExitSuccess();
        $this->assertNoMailSent();
    }

    /**
     * Nothing waiting is a quiet run rather than a failed one.
     *
     * @return void
     * @link \App\Command\ProcessEmailsCommand::execute()
     */
    public function testExecuteWithNothingWaiting(): void
    {
        $this->exec('process_emails');

        $this->assertExitSuccess();
        $this->assertNoMailSent();
    }
}
