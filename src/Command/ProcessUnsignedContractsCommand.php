<?php
declare(strict_types=1);

namespace App\Command;

use App\Contracts\Unsigned\UnsignedPaperwork;
use App\Contracts\Unsigned\UnsignedService;
use App\Contracts\Unsigned\UnsignedWaits;
use App\Model\Entity\Customer;
use App\Model\Entity\CustomerMessage;
use App\Model\Enum\CustomerMessageBodyFormat;
use App\Model\Enum\CustomerMessageDeliveryStatus;
use App\Model\Enum\CustomerMessageDirection;
use App\Model\Enum\CustomerMessageType;
use App\Model\Table\ContractVersionsTable;
use App\Model\Table\CustomerMessagesTable;
use App\Service\ErrorReport;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\I18n\Date;
use Cake\Log\Log;
use Override;
use Settings\Utility\Settings;
use Throwable;

/**
 * Writes to the customers whose running services have no signed paperwork behind them.
 *
 * Like the debtor run, this command sends nothing. It writes rows into `customer_messages`
 * for `process_emails` and `process_sms` to deliver, so that what went out is on the
 * customer's file whether or not the transport was reachable at the time.
 *
 * Which versions are due on a given day is not this command's judgement - it asks
 * {@see \App\Contracts\Unsigned\UnsignedPaperwork}, the same reading the overview lists and
 * the nightly blocking acts on.
 *
 * Two letters, because the two waits mean two different things. Reaching the reminder
 * deadline asks the customer for the paper. Reaching the blocking one tells them what
 * happens now that it has not come. A version that reaches its disconnection day today gets
 * only the second: the two contradict each other in the same post, and the sterner of them
 * is the one that is still true.
 *
 * Both are switched separately and both ship on, because cutting somebody off without
 * telling them is the worse of the two mistakes available here. The other one is real
 * though: with the blocking switched off, the second letter warns of a disconnection no run
 * will carry out, and an installation that chases paperwork without ever cutting anybody off
 * wants that letter switched off.
 *
 * Nothing is remembered between runs. A version is written about on the days its wait falls
 * on, worked out from the dates each time, which is what keeps one reminder from being sent
 * on every day that follows it. Two things follow from having no memory, and both are shared
 * with the debtor run this is modelled on:
 *
 *   A night the cron does not run is a reminder nobody ever sends. Where that matters,
 *   `notifications.daily_after` turns the named days into a floor and the chasing carries on
 *   itself until the paper arrives.
 *
 *   Running it twice on the same day writes the same letter twice. It is meant for a cron
 *   line, once a day; a second run by hand is a second letter.
 *
 * It does NOT touch `sent_date` on the version. That column is the office's record that the
 * papers went out, and a reminder writing to it would push its own deadline forward under the
 * sending anchor - chasing for ever and never arriving at anything.
 */
class ProcessUnsignedContractsCommand extends Command
{
    /**
     * Where the settings say who is written to, when, and in what words.
     */
    private const SETTINGS_PATH = 'core.contracts.paperwork.unsigned';

    /**
     * The name of this command.
     *
     * @var string
     */
    protected string $name = 'process_unsigned_contracts';

    /**
     * @return string
     */
    public static function defaultName(): string
    {
        return 'process_unsigned_contracts';
    }

    /**
     * @return string
     */
    public static function getDescription(): string
    {
        return 'Write to the customers whose running services have no signed paperwork.';
    }

