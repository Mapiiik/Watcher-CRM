<?php
declare(strict_types=1);

namespace Bookkeeping\Command;

use App\Command\Traits\MessageHandlerTrait;
use App\Model\Entity\CustomerMessage;
use App\Model\Enum\CustomerMessageBodyFormat;
use App\Model\Enum\CustomerMessageDeliveryStatus;
use App\Model\Enum\CustomerMessageDirection;
use App\Model\Enum\CustomerMessageType;
use App\Model\Table\CustomerMessagesTable;
use App\Service\ErrorReport;
use Bookkeeping\Debtors\Debtor;
use Bookkeeping\Debtors\DebtorsProcessor;
use Bookkeeping\Debtors\InvoicePdfUnavailableException;
use Bookkeeping\Service\BookkeepingService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\I18n\Date;
use Cake\I18n\Number;
use Cake\Log\Log;
use Closure;
use Override;
use Settings\Utility\Settings;
use Throwable;

/**
 * ProcessDebtors command.
 *
 * Processes customers with overdue invoices.
 *
 * This command evaluates overdue debtors and performs the following actions
 * based on configured rules and provided options:
 * - generates notification messages (email or SMS) for overdue invoices
 * - generates blocking notifications for customers exceeding allowed limits
 * - optionally updates service blocking in external systems
 *
 * The command creates customer message records (emails / SMS) in the database
 * but does NOT directly send them. Message delivery is handled asynchronously
 * by dedicated workers or background processes.
 *
 * This command acts as an orchestration layer and does NOT:
 * - modify invoice amounts or accounting data
 * - perform payment processing
 * - directly block or unblock services without explicit request
 *
 * It is intended to be run periodically (e.g. via cron)
 * to enforce payment discipline and inform customers about overdue debts.
 */
class ProcessDebtorsCommand extends Command
{
    use MessageHandlerTrait;

    private BookkeepingService $bookkeeping;

    /**
     * Who got no message and why, to be reported once at the end.
     *
     * @var list<string>
     */
    private array $passedOver = [];

    /**
     * The name of this command.
     */
    protected string $name = 'process_debtors';

    /**
     * Get the default command name.
     *
     * @return string
     */
    public static function defaultName(): string
    {
        return 'process_debtors';
    }

    /**
     * Get the command description.
     *
     * @return string
     */
    public static function getDescription(): string
    {
        return 'Process overdue debtors and generate notifications or blocking actions.';
    }

