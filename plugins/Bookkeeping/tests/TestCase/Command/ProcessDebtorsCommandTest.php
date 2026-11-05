<?php
declare(strict_types=1);

namespace Bookkeeping\Test\TestCase\Command;

use App\Model\Entity\Contract;
use App\Model\Entity\ContractState;
use App\Model\Entity\Customer;
use App\Model\Entity\CustomerMessage;
use App\Model\Entity\Email;
use App\Model\Entity\Phone;
use App\Model\Enum\CustomerMessageType;
use Bookkeeping\Command\ProcessDebtorsCommand;
use Bookkeeping\Debtors\Debtor;
use Bookkeeping\Model\Entity\Invoice;
use Bookkeeping\Service\BookkeepingService;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Console\TestSuite\StubConsoleOutput;
use Cake\Core\Configure;
use Cake\I18n\Date;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\TestCase;
use PhpCollective\DecimalObject\Decimal;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;
use Settings\Utility\Settings;

/**
 * Bookkeeping\Command\ProcessDebtorsCommand Test Case
 *
 * A run reads who is overdue from the accounting system and can go on to block them, which a test
 * is no place to reach or to have happen. What is covered here is what can be answered without it:
 * the name, the description and the options a cron entry names the command by. They are what tells
 * a run to only notify from one that blocks, so a renamed one turns a warning into a disconnection.
 */
