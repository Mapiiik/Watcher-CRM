<?php
declare(strict_types=1);

namespace Bookkeeping\Command;

use App\Model\Entity\CustomerMessage;
use App\Model\Enum\CustomerMessageBodyFormat;
use App\Model\Enum\CustomerMessageDeliveryStatus;
use App\Model\Enum\CustomerMessageDirection;
use App\Model\Enum\CustomerMessageType;
use App\Model\Table\CustomerMessagesTable;
use Bookkeeping\Debtors\Debtor;
use Bookkeeping\Debtors\DebtorsProcessor;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\I18n\Date;
use Cake\I18n\Number;
use Override;
use Settings\Utility\Settings;

/**
 * Process Debtors command.
 */
class ProcessDebtorsCommand extends Command
{
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
        $debtorsProcessor = new DebtorsProcessor(
            allowed_payment_delay: (int)env('DEBTORS_ALLOWED_PAYMENT_DELAY', '0'),
            allowed_total_overdue_debt: (float)env('DEBTORS_ALLOWED_TOTAL_OVERDUE_DEBT', '0'),
        );

        // automatically update the blocking of debtors in systems, if requested
        if ($args->getOption('blocking_update')) {
            $result = $debtorsProcessor->blockingUpdate();

            $io->info(
                __d('bookkeeping', 'Systems updated.') . PHP_EOL
                    . ($result ?: __d('bookkeeping', 'Nothing has changed.')),
            );
        }

        // get debtors to notify
        $debtorsToNotify = !$args->getOption('only_block') ?
            $debtorsProcessor
                ->getOverdueDebtors()
                ->filter(
                    function (Debtor $debtor) {

                        return $debtor->getDueDate() == Date::now()->subDays(5)
                            || $debtor->getDueDate() == Date::now()->subDays(10);
                    },
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
            if ($emails_available && !$args->getOption('skip_emails')) {
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
            if (!$emails_available && $phones_available && !$args->getOption('skip_sms')) {
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

            // block emails
            if ($emails_available && !$args->getOption('skip_emails')) {
                if ($debtor->getCustomer()->active_services) {
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
            if ($phones_available && !$args->getOption('skip_sms')) {
                if ($debtor->getCustomer()->active_services) {
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
    }

    /**
     * Get Invoices Table
     */
    private function getInvoicesTable(Debtor $debtor): string
    {
        $separator = Settings::getString('core.debtors.tables.invoices.separator');
        $footer = Settings::getString('core.debtors.tables.invoices.footer');

        $text =
            sprintf('%-15s', Settings::getString('core.debtors.tables.invoices.headers.number')) . "\t"
            . sprintf('%-12s', Settings::getString('core.debtors.tables.invoices.headers.variable_symbol')) . "\t"
            . sprintf('%-10s', Settings::getString('core.debtors.tables.invoices.headers.creation_date')) . "\t"
            . sprintf('%-10s', Settings::getString('core.debtors.tables.invoices.headers.due_date')) . "\t"
            . sprintf('%-12s', Settings::getString('core.debtors.tables.invoices.headers.total')) . "\t"
            . sprintf('%-12s', Settings::getString('core.debtors.tables.invoices.headers.debt'))
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
            sprintf('%-15s', Settings::getString('core.debtors.tables.invoices.total_label')) . "\t"
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
     * Get Attachments
     *
     * @return array<string, mixed>
     */
    private function getAttachments(Debtor $debtor): array
    {
        $attachments = [];

        foreach ($debtor->getInvoices() as $invoice) {
            $attachments['Faktura_' . $invoice->number . '.pdf'] = [
                'file' =>
                    (string)env('DATA_ROOT', DS . 'data' . DS)
                    . 'invoices' . DS . 'Faktura_' . $invoice->number . '.pdf',
                'mimetype' => 'application/pdf',
                'contentId' => 'invoice-' . $invoice->number,
            ];
        }

        return $attachments;
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
        $subjectTemplate = Settings::getString('core.debtors.emails.notify.subject');
        $contentTemplate = Settings::getString('core.debtors.emails.notify.body_text');

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
        $subjectTemplate = Settings::getString('core.debtors.emails.inactive.subject');
        $contentTemplate = Settings::getString('core.debtors.emails.inactive.body_text');

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
        $subjectTemplate = Settings::getString('core.debtors.emails.block.subject');
        $contentTemplate = Settings::getString('core.debtors.emails.block.body_text');

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
        $subjectTemplate = Settings::getString('core.debtors.sms.notify.subject');
        $contentTemplate = Settings::getString('core.debtors.sms.notify.body');

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
        $subjectTemplate = Settings::getString('core.debtors.sms.inactive.subject');
        $contentTemplate = Settings::getString('core.debtors.sms.inactive.body');

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
        $subjectTemplate = Settings::getString('core.debtors.sms.block.subject');
        $contentTemplate = Settings::getString('core.debtors.sms.block.body');

        return $this->generateSms(
            $debtor,
            $debtor->getCustomer()->phones,
            $subjectTemplate,
            $contentTemplate,
        );
    }
}
