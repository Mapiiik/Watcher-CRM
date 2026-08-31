<?php
declare(strict_types=1);

namespace App\Test\TestCase\Command;

use App\Command\ProcessUnsignedContractsCommand;
use App\Model\Enum\CustomerMessageDeliveryStatus;
use App\Model\Enum\CustomerMessageType;
use App\Model\Enum\UnsignedDeadlineAnchor;
use App\Model\Table\ContractVersionsTable;
use App\Model\Table\CustomerMessagesTable;
use App\Model\Table\EmailsTable;
use App\Model\Table\PhonesTable;
use Cake\Cache\Cache;
use Cake\Chronos\Chronos;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;
use Settings\Utility\Settings;

/**
 * App\Command\ProcessUnsignedContractsCommand Test Case
 *
 * A service is running on paperwork nobody signed, and after a while somebody has to say so
 * to the customer. What these cases pin down is which day that happens on and who hears
 * about it - the two things a wrong answer costs somebody a letter they should not have had,
 * or a letter they should have had and did not.
 */
#[UsesClass(ProcessUnsignedContractsCommand::class)]
class ProcessUnsignedContractsCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;
    use LocatorAwareTrait;

    /**
     * The fixture contract. It went in on 2022-11-28, so only the version's own wait is ever
     * in question here.
     */
    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    /**
     * The day the run happens, and the day a version has to take effect on for its wait to
     * run out exactly then: ten days later.
     */
    private const TODAY = '2026-06-01';

    private const DUE_TODAY = '2026-05-22';

    /**
     * And the day it has to take effect on for the disconnection wait to run out today:
     * twenty days later.
     */
    private const BLOCK_DUE_TODAY = '2026-05-12';

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
        'app.ContractStates',
        'app.ServiceTypes',
        'app.Contracts',
        'app.ContractVersions',
        'app.Emails',
        'app.Phones',
        'app.CustomerMessages',
        'plugin.Settings.Settings',
    ];

    private ContractVersionsTable $ContractVersions;

    private CustomerMessagesTable $CustomerMessages;

    /**
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->ContractVersions = $this->getTableLocator()
            ->get('ContractVersions', ['className' => ContractVersionsTable::class]);
        $this->CustomerMessages = $this->getTableLocator()
            ->get('CustomerMessages', ['className' => CustomerMessagesTable::class]);

        $this->ContractVersions->deleteAll(['1 = 1']);
        $this->CustomerMessages->deleteAll(['1 = 1']);
        $this->getTableLocator()->get('Emails', ['className' => EmailsTable::class])->deleteAll(['1 = 1']);
        $this->getTableLocator()->get('Phones', ['className' => PhonesTable::class])->deleteAll(['1 = 1']);

        Cache::clear('default');
        Chronos::setTestNow(new Chronos(self::TODAY . ' 09:00:00'));

        // Said rather than inherited, so a case answers for itself whatever the settings file
        // says today and whatever ran before it.
        Settings::set('core.contracts.unsigned.consider_from', '2020-01-01');
        Settings::set(UnsignedDeadlineAnchor::SETTINGS_PATH, UnsignedDeadlineAnchor::Installation->value);
        Settings::set('core.contracts.unsigned.notifications.after_installation_days', 5);
        Settings::set('core.contracts.unsigned.notifications.after_valid_from_days', 10);
        Settings::set('core.contracts.unsigned.notifications.reminder_days', [0]);
        Settings::set('core.contracts.unsigned.notifications.remind_daily_after', false);
        Settings::set('core.contracts.unsigned.notifications.channels.email.enabled', true);
        Settings::set('core.contracts.unsigned.notifications.channels.sms.enabled', true);
        Settings::set('core.contracts.unsigned.notifications.types.notify.enabled', true);
        Settings::set('core.contracts.unsigned.notifications.types.block.enabled', true);
        Settings::set('core.contracts.unsigned.blocking.after_installation_days', 10);
        Settings::set('core.contracts.unsigned.blocking.after_valid_from_days', 20);
        Settings::set('core.contracts.unsigned.notifications.enabled', true);
    }

    /**
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        Chronos::setTestNow(Chronos::now());
        Cache::clear('default');

        parent::tearDown();
    }

    /**
     * The options a cron line names the command by. Renaming one of these quietly turns a
     * scheduled reminder into nothing at all.
     *
     * @return void
     */
    public function testTheCommandIsNamedAndTakesItsOptions(): void
    {
        $this->assertSame('process_unsigned_contracts', ProcessUnsignedContractsCommand::defaultName());

        $this->exec('process_unsigned_contracts --help');

        $this->assertExitSuccess();
        $this->assertOutputContains('--skip_emails');
        $this->assertOutputContains('--skip_sms');
    }

    /**
     * The switch is the whole safety of this. Off, nothing reaches anybody however overdue.
     *
     * @return void
     * @link \App\Command\ProcessUnsignedContractsCommand::execute()
     */
    public function testSwitchedOffItWritesToNobody(): void
    {
        Settings::set('core.contracts.unsigned.notifications.enabled', false);
        $this->emailFor(self::CUSTOMER_ID);
        $this->agreed(self::DUE_TODAY);

        $this->exec('process_unsigned_contracts');

        $this->assertExitSuccess();
        $this->assertSame(0, $this->CustomerMessages->find()->count());
    }

    /**
     * @return void
     * @link \App\Command\ProcessUnsignedContractsCommand::generateEmail()
     */
    public function testAVersionDueTodayPutsALetterInTheOutbox(): void
    {
        $this->emailFor(self::CUSTOMER_ID);
        $this->agreed(self::DUE_TODAY);

        $this->exec('process_unsigned_contracts');

        $this->assertExitSuccess();

        /** @var \App\Model\Entity\CustomerMessage $message */
        $message = $this->CustomerMessages->find()->firstOrFail();

        $this->assertSame(CustomerMessageType::EmailContracts, $message->type);
        $this->assertSame(['paperwork@example.com'], $message->recipients);
        $this->assertStringContainsString('in effect since', (string)$message->body);
    }

    /**
     * The message is written, not sent. What puts it on the wire is process_emails, so that
     * a transport that is down loses nothing.
     *
     * @return void
     * @link \App\Command\ProcessUnsignedContractsCommand::generate()
     */
    public function testTheLetterIsLeftForTheSenderToDeliver(): void
    {
        $this->emailFor(self::CUSTOMER_ID);
        $this->agreed(self::DUE_TODAY);

        $this->exec('process_unsigned_contracts');

        /** @var \App\Model\Entity\CustomerMessage $message */
        $message = $this->CustomerMessages->find()->firstOrFail();

        $this->assertSame(CustomerMessageDeliveryStatus::Pending, $message->delivery_status);
        $this->assertNull($message->processed);
    }

    /**
     * A day either side of the one the wait runs out on is silence. This is the whole of the
     * repeat-suppression: without it one reminder would go out again every day after.
     *
     * @return void
     * @link \App\Contracts\Unsigned\UnsignedPaperwork::findBecomingDueOn()
     */
    public function testADayEitherSideOfTheWaitIsSilent(): void
    {
        $this->emailFor(self::CUSTOMER_ID);
        $this->agreed('2026-05-21');

        $this->exec('process_unsigned_contracts');

        $this->assertSame(0, $this->CustomerMessages->find()->count(), 'Its wait ran out yesterday.');

        $this->ContractVersions->deleteAll(['1 = 1']);
        $this->agreed('2026-05-23');

        $this->exec('process_unsigned_contracts');

        $this->assertSame(0, $this->CustomerMessages->find()->count(), 'And this one runs out tomorrow.');
    }

    /**
     * Switched on, the days that are named stop being the end of it.
     *
     * @return void
     * @link \App\Contracts\Unsigned\UnsignedPaperwork::findDueBefore()
     */
    public function testDailyAfterKeepsAskingOnceTheNamedDaysAreUsedUp(): void
    {
        $this->emailFor(self::CUSTOMER_ID);
        // the asking wait ran out a week ago and the disconnection one has not come yet, so
        // no named day of either falls on today
        $this->agreed('2026-05-15');

        $this->exec('process_unsigned_contracts');

        $this->assertSame(0, $this->CustomerMessages->find()->count());

        Settings::set('core.contracts.unsigned.notifications.remind_daily_after', true);

        $this->exec('process_unsigned_contracts');

        $this->assertSame(1, $this->CustomerMessages->find()->count());
    }

    /**
     * One customer holding three unsigned versions is one letter listing three, not three
     * letters landing at once.
     *
     * @return void
     * @link \App\Command\ProcessUnsignedContractsCommand::dueToday()
     */
    public function testAllOfACustomersVersionsGoInOneLetter(): void
    {
        $this->emailFor(self::CUSTOMER_ID);
        $this->agreed(self::DUE_TODAY);
        $this->agreed(self::DUE_TODAY);

        $this->exec('process_unsigned_contracts');

        $this->assertSame(1, $this->CustomerMessages->find()->count());
    }

    /**
     * SMS is the fallback, not the second copy: a letter says more than a hundred and sixty
     * characters can, so it only goes where there is no address to write to.
     *
     * @return void
     * @link \App\Command\ProcessUnsignedContractsCommand::writeToCustomer()
     */
    public function testWithNoAddressItSendsAnSms(): void
    {
        $this->phoneFor(self::CUSTOMER_ID);
        $this->agreed(self::DUE_TODAY);

        $this->exec('process_unsigned_contracts');

        /** @var \App\Model\Entity\CustomerMessage $message */
        $message = $this->CustomerMessages->find()->firstOrFail();

        $this->assertSame(CustomerMessageType::Sms, $message->type);
        $this->assertSame(['+420605123456'], $message->recipients);
    }

    /**
     * @return void
     * @link \App\Command\ProcessUnsignedContractsCommand::writeToCustomer()
     */
    public function testWithAnAddressTheSmsIsNotSentAsWell(): void
    {
        $this->emailFor(self::CUSTOMER_ID);
        $this->phoneFor(self::CUSTOMER_ID);
        $this->agreed(self::DUE_TODAY);

        $this->exec('process_unsigned_contracts');

        $this->assertSame(1, $this->CustomerMessages->find()->count());

        /** @var \App\Model\Entity\CustomerMessage $message */
        $message = $this->CustomerMessages->find()->firstOrFail();

        $this->assertSame(CustomerMessageType::EmailContracts, $message->type);
    }

    /**
     * Nobody to write to is said out loud rather than passed over. It is a finding about the
     * customer's file, not a quiet success.
     *
     * @return void
     * @link \App\Command\ProcessUnsignedContractsCommand::writeToCustomer()
     */
    public function testACustomerWithNoContactIsReported(): void
    {
        $this->agreed(self::DUE_TODAY);

        $this->exec('process_unsigned_contracts');

        $this->assertExitSuccess();
        $this->assertSame(0, $this->CustomerMessages->find()->count());
        $this->assertErrorContains('nobody to tell');
    }

    /**
     * The command records nothing on the version. That column is the office's record that
     * the papers went out, and a reminder writing to it would push its own deadline forward
     * under the sending anchor - chasing for ever and arriving at nothing.
     *
     * @return void
     * @link \App\Command\ProcessUnsignedContractsCommand::execute()
     */
    public function testItDoesNotStampTheSendingRecord(): void
    {
        $this->emailFor(self::CUSTOMER_ID);
        $this->agreed(self::DUE_TODAY);

        $this->exec('process_unsigned_contracts');

        /** @var \App\Model\Entity\ContractVersion $version */
        $version = $this->ContractVersions->find()->firstOrFail();

        $this->assertNull($version->sent_date);
    }

    /**
     * @return void
     * @link \App\Command\ProcessUnsignedContractsCommand::writeToCustomer()
     */
    public function testSkippingBothChannelsWritesNothing(): void
    {
        $this->emailFor(self::CUSTOMER_ID);
        $this->agreed(self::DUE_TODAY);

        $this->exec('process_unsigned_contracts --skip_emails --skip_sms');

        $this->assertExitSuccess();
        $this->assertSame(0, $this->CustomerMessages->find()->count());
    }

    /**
     * Reaching the disconnection deadline is a different letter, in sterner words.
     *
     * @return void
     * @link \App\Command\ProcessUnsignedContractsCommand::dueToday()
     */
    public function testReachingTheDisconnectionDeadlineSendsTheSternerLetter(): void
    {
        $this->emailFor(self::CUSTOMER_ID);
        $this->agreed(self::BLOCK_DUE_TODAY);

        $this->exec('process_unsigned_contracts');

        /** @var \App\Model\Entity\CustomerMessage $message */
        $message = $this->CustomerMessages->find()->firstOrFail();

        $this->assertStringContainsString('omezena', (string)$message->body);
        $this->assertStringNotContainsString('Prosíme Vás o její podepsání', (string)$message->body);
    }

    /**
     * Switched off, the warning does not go out even though the day has come. Which is what
     * an installation that has not switched the blocking on needs, since the warning would
     * otherwise promise a disconnection that is not coming.
     *
     * @return void
     * @link \App\Command\ProcessUnsignedContractsCommand::isTypeEnabled()
     */
    public function testTheWarningCanBeSwitchedOffOnItsOwn(): void
    {
        Settings::set('core.contracts.unsigned.notifications.types.block.enabled', false);
        $this->emailFor(self::CUSTOMER_ID);
        $this->agreed(self::BLOCK_DUE_TODAY);

        $this->exec('process_unsigned_contracts');

        $this->assertSame(0, $this->CustomerMessages->find()->count());
    }

    /**
     * And the asking can be switched off while the warning stays on.
     *
     * @return void
     * @link \App\Command\ProcessUnsignedContractsCommand::isTypeEnabled()
     */
    public function testTheAskingCanBeSwitchedOffOnItsOwn(): void
    {
        Settings::set('core.contracts.unsigned.notifications.types.notify.enabled', false);
        $this->emailFor(self::CUSTOMER_ID);
        $this->agreed(self::DUE_TODAY);

        $this->exec('process_unsigned_contracts');

        $this->assertSame(0, $this->CustomerMessages->find()->count());
    }

    /**
     * Where both waits fall on the same day, only the sterner letter goes. Asking for the
     * paper and saying the service is about to go off contradict each other, and the second
     * is the one that is still true.
     *
     * @return void
     * @link \App\Command\ProcessUnsignedContractsCommand::dueToday()
     */
    public function testWhereBothDaysFallTogetherOnlyTheWarningGoes(): void
    {
        // both waits set to the same length, so one version reaches both today
        Settings::set('core.contracts.unsigned.blocking.after_valid_from_days', 10);
        $this->emailFor(self::CUSTOMER_ID);
        $this->agreed(self::DUE_TODAY);

        $this->exec('process_unsigned_contracts');

        $this->assertSame(1, $this->CustomerMessages->find()->count());

        /** @var \App\Model\Entity\CustomerMessage $message */
        $message = $this->CustomerMessages->find()->firstOrFail();

        $this->assertStringContainsString('omezena', (string)$message->body);
    }

    /**
     * Put a version of the fixture contract on file, with nothing signed behind it.
     *
     * @param string $valid_from The day the version takes effect.
     * @return void
     */
    private function agreed(string $valid_from): void
    {
        $this->ContractVersions->saveOrFail($this->ContractVersions->newEntity([
            'contract_id' => self::CONTRACT_ID,
            'valid_from' => $valid_from,
            'conclusion_date' => null,
            'number_of_amendments' => 0,
            'obligations_settled' => false,
        ]));
    }

    /**
     * @param string $customer_id Who to give an address to.
     * @return void
     */
    private function emailFor(string $customer_id): void
    {
        $emails = $this->getTableLocator()->get('Emails', ['className' => EmailsTable::class]);

        $emails->saveOrFail($emails->newEntity([
            'customer_id' => $customer_id,
            'email' => 'paperwork@example.com',
        ]));
    }

    /**
     * @param string $customer_id Who to give a number to.
     * @return void
     */
    private function phoneFor(string $customer_id): void
    {
        $phones = $this->getTableLocator()->get('Phones', ['className' => PhonesTable::class]);

        $phones->saveOrFail($phones->newEntity([
            'customer_id' => $customer_id,
            'phone' => '+420605123456',
        ]));
    }
}