    /**
     * Initializes bookkeeping service used for invoice-related operations.
     */
    public function __construct()
    {
        $this->bookkeeping = new BookkeepingService();
    }

    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/5/en/console-commands/commands.html#defining-arguments-and-options
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    #[Override]
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);

        $parser->addOption('only_notify', [
            'help' => __d(
                'bookkeeping',
                'Send only a notification of an overdue claims (within the allowed payment delay).',
            ),
            'boolean' => true,
        ]);

        $parser->addOption('only_block', [
            'help' => __d(
                'bookkeeping',
                'Send only a notification of blocking (or continuing debt for services no longer active)'
                . ' for overdue claims (after an allowed delay in payment).',
            ),
            'boolean' => true,
        ]);

        $parser->addOption('blocking_update', [
            'help' => __d(
                'bookkeeping',
                'Automatically update the blocking of debtors in systems (routers, firewalls, IPTV, ...).',
            ),
            'boolean' => true,
        ]);

        $parser->addOption('skip_emails', [
            'help' => __d('bookkeeping', 'Do not send emails, the operation will be skipped.'),
            'boolean' => true,
        ]);

        $parser->addOption('skip_sms', [
            'help' => __d('bookkeeping', 'Do not send SMS, the operation will be skipped.'),
            'boolean' => true,
        ]);

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null|void The exit code or null for success
     */
    #[Override]
    public function execute(Arguments $args, ConsoleIo $io)
    {
        try {
            if (!(bool)Settings::get('bookkeeping.debtors.notifications.enabled', false)) {
                $io->warning(
                    __d(
                        'bookkeeping',
                        'Debtor notifications are globally disabled by settings.',
                    ),
                );
            }

            $today = Date::now();

            /** @var array<int> $notifyDays */
            $notifyDays = (array)Settings::get('bookkeeping.debtors.notifications.days', []);

            $debtorsProcessor = new DebtorsProcessor(
                allowed_payment_delay: (int)Settings::get('bookkeeping.debtors.thresholds.allowed_payment_delay'),
                allowed_total_overdue_debt: (float)Settings::get(
                    'bookkeeping.debtors.thresholds.allowed_total_overdue_debt',
                ),
            );

            // automatically update the blocking of debtors in systems, if requested
            if ($args->getOption('blocking_update')) {
                $debtorsProcessor->blockingUpdate();

                $this->handleMessages($debtorsProcessor->getMessages(), $io);
            }

            // get debtors to notify
            $debtorsToNotify = $args->getOption('only_block') ?
                []
                :
                $debtorsProcessor
                    ->getOverdueDebtors()
                    ->filter(
                        fn(Debtor $debtor): bool => $this->shouldNotifyDebtor(
                            debtor: $debtor,
                            notifyDays: $notifyDays,
                            today: $today,
                        ),
                    );

            // get debtors to block (or continuing debt for services no longer active)
            $debtorsToBlock = $args->getOption('only_notify') ?
                []
                :
                $debtorsProcessor->getFilteredOverdueDebtors();

            /** @var \Bookkeeping\Debtors\Debtor $debtor */
            foreach ($debtorsToNotify as $debtor) {
                $this->writingTo($debtor, $this->remind(...), $args, $io);
            }

            /** @var \Bookkeeping\Debtors\Debtor $debtor */
            foreach ($debtorsToBlock as $debtor) {
                $this->writingTo($debtor, $this->tellTheBlocked(...), $args, $io);
            }

            $this->reportWhoWasPassedOver($io);

            $io->info(__d('bookkeeping', 'Done'));

            return static::CODE_SUCCESS;
        } catch (Throwable $e) {
            Log::error('Error during debtor processing: ' . $e->getMessage());

            $io->error(__d(
                'bookkeeping',
                'Error during debtor processing: {0}',
                $e->getMessage(),
            ));

            ErrorReport::send(
                __d('bookkeeping', 'Debtor processing failed'),
                __d(
                    'bookkeeping',
                    'Debtor processing failed.' . PHP_EOL . PHP_EOL
                    . 'Error: {0}',
                    [$e->getMessage()],
                ),
            );

            return static::CODE_ERROR;
        }
    }

    /**
     * Reminds a debtor by the channel they can be reached on.
     *
     * @param \Bookkeeping\Debtors\Debtor $debtor Who is being written to.
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return void
     */
    private function remind(Debtor $debtor, Arguments $args, ConsoleIo $io): void
    {
        $customer = $debtor->getCustomer();
        $emails_available = (count($customer->billing_emails) > 0);
        $phones_available = (count($customer->billing_phones) > 0);

        // notify emails
        if (
            $emails_available
            && !$args->getOption('skip_emails')
            && static::isDebtorNotificationEnabled('notify', 'email')
        ) {
            $io->info(__d(
                'bookkeeping',
                'Notification email has been generated'
                    . ' for customer {customer_number}, recipients: {recipients}',
                $this->written($debtor, $this->generateEmail($debtor, 'notify', $customer->billing_emails)),
            ));
        }

        // notify SMS
        if (
            !$emails_available
            && $phones_available
            && !$args->getOption('skip_sms')
            && static::isDebtorNotificationEnabled('notify', 'sms')
        ) {
            $io->info(__d(
                'bookkeeping',
                'Notification SMS has been generated'
                    . ' for customer {customer_number}, recipients: {recipients}',
                $this->written($debtor, $this->generateSms($debtor, 'notify', $customer->billing_phones)),
            ));
        }
    }

    /**
     * Tells a debtor they have been blocked, or that their services are gone and the debt is not.
     *
     * @param \Bookkeeping\Debtors\Debtor $debtor Who is being written to.
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return void
     */
    private function tellTheBlocked(Debtor $debtor, Arguments $args, ConsoleIo $io): void
    {
        $customer = $debtor->getCustomer();
        $messageType = $customer->active_services ? 'block' : 'inactive';

        // block emails
        if (
            count($customer->emails) > 0
            && !$args->getOption('skip_emails')
            && static::isDebtorNotificationEnabled($messageType, 'email')
        ) {
            $said = $this->written($debtor, $this->generateEmail($debtor, $messageType, $customer->emails));

            $io->info($messageType === 'block'
                ? __d(
                    'bookkeeping',
                    'Blocking email has been generated'
                        . ' for customer {customer_number}, recipients: {recipients}',
                    $said,
                )
                : __d(
                    'bookkeeping',
                    'Notification email has been generated for inactive services'
                        . ' for customer {customer_number}, recipients: {recipients}',
                    $said,
                ));
        }

        // block SMS
        if (
            count($customer->phones) > 0
            && !$args->getOption('skip_sms')
            && static::isDebtorNotificationEnabled($messageType, 'sms')
        ) {
            $said = $this->written($debtor, $this->generateSms($debtor, $messageType, $customer->phones));

            $io->info($messageType === 'block'
                ? __d(
                    'bookkeeping',
                    'Blocking SMS has been generated'
                        . ' for customer {customer_number}, recipients: {recipients}',
                    $said,
                )
                : __d(
                    'bookkeeping',
                    'Notification SMS has been generated for inactive services'
                        . ' for customer {customer_number}, recipients: {recipients}',
                    $said,
                ));
        }
    }

    /**
     * Who a message went to, the way the output of the run says it.
     *
     * @param \Bookkeeping\Debtors\Debtor $debtor Who was written to.
     * @param \App\Model\Entity\CustomerMessage $message What was written.
     * @return array<string, string>
     */
    private function written(Debtor $debtor, CustomerMessage $message): array
    {
        return [
            'customer_number' => $debtor->getCustomer()->number,
            'recipients' => implode(', ', $message->recipients),
        ];
    }

    /**
     * Writes to a debtor, or passes over them where the PDF of an invoice cannot be had.
     *
     * A message goes out with its invoices, so without one of them it does not go out at all. The
     * rest of the run is not held up for it: the blocking has happened by now, and everybody after
     * this customer would be cut off without a word.
     *
     * @param \Bookkeeping\Debtors\Debtor $debtor Who is being written to.
     * @param \Closure $write What writes to them.
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return void
     */
    private function writingTo(Debtor $debtor, Closure $write, Arguments $args, ConsoleIo $io): void
    {
        try {
            $write($debtor, $args, $io);
        } catch (InvoicePdfUnavailableException $e) {
            $said = __d(
                'bookkeeping',
                'Customer {0}, invoice {1}: {2}',
                $debtor->getCustomer()->number,
                $e->invoice->number,
                $e->getMessage(),
            );

            Log::error($said);
            $io->error($said);

            $this->passedOver[] = $said;
        }
    }

    /**
     * Says once who got no message, which invoice it was over and what the accounting system said.
     *
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return void
     */
    private function reportWhoWasPassedOver(ConsoleIo $io): void
    {
        if ($this->passedOver === []) {
            return;
        }

        ErrorReport::send(
            __d('bookkeeping', 'Debtors left without a message'),
            __d('bookkeeping', 'These customers got no message, because the PDF of an invoice could not be had:')
                . PHP_EOL . PHP_EOL
                . implode(PHP_EOL, array_unique($this->passedOver)),
        );
    }

    /**
     * Determine whether debtor notification delivery is enabled.
     *
     * This method evaluates notification policy settings for debtors,
     * including the global notifications switch, the delivery channel,
     * and the specific notification type.
     *
     * Behaviour:
     * - If notifications are globally disabled, returns false.
     * - If the specified delivery channel is disabled, returns false.
     * - Otherwise, returns true only if the given notification type is enabled.
     *
     * Configuration keys:
     * - bookkeeping.debtors.notifications.enabled
     * - bookkeeping.debtors.notifications.channels.<channel>.enabled
     * - bookkeeping.debtors.notifications.types.<type>.enabled
     *
     * @param string $type Notification type identifier
     *                     (e.g. "notify", "block", "inactive").
     * @param string $channel Delivery channel identifier
     *                        (e.g. "email", "sms").
     * @return bool True if the notification is allowed to be generated
     *              for the given type and channel.
     */
    public static function isDebtorNotificationEnabled(
        string $type,
        string $channel,
    ): bool {
        if (!(bool)Settings::get('bookkeeping.debtors.notifications.enabled', false)) {
            return false;
        }

        if (!(bool)Settings::get(sprintf('bookkeeping.debtors.notifications.channels.%s.enabled', $channel), false)) {
            return false;
        }

        return (bool)Settings::get(sprintf('bookkeeping.debtors.notifications.types.%s.enabled', $type), false);
    }

    /**
     * Get Invoices Table
     */
    private function getInvoicesTable(Debtor $debtor): string
    {
        $separator = Settings::getString('bookkeeping.debtors.tables.invoices.separator');
        $footer = Settings::getString('bookkeeping.debtors.tables.invoices.footer');

        $text =
            sprintf('%-15s', Settings::getString('bookkeeping.debtors.tables.invoices.headers.number')) . "\t"
            . sprintf(
                '%-12s',
                Settings::getString('bookkeeping.debtors.tables.invoices.headers.variable_symbol'),
            ) . "\t"
            . sprintf('%-10s', Settings::getString('bookkeeping.debtors.tables.invoices.headers.creation_date')) . "\t"
            . sprintf('%-10s', Settings::getString('bookkeeping.debtors.tables.invoices.headers.due_date')) . "\t"
            . sprintf('%-12s', Settings::getString('bookkeeping.debtors.tables.invoices.headers.total')) . "\t"
            . sprintf('%-12s', Settings::getString('bookkeeping.debtors.tables.invoices.headers.debt'))
            . PHP_EOL;

        $text .= $separator . PHP_EOL;

        foreach ($debtor->getInvoices() as $invoice) {
            $text .=
                sprintf('%-15s', $invoice->number) . "\t"
                . sprintf('%-12s', $invoice->variable_symbol) . "\t"
                . sprintf('%-10s', $invoice->creation_date) . "\t"
                . sprintf('%-10s', $invoice->due_date) . "\t"
                . sprintf('%-12s', Number::currency($invoice->total->toFloat())) . "\t"
                . sprintf('%-12s', Number::currency($invoice->debt->toFloat()))
                . PHP_EOL;
        }

        $text .= $separator . PHP_EOL;

        $text .=
            sprintf('%-15s', Settings::getString('bookkeeping.debtors.tables.invoices.total_label')) . "\t"
            . sprintf('%-12s', '') . "\t"
            . sprintf('%-10s', '') . "\t"
            . sprintf('%-10s', '') . "\t"
            . sprintf('%-12s', '') . "\t"
            . sprintf('%-12s', Number::currency($debtor->getTotalDebt()))
            . PHP_EOL;

        return $text . $footer . PHP_EOL;
    }

    /**
     * Returns invoice PDF attachments for debtor messages.
     *
     * File existence is not validated here.
     *
     * @return array<string, array{
     *     file: string,
     *     mimetype: string,
     *     contentId: string,
     * }>
     */
    private function getAttachments(Debtor $debtor): array
    {
        $attachments = [];

        foreach ($debtor->getInvoices() as $invoice) {
            try {
                $filePath = $this->bookkeeping->getInvoicePdfPath($invoice);
            } catch (Throwable $e) {
                throw new InvoicePdfUnavailableException($invoice, $e);
            }

            $attachments[basename($filePath)] = [
                'file' => $filePath,
                'mimetype' => 'application/pdf',
                'contentId' => 'invoice-' . $invoice->number,
            ];
        }

        return $attachments;
    }

    /**
     * Determine if debtor should be notified today
     *
     * @param array<int> $notifyDays
     */
    private function shouldNotifyDebtor(
        Debtor $debtor,
        array $notifyDays,
        Date $today,
    ): bool {
        foreach ($notifyDays as $days) {
            if ($debtor->getDueDate()->equals($today->subDays($days))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Writes an email to a debtor, with the invoices it is about.
     *
     * @param \Bookkeeping\Debtors\Debtor $debtor Who it is for.
     * @param string $kind Which of the texts: notify, block or inactive.
     * @param array<\App\Model\Entity\Email> $recipients Where it goes.
     * @return \App\Model\Entity\CustomerMessage
     */
    private function generateEmail(Debtor $debtor, string $kind, array $recipients): CustomerMessage
    {
        return $this->saveMessage(
            debtor: $debtor,
            type: CustomerMessageType::EmailInvoices,
            recipients: $recipients,
            subject: Settings::getString('bookkeeping.debtors.emails.' . $kind . '.subject'),
            body: Settings::getString('bookkeeping.debtors.emails.' . $kind . '.body_text'),
            replacements: $this->placeholders($debtor) + [
                '{invoices_table}' => $this->getInvoicesTable($debtor),

                // company placeholders
                '{company_address_line_1}' => Settings::getString('core.company.address_line_1'),
                '{company_address_line_2}' => Settings::getString('core.company.address_line_2'),
                '{identity_number}' => Settings::getString('core.company.identity_number'),
                '{vat_number}' => Settings::getString('core.company.vat_number'),

                // billing/contact placeholders
                '{company_invoices_email}' => Settings::getString('core.company.invoices.email'),
                '{user_portal_url}' => Settings::getString('core.company.user_portal_url'),
            ],
            attachments: $this->getAttachments($debtor),
        );
    }

    /**
     * Writes an SMS to a debtor.
     *
     * @param \Bookkeeping\Debtors\Debtor $debtor Who it is for.
     * @param string $kind Which of the texts: notify, block or inactive.
     * @param array<\App\Model\Entity\Phone> $recipients Where it goes.
     * @return \App\Model\Entity\CustomerMessage
     */
    private function generateSms(Debtor $debtor, string $kind, array $recipients): CustomerMessage
    {
        return $this->saveMessage(
            debtor: $debtor,
            type: CustomerMessageType::Sms,
            recipients: $recipients,
            subject: Settings::getString('bookkeeping.debtors.sms.' . $kind . '.subject'),
            body: Settings::getString('bookkeeping.debtors.sms.' . $kind . '.body'),
            replacements: $this->placeholders($debtor),
        );
    }

    /**
     * What the texts of every channel can say.
     *
     * @param \Bookkeeping\Debtors\Debtor $debtor Who is written to.
     * @return array<string, mixed>
     */
    private function placeholders(Debtor $debtor): array
    {
        return [
            '{date}' => Date::now(),
            '{total_overdue_debt}' => Number::currency($debtor->getTotalOverdueDebt()),
            '{customer_number}' => $debtor->getCustomer()->number,
            '{company_name}' => Settings::getString('core.company.name'),
            '{company_invoices_phone}' => Settings::getString('core.company.invoices.phone'),
            '{bank_account_number}' => Settings::getString('core.company.bank_account_number'),
        ];
    }

    /**
     * Puts a message to a debtor in the queue.
     *
     * @param \Bookkeeping\Debtors\Debtor $debtor Who it is for.
     * @param \App\Model\Enum\CustomerMessageType $type How it goes.
     * @param array<\App\Model\Entity\Email>|array<\App\Model\Entity\Phone> $recipients Where it goes.
     * @param string $subject The subject, placeholders still in it.
     * @param string $body The body, placeholders still in it.
     * @param array<string, mixed> $replacements What the placeholders stand for.
     * @param array<string, array<string, string>>|null $attachments What goes with it.
     * @return \App\Model\Entity\CustomerMessage
     */
    private function saveMessage(
        Debtor $debtor,
        CustomerMessageType $type,
        array $recipients,
        string $subject,
        string $body,
        array $replacements,
        ?array $attachments = null,
    ): CustomerMessage {
        $customerMessagesTable = $this->fetchTable(CustomerMessagesTable::class);

        $customerMessage = $customerMessagesTable->newEmptyEntity();

        $customerMessage->type = $type;
        $customerMessage->direction = CustomerMessageDirection::Outgoing;
        $customerMessage->body_format = CustomerMessageBodyFormat::Plaintext;
        $customerMessage->delivery_status = CustomerMessageDeliveryStatus::Pending;

        $customerMessage->customer_id = $debtor->getCustomer()->id;
        $customerMessage->recipients = $recipients;
        $customerMessage->subject = strtr($subject, $replacements);
        $customerMessage->body = strtr($body, $replacements);
        $customerMessage->attachments = $attachments;

        return $customerMessagesTable->saveOrFail($customerMessage);
    }
}
