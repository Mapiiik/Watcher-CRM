<?php
declare(strict_types=1);

namespace BookkeepingPohoda\Command;

use App\Model\Entity\CustomerMessage;
use App\Model\Enum\CustomerMessageBodyFormat;
use App\Model\Enum\CustomerMessageDeliveryStatus;
use App\Model\Enum\CustomerMessageDirection;
use App\Model\Enum\CustomerMessageType;
use BookkeepingPohoda\Debtors\Debtor;
use BookkeepingPohoda\Debtors\DebtorsProcessor;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\I18n\Date;
use Cake\I18n\Number;

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
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null|void The exit code or null for success
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $debtorsProcessor = new DebtorsProcessor(
            allowed_payment_delay: (int)env('DEBTORS_ALLOWED_PAYMENT_DELAY', '0'),
            allowed_total_overdue_debt: (float)env('DEBTORS_ALLOWED_TOTAL_OVERDUE_DEBT', '0')
        );

        $debtorsToNotify = $debtorsProcessor
            ->getOverdueDebtors()
            ->filter(
                function (Debtor $debtor) {

                    return $debtor->getDueDate() == Date::now()->subDays(5)
                        || $debtor->getDueDate() == Date::now()->subDays(10);
                }
            );

        $debtorsToBlock = $debtorsProcessor->getFilteredOverdueDebtors();

        /** @var \BookkeepingPohoda\Debtors\Debtor $debtor */
        foreach ($debtorsToNotify as $debtor) {
            $emails_available = (count($debtor->getCustomer()->billing_emails) > 0);
            $phones_available = (count($debtor->getCustomer()->billing_phones) > 0);

            if ($emails_available) {
            /**
             * TODO: It needs to be finished
             */
            }

            if (!$emails_available && $phones_available) {
                $customerMessage = $this->generateNotifySms($debtor);
                $io->info(__d(
                    'bookkeeping_pohoda',
                    'Notification SMS has been generated, recipients: {recipients}, content: {body}',
                    [
                        'recipients' => implode(', ', $customerMessage->recipients),
                        'body' => $customerMessage->body,
                    ]
                ));
                unset($customerMessage);
            }
        }

        /** @var \BookkeepingPohoda\Debtors\Debtor $debtor */
        foreach ($debtorsToBlock as $debtor) {
            $emails_available = (count($debtor->getCustomer()->emails) > 0);
            $phones_available = (count($debtor->getCustomer()->phones) > 0);

            if ($emails_available) {
            /**
             * TODO: It needs to be finished
             */
            }

            if ($phones_available) {
                if ($debtor->getCustomer()->active_services) {
                    $customerMessage = $this->generateBlockSms($debtor);
                    $io->info(__d(
                        'bookkeeping_pohoda',
                        'Blocking SMS has been generated, recipients: {recipients}, content: {body}',
                        [
                            'recipients' => implode(', ', $customerMessage->recipients),
                            'body' => $customerMessage->body,
                        ]
                    ));
                } else {
                    $customerMessage = $this->generateNotifySmsForInactiveServices($debtor);
                    $io->info(__d(
                        'bookkeeping_pohoda',
                        'Notification SMS has been generated for inactive services'
                            . ', recipients: {recipients}, content: {body}',
                        [
                            'recipients' => implode(', ', $customerMessage->recipients),
                            'body' => $customerMessage->body,
                        ]
                    ));
                }
                unset($customerMessage);
            }
        }
        $io->info(__d('bookkeeping_pohoda', 'Done'));
    }

    /**
     * Generate SMS message
     */
    private function generateSms(
        Debtor $debtor,
        array $recipients,
        string $subjectTemplate,
        string $contentTemplate
    ): CustomerMessage {
        $replacements = [
            '{date}' => Date::now(),
            '{total_overdue_debt}' => Number::currency($debtor->getTotalOverdueDebt()),
            '{customer_number}' => $debtor->getCustomer()->number,
        ];

        /** @var \App\Model\Table\CustomerMessagesTable $customerMessagesTable */
        $customerMessagesTable = $this->fetchTable('CustomerMessages');

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
     * Generate Notify SMS message
     */
    private function generateNotifySms(Debtor $debtor): CustomerMessage
    {
        $subjectTemplate =
            // phpcs:ignore
            'NETAIR - neuhrazené pohledávky ke dni {date} - VS: {customer_number}';
        $contentTemplate =
            // phpcs:ignore
            'Vážený zákazníku, rádi bychom Vás upozornili, že k dnešnímu dni evidujeme neuhrazené pohledávky po splatnosti ve výši {total_overdue_debt}, VS: {customer_number}. Pokud máte vše uhrazeno, kontaktujte nás prosím.'
            . ' NETAIR, s.r.o., tel: +420488572511, č.ú.: 207385091/0100';

        return $this->generateSms($debtor, $debtor->getCustomer()->billing_phones, $subjectTemplate, $contentTemplate);
    }

    /**
     * Generate Notify SMS message
     */
    private function generateNotifySmsForInactiveServices(Debtor $debtor): CustomerMessage
    {
        $subjectTemplate =
            // phpcs:ignore
            'NETAIR - neaktivní služby - neuhrazené pohledávky ke dni {date} - VS: {customer_number}';
        $contentTemplate =
            // phpcs:ignore
            'Vážený zákazníku, rádi bychom Vás upozornili, že k dnešnímu dni stále evidujeme neuhrazené pohledávky po splatnosti ve výši {total_overdue_debt}, VS: {customer_number}. Pokud máte vše uhrazeno, kontaktujte nás prosím.'
            . ' NETAIR, s.r.o., tel: +420488572511, č.ú.: 207385091/0100';

        return $this->generateSms($debtor, $debtor->getCustomer()->billing_phones, $subjectTemplate, $contentTemplate);
    }

    /**
     * Generate Block SMS message
     */
    private function generateBlockSms(Debtor $debtor): CustomerMessage
    {
        $subjectTemplate =
            // phpcs:ignore
            'NETAIR - pozastavení služeb - neuhrazené pohledávky ke dni {date} - VS: {customer_number}';
        $contentTemplate =
            // phpcs:ignore
            'Vážený zákazníku, naše služby byly pozastaveny z důvodu neuhrazené pohledávky po splatnosti ve výši {total_overdue_debt}, VS: {customer_number}. Pokud máte vše uhrazeno, kontaktujte nás prosím.'
            . ' NETAIR, s.r.o., tel: +420488572511, č.ú.: 207385091/0100';

        return $this->generateSms($debtor, $debtor->getCustomer()->phones, $subjectTemplate, $contentTemplate);
    }
}
