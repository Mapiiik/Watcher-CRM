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
use Bookkeeping\Debtors\Debtor;
use Bookkeeping\Debtors\DebtorsProcessor;
use Bookkeeping\Service\BookkeepingService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\I18n\Date;
use Cake\I18n\Number;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
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
     * The name of this command.
     *
     * @var string
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
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
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

            $notifyDays = array_map(
                'intval',
                array_filter(
                    explode(',', (string)env('DEBTORS_NOTIFY_DAYS', '5,10')),
                ),
            );

            $debtorsProcessor = new DebtorsProcessor(
                allowed_payment_delay: (int)env('DEBTORS_ALLOWED_PAYMENT_DELAY', '0'),
                allowed_total_overdue_debt: (float)env('DEBTORS_ALLOWED_TOTAL_OVERDUE_DEBT', '0'),
            );

            // automatically update the blocking of debtors in systems, if requested
            if ($args->getOption('blocking_update')) {
                $debtorsProcessor->blockingUpdate();

                $this->handleMessages($debtorsProcessor->getMessages(), $io);
            }

            // get debtors to notify
            $debtorsToNotify = !$args->getOption('only_block') ?
                $debtorsProcessor
                    ->getOverdueDebtors()
                    ->filter(
                        fn(Debtor $debtor) => $this->shouldNotifyDebtor(
                            debtor: $debtor,
                            notifyDays: $notifyDays,
                            today: $today,
                        ),
                    )
                :
                [];

            // get debtors to block (or continuing debt for services no longer active)
            $debtorsToBlock = !$args->getOption('only_notify') ?
                $debtorsProcessor->getFilteredOverdueDebtors()
                :
                [];

            /** @var \Bookkeeping\Debtors\Debtor $debtor */
            foreach ($debtorsToNotify as $debtor) {
                $emails_available = (count($debtor->getCustomer()->billing_emails) > 0);
                $phones_available = (count($debtor->getCustomer()->billing_phones) > 0);

                // notify emails
                if (
                    $emails_available
                    && !$args->getOption('skip_emails')
                    && $this->isDebtorNotificationEnabled('notify', 'email')
                ) {
                    $customerMessage = $this->generateNotifyEmail($debtor);
                    $io->info(__d(
                        'bookkeeping',
                        'Notification email has been generated'
                            . ' for customer {customer_number}, recipients: {recipients}',
                        [
                            'customer_number' => $debtor->getCustomer()->number,
                            'recipients' => implode(', ', $customerMessage->recipients),
                        ],
                    ));
                    unset($customerMessage);
                }

                // notify SMS
                if (
                    !$emails_available
                    && $phones_available
                    && !$args->getOption('skip_sms')
                    && $this->isDebtorNotificationEnabled('notify', 'sms')
                ) {
                    $customerMessage = $this->generateNotifySms($debtor);
                    $io->info(__d(
                        'bookkeeping',
                        'Notification SMS has been generated'
                            . ' for customer {customer_number}, recipients: {recipients}',
                        [
                            'customer_number' => $debtor->getCustomer()->number,
                            'recipients' => implode(', ', $customerMessage->recipients),
                        ],
                    ));
                    unset($customerMessage);
                }
            }

            /** @var \Bookkeeping\Debtors\Debtor $debtor */
            foreach ($debtorsToBlock as $debtor) {
                $emails_available = (count($debtor->getCustomer()->emails) > 0);
                $phones_available = (count($debtor->getCustomer()->phones) > 0);
                $messageType = $debtor->getCustomer()->active_services ? 'block' : 'inactive';

                // block emails
                if (
                    $emails_available
                    && !$args->getOption('skip_emails')
                    && $this->isDebtorNotificationEnabled($messageType, 'email')
                ) {
                    if ($messageType == 'block') {
                        $customerMessage = $this->generateBlockEmail($debtor);
                        $io->info(__d(
                            'bookkeeping',
                            'Blocking email has been generated'
                                . ' for customer {customer_number}, recipients: {recipients}',
                            [
                                'customer_number' => $debtor->getCustomer()->number,
                                'recipients' => implode(', ', $customerMessage->recipients),
                            ],
                        ));
                    } else {
                        $customerMessage = $this->generateNotifyEmailForInactiveServices($debtor);
                        $io->info(__d(
                            'bookkeeping',
                            'Notification email has been generated for inactive services'
                                . ' for customer {customer_number}, recipients: {recipients}',
                            [
                                'customer_number' => $debtor->getCustomer()->number,
                                'recipients' => implode(', ', $customerMessage->recipients),
                            ],
                        ));
                    }
                    unset($customerMessage);
                }

                // block SMS
                if (
                    $phones_available
                    && !$args->getOption('skip_sms')
                    && $this->isDebtorNotificationEnabled($messageType, 'sms')
                ) {
                    if ($messageType == 'block') {
                        $customerMessage = $this->generateBlockSms($debtor);
                        $io->info(__d(
                            'bookkeeping',
                            'Blocking SMS has been generated'
                                . ' for customer {customer_number}, recipients: {recipients}',
                            [
                                'customer_number' => $debtor->getCustomer()->number,
                                'recipients' => implode(', ', $customerMessage->recipients),
                            ],
                        ));
                    } else {
                        $customerMessage = $this->generateNotifySmsForInactiveServices($debtor);
                        $io->info(__d(
                            'bookkeeping',
                            'Notification SMS has been generated for inactive services'
                                . ' for customer {customer_number}, recipients: {recipients}',
                            [
                                'customer_number' => $debtor->getCustomer()->number,
                                'recipients' => implode(', ', $customerMessage->recipients),
                            ],
                        ));
                    }
                    unset($customerMessage);
                }
            }
            $io->info(__d('bookkeeping', 'Done'));

            return static::CODE_SUCCESS;
        } catch (Throwable $e) {
            Log::error('Error during debtor processing: ' . $e->getMessage());

            $io->error(__d(
                'bookkeeping',
                'Error during debtor processing: {0}',
                $e->getMessage(),
            ));

            // notify by email (if it fails, let it crash)
            $errorMailer = new Mailer('default');

            foreach (explode(' ', (string)env('REPORT_EMAILS')) as $email) {
                $errorMailer->addTo($email);
            }

            $errorMailer->setSubject(__d(
                'bookkeeping',
                'Debtor processing failed',
            ));

            $errorMailer->deliver(__d(
                'bookkeeping',
                'Debtor processing failed.' . PHP_EOL . PHP_EOL
                . 'Error: {0}',
                [$e->getMessage()],
            ));

            unset($errorMailer);

            return static::CODE_ERROR;
        }
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

        if (!(bool)Settings::get("bookkeeping.debtors.notifications.channels.$channel.enabled", false)) {
            return false;
        }

        return (bool)Settings::get("bookkeeping.debtors.notifications.types.$type.enabled", false);
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

        $text .= $footer . PHP_EOL;

        return $text;
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
            $filePath = $this->bookkeeping->getInvoicePdfPath($invoice);

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
     * @param \Bookkeeping\Debtors\Debtor $debtor
     * @param array<int> $notifyDays
     * @param \Cake\I18n\Date $today
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
     * Generate Email Message
     *
     * @param array<\App\Model\Entity\Email>|array<string> $recipients
     */
    private function generateEmail(
        Debtor $debtor,
        array $recipients,
        string $subjectTemplate,
        string $contentTemplate,
    ): CustomerMessage {
        $replacements = [
            '{date}' => Date::now(),
            '{total_overdue_debt}' => Number::currency($debtor->getTotalOverdueDebt()),
            '{customer_number}' => $debtor->getCustomer()->number,
            '{invoices_table}' => $this->getInvoicesTable($debtor),

            // company placeholders
            '{company_name}' => Settings::getString('core.company.name'),
            '{company_address_line_1}' => Settings::getString('core.company.address_line_1'),
            '{company_address_line_2}' => Settings::getString('core.company.address_line_2'),
            '{identity_number}' => Settings::getString('core.company.identity_number'),
            '{vat_number}' => Settings::getString('core.company.vat_number'),

            // billing/contact placeholders
            '{company_invoices_email}' => Settings::getString('core.company.invoices.email'),
            '{company_invoices_phone}' => Settings::getString('core.company.invoices.phone'),
            '{bank_account_number}' => Settings::getString('core.company.bank_account_number'),
            '{user_portal_url}' => Settings::getString('core.company.user_portal_url'),
        ];

        $customerMessagesTable = $this->fetchTable(CustomerMessagesTable::class);

        $customerMessage = $customerMessagesTable->newEmptyEntity();

        $customerMessage->type = CustomerMessageType::EmailInvoices;
        $customerMessage->direction = CustomerMessageDirection::Outgoing;
        $customerMessage->body_format = CustomerMessageBodyFormat::Plaintext;
        $customerMessage->delivery_status = CustomerMessageDeliveryStatus::Pending;

        $customerMessage->customer_id = $debtor->getCustomer()->id;
        $customerMessage->recipients = $recipients;
        $customerMessage->subject = strtr($subjectTemplate, $replacements);
        $customerMessage->body = strtr($contentTemplate, $replacements);

        $customerMessage->attachments = $this->getAttachments($debtor);

        return $customerMessagesTable->saveOrFail($customerMessage);
    }

    /**
     * Generate Notify Email Message
     */
    private function generateNotifyEmail(Debtor $debtor): CustomerMessage
    {
        $subjectTemplate = Settings::getString('bookkeeping.debtors.emails.notify.subject');
        $contentTemplate = Settings::getString('bookkeeping.debtors.emails.notify.body_text');

        return $this->generateEmail(
            $debtor,
            $debtor->getCustomer()->billing_emails,
            $subjectTemplate,
            $contentTemplate,
        );
    }

    /**
     * Generate Notify Email Message for Inactive Services
     */
    private function generateNotifyEmailForInactiveServices(Debtor $debtor): CustomerMessage
    {
        $subjectTemplate = Settings::getString('bookkeeping.debtors.emails.inactive.subject');
        $contentTemplate = Settings::getString('bookkeeping.debtors.emails.inactive.body_text');

        return $this->generateEmail(
            $debtor,
            $debtor->getCustomer()->emails,
            $subjectTemplate,
            $contentTemplate,
        );
    }

    /**
     * Generate Block Email Message
     */
    private function generateBlockEmail(Debtor $debtor): CustomerMessage
    {
        $subjectTemplate = Settings::getString('bookkeeping.debtors.emails.block.subject');
        $contentTemplate = Settings::getString('bookkeeping.debtors.emails.block.body_text');

        return $this->generateEmail(
            $debtor,
            $debtor->getCustomer()->emails,
            $subjectTemplate,
            $contentTemplate,
        );
    }

    /**
     * Generate SMS Message
     *
     * @param array<\App\Model\Entity\Phone>|array<string> $recipients
     */
    private function generateSms(
        Debtor $debtor,
        array $recipients,
        string $subjectTemplate,
        string $contentTemplate,
    ): CustomerMessage {
        $replacements = [
            '{date}' => Date::now(),
            '{total_overdue_debt}' => Number::currency($debtor->getTotalOverdueDebt()),
            '{customer_number}' => $debtor->getCustomer()->number,
            '{company_name}' => Settings::getString('core.company.name'),
            '{company_invoices_phone}' => Settings::getString('core.company.invoices.phone'),
            '{bank_account_number}' => Settings::getString('core.company.bank_account_number'),
        ];

        $customerMessagesTable = $this->fetchTable(CustomerMessagesTable::class);

        $customerMessage = $customerMessagesTable->newEmptyEntity();

        $customerMessage->type = CustomerMessageType::Sms;
        $customerMessage->direction = CustomerMessageDirection::Outgoing;
        $customerMessage->body_format = CustomerMessageBodyFormat::Plaintext;
        $customerMessage->delivery_status = CustomerMessageDeliveryStatus::Pending;

        $customerMessage->customer_id = $debtor->getCustomer()->id;
        $customerMessage->recipients = $recipients;
        $customerMessage->subject = strtr($subjectTemplate, $replacements);
        $customerMessage->body = strtr($contentTemplate, $replacements);

        return $customerMessagesTable->saveOrFail($customerMessage);
    }

    /**
     * Generate Notify SMS Message
     */
    private function generateNotifySms(Debtor $debtor): CustomerMessage
    {
        $subjectTemplate = Settings::getString('bookkeeping.debtors.sms.notify.subject');
        $contentTemplate = Settings::getString('bookkeeping.debtors.sms.notify.body');

        return $this->generateSms(
            $debtor,
            $debtor->getCustomer()->billing_phones,
            $subjectTemplate,
            $contentTemplate,
        );
    }

    /**
     * Generate Notify SMS Message for Inactive Services
     */
    private function generateNotifySmsForInactiveServices(Debtor $debtor): CustomerMessage
    {
        $subjectTemplate = Settings::getString('bookkeeping.debtors.sms.inactive.subject');
        $contentTemplate = Settings::getString('bookkeeping.debtors.sms.inactive.body');

        return $this->generateSms(
            $debtor,
            $debtor->getCustomer()->phones,
            $subjectTemplate,
            $contentTemplate,
        );
    }

    /**
     * Generate Block SMS Message
     */
    private function generateBlockSms(Debtor $debtor): CustomerMessage
    {
        $subjectTemplate = Settings::getString('bookkeeping.debtors.sms.block.subject');
        $contentTemplate = Settings::getString('bookkeeping.debtors.sms.block.body');

        return $this->generateSms(
            $debtor,
            $debtor->getCustomer()->phones,
            $subjectTemplate,
            $contentTemplate,
        );
    }
}