    /**
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined.
     * @return \Cake\Console\ConsoleOptionParser
     */
    #[Override]
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);

        $parser->addOption('skip_emails', [
            'help' => __('Do not write any e-mails, the operation will be skipped.'),
            'boolean' => true,
        ]);

        $parser->addOption('skip_sms', [
            'help' => __('Do not write any SMS, the operation will be skipped.'),
            'boolean' => true,
        ]);

        return $parser;
    }

    /**
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io.
     * @return int|null|void The exit code or null for success
     */
    #[Override]
    public function execute(Arguments $args, ConsoleIo $io)
    {
        try {
            if (!(bool)Settings::get(self::SETTINGS_PATH . '.notifications.enabled', false)) {
                $io->warning(__('Notifications about unsigned contracts are switched off in the settings.'));

                return static::CODE_SUCCESS;
            }

            $due = $this->dueToday();

            if ($due === []) {
                $io->info(__('Nobody is due to hear about unsigned documents today.'));

                return static::CODE_SUCCESS;
            }

            foreach ($due as $by_kind) {
                foreach ($by_kind as $kind => $services) {
                    $this->writeToCustomer($kind, $services, $args, $io);
                }
            }

            $io->info(__('Done'));

            return static::CODE_SUCCESS;
        } catch (Throwable $e) {
            Log::error('Error while processing unsigned contracts: ' . $e->getMessage());

            $io->error(__('Error while processing unsigned contracts: {0}', $e->getMessage()));

            ErrorReport::send(
                __('Processing of unsigned contracts failed'),
                // one literal rather than pieces joined together: the extractor reads what is
                // written here, and a joined string leaves it with half a msgid that nothing
                // will ever match at run time
                __("Processing of unsigned contracts failed.\n\nError: {0}", [$e->getMessage()]),
            );

            return static::CODE_ERROR;
        }
    }

    /**
     * The services to write about today, under the customer who holds them and the letter
     * they are due.
     *
     * One customer with three unsigned services is one letter listing three, not three
     * letters. Which is also why they are collected before anything is written.
     *
     * A version that has reached the day of its disconnection today is left out of the
     * asking letter, whatever the reminder days would otherwise say: the two would be
     * contradicting each other in the same post.
     *
     * @return array<string, array<string, list<\App\Contracts\Unsigned\UnsignedService>>>
     */
    private function dueToday(): array
    {
        $today = Date::today();

        $blocking = $this->gather(UnsignedWaits::beforeBlocking(), $today);
        $notifying = $this->gather(UnsignedWaits::beforeNotifying(), $today);

        $due = [];

        foreach (['block' => $blocking, 'notify' => $notifying] as $kind => $services) {
            if (!$this->isTypeEnabled($kind)) {
                continue;
            }

            foreach ($services as $key => $service) {
                if ($kind === 'notify' && isset($blocking[$key])) {
                    continue;
                }

                $customer_id = $service->customer()?->id;

                if ($customer_id === null) {
                    continue;
                }

                $due[$customer_id][$kind][] = $service;
            }
        }

        return $due;
    }

    /**
     * Every unsigned service whose wait of that length ran out on one of the days written about
     * today, of both kinds - a version that came back unsigned, and a service no version covers.
     *
     * @param \App\Contracts\Unsigned\UnsignedWaits $waits How long the service may go unsigned first.
     * @param \Cake\I18n\Date $today The day the run is happening on.
     * @return array<string, \App\Contracts\Unsigned\UnsignedService> Keyed, so that the named days
     *   and the daily sweep cannot hand back the same one twice.
     */
    private function gather(UnsignedWaits $waits, Date $today): array
    {
        $paperwork = new UnsignedPaperwork($this->fetchTable(ContractVersionsTable::class));

        /** @var list<int> $reminder_days */
        $reminder_days = array_map(intval(...), (array)Settings::get(
            self::SETTINGS_PATH . '.notifications.days',
            [0],
        ));

        $found = [];

        // The named days, each asked for as the one day a wait ran out on. Asking for the day
        // rather than for everything since it is what stops one letter from going out again on
        // every day that follows, without anything having to be kept.
        foreach ($reminder_days as $days) {
            foreach ($paperwork->becomingDueOn($waits, $today->subDays(max(0, $days))) as $service) {
                $found[$service->key()] = $service;
            }
        }

        // And, where the office would rather keep asking than let it go quiet, everything
        // that ran out before the last of those days. The boundary is strict, so this cannot
        // pick up a service one of the named days has already taken.
        if ((bool)Settings::get(self::SETTINGS_PATH . '.notifications.daily_after', false)) {
            $since = $today->subDays(max($reminder_days === [] ? [0] : $reminder_days));

            foreach ($paperwork->dueBefore($waits, $since) as $service) {
                $found[$service->key()] = $service;
            }
        }

        return $found;
    }

    /**
     * Write to one customer about every unsigned version they hold.
     *
     * E-mail where there is an address, SMS only where there is not. A letter says more than
     * a hundred and sixty characters can, and sending both would have the customer hear the
     * same thing twice.
     *
     * @param string $kind Which letter is due ("notify", "block").
     * @param list<\App\Contracts\Unsigned\UnsignedService> $services What to write about.
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io.
     * @return void
     */
    private function writeToCustomer(string $kind, array $services, Arguments $args, ConsoleIo $io): void
    {
        $customer = $services[0]->customer();

        if ($customer === null) {
            return;
        }

        $emails_available = count($customer->emails) > 0;
        $phones_available = count($customer->phones) > 0;

        if ($emails_available && !$args->getOption('skip_emails') && $this->isEnabled('email')) {
            $message = $this->generateEmail($kind, $customer, $services);
            $io->info(__(
                '{0} email has been generated for customer {1}, recipients: {2}',
                $kind,
                $customer->number,
                implode(', ', $message->recipients),
            ));

            return;
        }

        if (!$emails_available && $phones_available && !$args->getOption('skip_sms') && $this->isEnabled('sms')) {
            $message = $this->generateSms($kind, $customer, $services);
            $io->info(__(
                '{0} SMS has been generated for customer {1}, recipients: {2}',
                $kind,
                $customer->number,
                implode(', ', $message->recipients),
            ));

            return;
        }

        $io->warning(__(
            'Customer {0} has unsigned documents but nobody to tell about it.',
            $customer->number,
        ));
    }

    /**
     * Whether a channel may be used at all.
     *
     * @param string $channel Delivery channel identifier ("email", "sms").
     * @return bool
     */
    private function isEnabled(string $channel): bool
    {
        return (bool)Settings::get(
            sprintf('%s.notifications.channels.%s.enabled', self::SETTINGS_PATH, $channel),
            false,
        );
    }

    /**
     * Whether a kind of letter may go out at all.
     *
     * Warning of a disconnection is switched apart from asking for the paper, so that an
     * installation which chases paperwork without ever cutting anybody off can leave off the
     * letter that threatens one. Both are on to begin with: the worse mistake is the service
     * going quiet with nothing said about it.
     *
     * @param string $kind Letter identifier ("notify", "block").
     * @return bool
     */
    private function isTypeEnabled(string $kind): bool
    {
        return (bool)Settings::get(
            sprintf('%s.notifications.types.%s.enabled', self::SETTINGS_PATH, $kind),
            false,
        );
    }

    /**
     * @param string $kind Which letter is due ("notify", "block").
     * @param \App\Model\Entity\Customer $customer Who to write to.
     * @param list<\App\Contracts\Unsigned\UnsignedService> $services What to write about.
     * @return \App\Model\Entity\CustomerMessage
     */
    private function generateEmail(string $kind, Customer $customer, array $services): CustomerMessage
    {
        return $this->generate(
            $customer,
            $services,
            CustomerMessageType::EmailContracts,
            $customer->emails,
            Settings::getString(sprintf('%s.emails.%s.subject', self::SETTINGS_PATH, $kind)),
            Settings::getString(sprintf('%s.emails.%s.body_text', self::SETTINGS_PATH, $kind)),
        );
    }

    /**
     * @param string $kind Which letter is due ("notify", "block").
     * @param \App\Model\Entity\Customer $customer Who to write to.
     * @param list<\App\Contracts\Unsigned\UnsignedService> $services What to write about.
     * @return \App\Model\Entity\CustomerMessage
     */
    private function generateSms(string $kind, Customer $customer, array $services): CustomerMessage
    {
        return $this->generate(
            $customer,
            $services,
            CustomerMessageType::Sms,
            $customer->phones,
            Settings::getString(sprintf('%s.sms.%s.subject', self::SETTINGS_PATH, $kind)),
            Settings::getString(sprintf('%s.sms.%s.body', self::SETTINGS_PATH, $kind)),
        );
    }

    /**
     * Put one message in the outbox.
     *
     * @param \App\Model\Entity\Customer $customer Who to write to.
     * @param list<\App\Contracts\Unsigned\UnsignedService> $services What to write about.
     * @param \App\Model\Enum\CustomerMessageType $type Which channel, and so which mailer.
     * @param array<\App\Model\Entity\Email>|array<\App\Model\Entity\Phone> $recipients Who to write to.
     * @param string $subject_template The subject, before the placeholders are filled in.
     * @param string $body_template The body, before the placeholders are filled in.
     * @return \App\Model\Entity\CustomerMessage
     */
    private function generate(
        Customer $customer,
        array $services,
        CustomerMessageType $type,
        array $recipients,
        string $subject_template,
        string $body_template,
    ): CustomerMessage {
        $replacements = [
            '{date}' => Date::now(),
            '{customer_number}' => $customer->number,
            '{contracts_table}' => $this->getContractsTable($services),

            '{company_name}' => Settings::getString('core.company.name'),
            '{company_address_line_1}' => Settings::getString('core.company.address_line_1'),
            '{company_address_line_2}' => Settings::getString('core.company.address_line_2'),
            '{identity_number}' => Settings::getString('core.company.identity_number'),
            '{vat_number}' => Settings::getString('core.company.vat_number'),

            '{company_contracts_email}' => Settings::getString('core.company.contracts.email'),
            '{company_contracts_phone}' => Settings::getString('core.company.contracts.phone'),
            '{user_portal_url}' => Settings::getString('core.company.user_portal_url'),
        ];

        $customerMessages = $this->fetchTable(CustomerMessagesTable::class);

        $message = $customerMessages->newEmptyEntity();

        $message->type = $type;
        $message->direction = CustomerMessageDirection::Outgoing;
        $message->body_format = CustomerMessageBodyFormat::Plaintext;
        $message->delivery_status = CustomerMessageDeliveryStatus::Pending;

        $message->customer_id = $customer->id;
        $message->recipients = $recipients;
        $message->subject = strtr($subject_template, $replacements);
        $message->body = strtr($body_template, $replacements);

        return $customerMessages->saveOrFail($message);
    }

    /**
     * The contracts the letter is about, one to a line.
     *
     * Facts only - which contract, and since when it has been running on nothing. What
     * follows from that is the office's to word, because whether anything follows at all is
     * a matter of whether the blocking is switched on.
     *
     * @param list<\App\Contracts\Unsigned\UnsignedService> $services What to write about.
     * @return string
     */
    private function getContractsTable(array $services): string
    {
        $lines = array_map(
            // A version says since when it has been in effect. A service nobody has drawn one for
            // has no such day to name, so it says since when it has been provided instead - which
            // is what the customer would recognise anyway.
            fn(UnsignedService $service): string => sprintf(
                '%s (%s %s)',
                $service->contract->name,
                $service->version !== null ? __('in effect since') : __('provided since'),
                $service->running_since,
            ),
            $services,
        );

        return implode(PHP_EOL, $lines) . PHP_EOL;
    }
}