#[UsesClass(ProcessDebtorsCommand::class)]
class ProcessDebtorsCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;
    use EmailTrait;

    /**
     * Whose the messages are.
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
        'app.Users',
        'app.AccountingProfiles',
        'app.Customers',
        'app.CustomerMessages',
    ];

    /**
     * The name a cron entry calls the command by.
     *
     * @return void
     * @link \Bookkeeping\Command\ProcessDebtorsCommand::defaultName()
     */
    public function testDefaultName(): void
    {
        $this->assertSame('process_debtors', ProcessDebtorsCommand::defaultName());
    }

    /**
     * The command says what it does in the list of commands, which is where an operator looks.
     *
     * @return void
     * @link \Bookkeeping\Command\ProcessDebtorsCommand::getDescription()
     */
    public function testGetDescription(): void
    {
        $this->assertNotEmpty(ProcessDebtorsCommand::getDescription());

        $this->exec('process_debtors --help');

        $this->assertExitSuccess();
        $this->assertOutputContains(ProcessDebtorsCommand::getDescription());
    }

    /**
     * A debtor with one invoice of a given number.
     *
     * @param int $nid What the customer's number is worked out from.
     * @param string $invoice The number of the invoice.
     * @return \Bookkeeping\Debtors\Debtor
     */
    private function debtor(int $nid, string $invoice): Debtor
    {
        return new Debtor([new Invoice([
            'number' => $invoice,
            'customer' => new Customer(['nid' => $nid]),
            'due_date' => new Date('2026-10-01'),
            'debt' => Decimal::create('100'),
        ])]);
    }

    /**
     * A message goes out with its invoices, so a customer whose PDF cannot be had gets none at all,
     * and whoever is on call is told who it was, which invoice it was and what the accounting
     * system said. Nobody after them is held up: the blocking has happened by then, and they would
     * be cut off without a word.
     *
     * @return void
     * @link \Bookkeeping\Command\ProcessDebtorsCommand::writingTo()
     */
    public function testACustomerWhoseInvoiceHasNoPdfIsPassedOverAndReported(): void
    {
        Configure::write('Report.errorEmails', ['operator@example.com']);

        $bookkeeping = $this->createStub(BookkeepingService::class);
        $bookkeeping->method('getInvoicePdfPath')->willThrowException(
            new RuntimeException('Eurofaktura API error: Error nr: 7 #documentNotFound'),
        );

        $command = new ProcessDebtorsCommand();
        (new ReflectionProperty(ProcessDebtorsCommand::class, 'bookkeeping'))->setValue($command, $bookkeeping);

        $attachments = new ReflectionMethod(ProcessDebtorsCommand::class, 'getAttachments');
        $writingTo = new ReflectionMethod(ProcessDebtorsCommand::class, 'writingTo');
        $io = new ConsoleIo(new StubConsoleOutput(), new StubConsoleOutput());
        $args = new Arguments([], [], []);

        $missing = $this->debtor(7001, 'FA-1');
        $reached = false;

        $writingTo->invoke($command, $missing, fn(): mixed => $attachments->invoke($command, $missing), $args, $io);
        $writingTo->invoke($command, $this->debtor(7002, 'FA-2'), function () use (&$reached): void {
            $reached = true;
        }, $args, $io);

        (new ReflectionMethod(ProcessDebtorsCommand::class, 'reportWhoWasPassedOver'))->invoke($command, $io);

        Configure::delete('Report.errorEmails');

        $this->assertTrue($reached, 'The customer after them was held up.');
        $this->assertMailSentTo('operator@example.com');
        $this->assertMailContains($missing->getCustomer()->number);
        $this->assertMailContains('FA-1');
        $this->assertMailContains('Eurofaktura API error: Error nr: 7 #documentNotFound');
    }

    /**
     * Nobody passed over, nothing to report.
     *
     * @return void
     * @link \Bookkeeping\Command\ProcessDebtorsCommand::reportWhoWasPassedOver()
     */
    public function testNobodyPassedOverReportsNothing(): void
    {
        Configure::write('Report.errorEmails', ['operator@example.com']);

        (new ReflectionMethod(ProcessDebtorsCommand::class, 'reportWhoWasPassedOver'))->invoke(
            new ProcessDebtorsCommand(),
            new ConsoleIo(new StubConsoleOutput(), new StubConsoleOutput()),
        );

        Configure::delete('Report.errorEmails');

        $this->assertNoMailSent();
    }

    /**
     * A reminder goes to the billing addresses and nowhere else, with the invoices it is about.
     *
     * @return void
     * @link \Bookkeeping\Command\ProcessDebtorsCommand::remind()
     */
    public function testAReminderGoesToTheBillingAddressesWithItsInvoices(): void
    {
        $written = $this->written('remind', $this->customer(
            emails: ['billing@example.com' => true, 'other@example.com' => false],
            phones: ['+420601000001' => true],
        ));

        $this->assertCount(1, $written);
        $this->assertSame(CustomerMessageType::EmailInvoices, $written[0]->type);
        $this->assertSame(['billing@example.com'], $written[0]->recipients);
        $this->assertSaysWhatTheSettingsSay('bookkeeping.debtors.emails.notify', 'body_text', $written[0]);
        $this->assertNotEmpty($written[0]->attachments);
    }

    /**
     * Without a billing address a reminder goes by SMS, to the billing phones.
     *
     * @return void
     * @link \Bookkeeping\Command\ProcessDebtorsCommand::remind()
     */
    public function testWithoutABillingAddressAReminderGoesBySms(): void
    {
        $written = $this->written('remind', $this->customer(
            emails: ['other@example.com' => false],
            phones: ['+420601000001' => true, '+420601000002' => false],
        ));

        $this->assertCount(1, $written);
        $this->assertSame(CustomerMessageType::Sms, $written[0]->type);
        $this->assertSame(['+420601000001'], $written[0]->recipients);
        $this->assertSaysWhatTheSettingsSay('bookkeeping.debtors.sms.notify', 'body', $written[0]);
        $this->assertEmpty($written[0]->attachments);
    }

    /**
     * Being blocked is told on every address and every phone there is, billing or not, because this
     * one has to arrive.
     *
     * @return void
     * @link \Bookkeeping\Command\ProcessDebtorsCommand::tellTheBlocked()
     */
    public function testTheBlockedAreToldEverywhere(): void
    {
        $written = $this->written('tellTheBlocked', $this->customer(
            emails: ['billing@example.com' => true, 'other@example.com' => false],
            phones: ['+420601000001' => true, '+420601000002' => false],
        ));

        $this->assertCount(2, $written);
        $this->assertSame(CustomerMessageType::EmailInvoices, $written[0]->type);
        $this->assertSame(['billing@example.com', 'other@example.com'], $written[0]->recipients);
        $this->assertSaysWhatTheSettingsSay('bookkeeping.debtors.emails.block', 'body_text', $written[0]);
        $this->assertNotEmpty($written[0]->attachments);

        $this->assertSame(CustomerMessageType::Sms, $written[1]->type);
        $this->assertSame(['+420601000001', '+420601000002'], $written[1]->recipients);
        $this->assertSaysWhatTheSettingsSay('bookkeeping.debtors.sms.block', 'body', $written[1]);
    }

    /**
     * Whoever has no service left has nothing to be blocked from, so they are told about the debt.
     *
     * @return void
     * @link \Bookkeeping\Command\ProcessDebtorsCommand::tellTheBlocked()
     */
    public function testWhoHasNoServiceLeftIsToldAboutTheDebt(): void
    {
        $written = $this->written('tellTheBlocked', $this->customer(
            emails: ['other@example.com' => false],
            phones: ['+420601000002' => false],
            activeServices: false,
        ));

        $this->assertCount(2, $written);
        $this->assertSame(['other@example.com'], $written[0]->recipients);
        $this->assertSaysWhatTheSettingsSay('bookkeeping.debtors.emails.inactive', 'body_text', $written[0]);
        $this->assertSame(['+420601000002'], $written[1]->recipients);
        $this->assertSaysWhatTheSettingsSay('bookkeeping.debtors.sms.inactive', 'body', $written[1]);
    }

    /**
     * A channel left out on the command line stays out, whatever is being said.
     *
     * @param string $method What is being said.
     * @return void
     * @link \Bookkeeping\Command\ProcessDebtorsCommand::remind()
     * @link \Bookkeeping\Command\ProcessDebtorsCommand::tellTheBlocked()
     */
    #[TestWith(['remind'])]
    #[TestWith(['tellTheBlocked'])]
    public function testAChannelLeftOutStaysOut(string $method): void
    {
        $customer = fn(): Customer => $this->customer(
            emails: ['billing@example.com' => true],
            phones: ['+420601000001' => true],
        );

        $this->assertSame([], $this->written($method, $customer(), ['skip_emails' => true, 'skip_sms' => true]));

        foreach ($this->written($method, $customer(), ['skip_emails' => true]) as $message) {
            $this->assertSame(CustomerMessageType::Sms, $message->type);
        }
    }

    /**
     * A customer who can be written to.
     *
     * @param array<string, bool> $emails Their addresses, and whether each is for billing.
     * @param array<string, bool> $phones Their phones, and whether each is for billing.
     * @param bool $activeServices Whether anything of theirs is running.
     * @return \App\Model\Entity\Customer
     */
    private function customer(array $emails, array $phones, bool $activeServices = true): Customer
    {
        $records = function (string $class, string $field, array $values): array {
            $found = [];
            foreach ($values as $value => $forBilling) {
                $found[] = new $class([$field => (string)$value, 'use_for_billing' => $forBilling]);
            }

            return $found;
        };

        return new Customer([
            'id' => self::CUSTOMER_ID,
            'nid' => 1,
            'emails' => $records(Email::class, 'email', $emails),
            'phones' => $records(Phone::class, 'phone', $phones),
            'contracts' => [new Contract([
                'contract_state' => new ContractState(['active_services' => $activeServices]),
            ])],
        ]);
    }

    /**
     * What a step of the command writes to a debtor, in the order it was written.
     *
     * @param string $method Which step.
     * @param \App\Model\Entity\Customer $customer The debtor.
     * @param array<string, bool> $options What the command line says.
     * @return list<\App\Model\Entity\CustomerMessage>
     */
    private function written(string $method, Customer $customer, array $options = []): array
    {
        $bookkeeping = $this->createStub(BookkeepingService::class);
        $bookkeeping->method('getInvoicePdfPath')->willReturn('/tmp/FA-1.pdf');

        $command = new ProcessDebtorsCommand();
        (new ReflectionProperty(ProcessDebtorsCommand::class, 'bookkeeping'))->setValue($command, $bookkeeping);

        $debtor = new Debtor([new Invoice([
            'number' => 'FA-1',
            'variable_symbol' => '1',
            'customer' => $customer,
            'creation_date' => new Date('2026-09-01'),
            'due_date' => new Date('2026-10-01'),
            'total' => Decimal::create('100'),
            'debt' => Decimal::create('100'),
        ])]);

        $messages = $this->getTableLocator()->get('CustomerMessages');
        $before = $messages->find()->all()->extract('id')->toList();

        (new ReflectionMethod(ProcessDebtorsCommand::class, $method))->invoke(
            $command,
            $debtor,
            new Arguments([], $options + ['skip_emails' => false, 'skip_sms' => false], []),
            new ConsoleIo(new StubConsoleOutput(), new StubConsoleOutput()),
        );

        /** @var list<\App\Model\Entity\CustomerMessage> */
        return $messages->find()
            ->where($before === [] ? [] : ['id NOT IN' => $before])
            ->orderBy(['type' => 'ASC'])
            ->all()
            ->toList();
    }

    /**
     * That a message is the one the settings hold, placeholders filled in.
     *
     * @param string $key Where the texts are.
     * @param string $bodyKey What the body is called there.
     * @param \App\Model\Entity\CustomerMessage $message The message.
     * @return void
     */
    private function assertSaysWhatTheSettingsSay(string $key, string $bodyKey, CustomerMessage $message): void
    {
        $subject = Settings::getString($key . '.subject');
        $this->assertStringStartsWith((string)strstr($subject, '{', true), $message->subject);
        $this->assertStringNotContainsString('{', $message->subject);

        $body = Settings::getString($key . '.' . $bodyKey);
        $this->assertStringStartsWith(substr($body, 0, 20), $message->body);
        $this->assertStringNotContainsString('{', $message->body);
    }

    /**
     * Every option is still there.
     *
     * @return void
     * @link \Bookkeeping\Command\ProcessDebtorsCommand::buildOptionParser()
     */
    public function testBuildOptionParser(): void
    {
        $this->exec('process_debtors --help');

        $this->assertExitSuccess();
        $this->assertOutputContains('--only_notify');
        $this->assertOutputContains('--only_block');
        $this->assertOutputContains('--blocking_update');
    }
}
