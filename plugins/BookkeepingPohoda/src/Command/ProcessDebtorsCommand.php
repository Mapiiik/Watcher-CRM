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

            // notify emails
            if ($emails_available) {
                $customerMessage = $this->generateNotifyEmail($debtor);
                $io->info(__d(
                    'bookkeeping_pohoda',
                    'Notification email has been generated'
                        . ' for customer {customer_number}, recipients: {recipients}',
                    [
                        'customer_number' => $debtor->getCustomer()->number,
                        'recipients' => implode(', ', $customerMessage->recipients),
                    ]
                ));
                unset($customerMessage);
            }

            // notify SMS
            if (!$emails_available && $phones_available) {
                $customerMessage = $this->generateNotifySms($debtor);
                $io->info(__d(
                    'bookkeeping_pohoda',
                    'Notification SMS has been generated'
                        . ' for customer {customer_number}, recipients: {recipients}',
                    [
                        'customer_number' => $debtor->getCustomer()->number,
                        'recipients' => implode(', ', $customerMessage->recipients),
                    ]
                ));
                unset($customerMessage);
            }
        }

        /** @var \BookkeepingPohoda\Debtors\Debtor $debtor */
        foreach ($debtorsToBlock as $debtor) {
            $emails_available = (count($debtor->getCustomer()->emails) > 0);
            $phones_available = (count($debtor->getCustomer()->phones) > 0);

            // block emails
            if ($emails_available) {
                if ($debtor->getCustomer()->active_services) {
                    $customerMessage = $this->generateBlockEmail($debtor);
                    $io->info(__d(
                        'bookkeeping_pohoda',
                        'Blocking email has been generated'
                            . ' for customer {customer_number}, recipients: {recipients}',
                        [
                            'customer_number' => $debtor->getCustomer()->number,
                            'recipients' => implode(', ', $customerMessage->recipients),
                        ]
                    ));
                } else {
                    $customerMessage = $this->generateNotifyEmailForInactiveServices($debtor);
                    $io->info(__d(
                        'bookkeeping_pohoda',
                        'Notification email has been generated for inactive services'
                            . ' for customer {customer_number}, recipients: {recipients}',
                        [
                            'customer_number' => $debtor->getCustomer()->number,
                            'recipients' => implode(', ', $customerMessage->recipients),
                        ]
                    ));
                }
                unset($customerMessage);
            }

            // block SMS
            if ($phones_available) {
                if ($debtor->getCustomer()->active_services) {
                    $customerMessage = $this->generateBlockSms($debtor);
                    $io->info(__d(
                        'bookkeeping_pohoda',
                        'Blocking SMS has been generated'
                            . ' for customer {customer_number}, recipients: {recipients}',
                        [
                            'customer_number' => $debtor->getCustomer()->number,
                            'recipients' => implode(', ', $customerMessage->recipients),
                        ]
                    ));
                } else {
                    $customerMessage = $this->generateNotifySmsForInactiveServices($debtor);
                    $io->info(__d(
                        'bookkeeping_pohoda',
                        'Notification SMS has been generated for inactive services'
                            . ' for customer {customer_number}, recipients: {recipients}',
                        [
                            'customer_number' => $debtor->getCustomer()->number,
                            'recipients' => implode(', ', $customerMessage->recipients),
                        ]
                    ));
                }
                unset($customerMessage);
            }
        }
        $io->info(__d('bookkeeping_pohoda', 'Done'));
    }

    /**
     * Get Invoices Table
     */
    private function getInvoicesTable(Debtor $debtor): string
    {
        $text =
            sprintf('%-15s', 'Číslo faktury') . "\t"
            . sprintf('%-12s', 'Var. symbol') . "\t"
            . sprintf('%-10s', 'Datum') . "\t"
            . sprintf('%-10s', 'Splatnost') . "\t"
            . sprintf('%-12s', 'Cena') . "\t"
            . sprintf('%-12s', 'Dluh')
            . PHP_EOL;

        // phpcs:ignore
        $text .= '-------------------------------------------------------------------------------------------' . PHP_EOL;

        foreach ($debtor->getInvoices() as $invoice) {
            $text .=
                sprintf('%-15s', $invoice->number) . "\t"
                . sprintf('%-12s', $invoice->variable_symbol) . "\t"
                . sprintf('%-10s', $invoice->creation_date) . "\t"
                . sprintf('%-10s', $invoice->due_date) . "\t"
                . sprintf('%-12s', Number::currency($invoice->total)) . "\t"
                . sprintf('%-12s', Number::currency($invoice->debt))
                . PHP_EOL;
        }

        // phpcs:ignore
        $text .= '-------------------------------------------------------------------------------------------' . PHP_EOL;

        $text .=
            sprintf('%-15s', 'Dluh celkem:') . "\t"
            . sprintf('%-12s', '') . "\t"
            . sprintf('%-10s', '') . "\t"
            . sprintf('%-10s', '') . "\t"
            . sprintf('%-12s', '') . "\t"
            . sprintf('%-12s', Number::currency($debtor->getTotalDebt()))
            . PHP_EOL;

        // phpcs:ignore
        $text .= '===========================================================================================' . PHP_EOL;

        return $text;
    }

    /**
     * Get Attachments
     */
    private function getAttachments(Debtor $debtor): array
    {
        $attachments = [];

        foreach ($debtor->getInvoices() as $invoice) {
            $attachments['Faktura_' . $invoice->number . '.pdf'] = [
                'file' =>
                    env('DATA_ROOT', DS . 'data' . DS)
                    . 'invoices' . DS . 'Faktura_' . $invoice->number . '.pdf',
                'mimetype' => 'application/pdf',
                'contentId' => 'invoice-' . $invoice->number,
            ];
        }

        return $attachments;
    }

    /**
     * Generate Email Message
     */
    private function generateEmail(
        Debtor $debtor,
        array $recipients,
        string $subjectTemplate,
        string $contentTemplate
    ): CustomerMessage {
        $replacements = [
            '{date}' => Date::now(),
            '{total_overdue_debt}' => Number::currency($debtor->getTotalOverdueDebt()),
            '{customer_number}' => $debtor->getCustomer()->number,
            '{invoices_table}' => $this->getInvoicesTable($debtor),
        ];

        /** @var \App\Model\Table\CustomerMessagesTable $customerMessagesTable */
        $customerMessagesTable = $this->fetchTable('CustomerMessages');

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
        // phpcs:disable
        $subjectTemplate =
            'NETAIR - neuhrazené pohledávky ke dni {date} - VS: {customer_number}';
        $contentTemplate =
            'Vážený zákazníku,' . PHP_EOL
            . PHP_EOL
            . 'rádi bychom Vás upozornili, že k dnešnímu dni evidujeme neuhrazené pohledávky po splatnosti ve výši {total_overdue_debt}, VS: {customer_number}.' . PHP_EOL
            . PHP_EOL
            . '{invoices_table}'
            . PHP_EOL
            . 'Pokud máte vše uhrazeno, kontaktujte nás prosím a sdělte nám datum, variabilní symbol a číslo účtu, ze kterého byly platby provedeny.' . PHP_EOL
            . PHP_EOL
            . 'Pokud se jedná o nedoplatek, je to pravděpodobně způsobeno tím, že jste nedávno byli převedeni na nové tarify, o čemž jsme vás s dostatečným předstihem informovali e-mailem.' . PHP_EOL
            . PHP_EOL
            . 'Kontakty na naše účetní oddělení' . PHP_EOL
            . 'Mail: fakturace@netair.cz' . PHP_EOL
            . 'Telefon: +420 488572511' . PHP_EOL
            . 'Číslo účtu: 207385091/0100' . PHP_EOL
            . PHP_EOL
            . 'Volat můžete od pondělí do pátku mezi 08:00-12:00 a 13:00-17:00.' . PHP_EOL
            . PHP_EOL
            . 'Pokud Vám nepřichází faktury do emailu, zkontrolujte si prosím zda jste odsouhlasili, že je chcete dostávat.' . PHP_EOL
            . 'Potřebné souhlasy je možné udělit v sekci Uživatelské údaje, po přihlášení do Uživatelského portálu: https://nms.netair.cz/netair/' . PHP_EOL
            . 'Pokud nemáte přihlašovací údaje, můžete si je vyžádat zde: https://netair.cz/podpora/zrizeni-pristupu-do-uzivatelskeho-portalu/' . PHP_EOL
            . PHP_EOL
            . 'NETAIR, s.r.o.' . PHP_EOL
            . 'Jablonec nad Jizerou 299' . PHP_EOL
            . '512 43 Jablonec nad Jizerou' . PHP_EOL
            . 'IČ: 27496139, DIČ: CZ27496139' . PHP_EOL;
        // phpcs:enable

        return $this->generateEmail(
            $debtor,
            $debtor->getCustomer()->billing_emails,
            $subjectTemplate,
            $contentTemplate
        );
    }

    /**
     * Generate Notify Email Message for Inactive Services
     */
    private function generateNotifyEmailForInactiveServices(Debtor $debtor): CustomerMessage
    {
        // phpcs:disable
        $subjectTemplate =
            'NETAIR - neaktivní služby - neuhrazené pohledávky ke dni {date} - VS: {customer_number}';
        $contentTemplate =
            'Vážený zákazníku,' . PHP_EOL
            . PHP_EOL
            . 'rádi bychom Vás upozornili, že k dnešnímu dni stále evidujeme neuhrazené pohledávky po splatnosti ve výši {total_overdue_debt}, VS: {customer_number}.' . PHP_EOL
            . PHP_EOL
            . '{invoices_table}'
            . PHP_EOL
            . 'Pokud máte vše uhrazeno, kontaktujte nás prosím a sdělte nám datum, variabilní symbol a číslo účtu, ze kterého byly platby provedeny.' . PHP_EOL
            . PHP_EOL
            . 'Kontakty na naše účetní oddělení' . PHP_EOL
            . 'Mail: fakturace@netair.cz' . PHP_EOL
            . 'Telefon: +420 488572511' . PHP_EOL
            . 'Číslo účtu: 207385091/0100' . PHP_EOL
            . PHP_EOL
            . 'Volat můžete od pondělí do pátku mezi 08:00-12:00 a 13:00-17:00.' . PHP_EOL
            . PHP_EOL
            . 'Pokud Vám nepřichází faktury do emailu, zkontrolujte si prosím zda jste odsouhlasili, že je chcete dostávat.' . PHP_EOL
            . 'Potřebné souhlasy je možné udělit v sekci Uživatelské údaje, po přihlášení do Uživatelského portálu: https://nms.netair.cz/netair/' . PHP_EOL
            . 'Pokud nemáte přihlašovací údaje, můžete si je vyžádat zde: https://netair.cz/podpora/zrizeni-pristupu-do-uzivatelskeho-portalu/' . PHP_EOL
            . PHP_EOL
            . 'NETAIR, s.r.o.' . PHP_EOL
            . 'Jablonec nad Jizerou 299' . PHP_EOL
            . '512 43 Jablonec nad Jizerou' . PHP_EOL
            . 'IČ: 27496139, DIČ: CZ27496139' . PHP_EOL;
        // phpcs:enable

        return $this->generateEmail($debtor, $debtor->getCustomer()->emails, $subjectTemplate, $contentTemplate);
    }

    /**
     * Generate Block Email Message
     */
    private function generateBlockEmail(Debtor $debtor): CustomerMessage
    {
        // phpcs:disable
        $subjectTemplate =
            'NETAIR - pozastavení služeb - neuhrazené pohledávky ke dni {date} - VS: {customer_number}';
        $contentTemplate =
            'Vážený zákazníku,' . PHP_EOL
            . PHP_EOL
            . 'rádi bychom Vás upozornili, že naše služby byly pozastaveny z důvodu neuhrazené pohledávky po splatnosti ve výši {total_overdue_debt}, VS: {customer_number}.' . PHP_EOL
            . PHP_EOL
            . '{invoices_table}'
            . PHP_EOL
            . 'Pokud máte vše uhrazeno, kontaktujte nás prosím a sdělte nám datum, variabilní symbol a číslo účtu, ze kterého byly platby provedeny.' . PHP_EOL
            . PHP_EOL
            . 'Pokud se jedná o nedoplatek, je to pravděpodobně způsobeno tím, že jste nedávno byli převedeni na nové tarify, o čemž jsme vás s dostatečným předstihem informovali e-mailem.' . PHP_EOL
            . PHP_EOL
            . 'Kontakty na naše účetní oddělení' . PHP_EOL
            . 'Mail: fakturace@netair.cz' . PHP_EOL
            . 'Telefon: +420 488572511' . PHP_EOL
            . 'Číslo účtu: 207385091/0100' . PHP_EOL
            . PHP_EOL
            . 'Volat můžete od pondělí do pátku mezi 08:00-12:00 a 13:00-17:00.' . PHP_EOL
            . PHP_EOL
            . 'Pokud Vám nepřichází faktury do emailu, zkontrolujte si prosím zda jste odsouhlasili, že je chcete dostávat.' . PHP_EOL
            . 'Potřebné souhlasy je možné udělit v sekci Uživatelské údaje, po přihlášení do Uživatelského portálu: https://nms.netair.cz/netair/' . PHP_EOL
            . 'Pokud nemáte přihlašovací údaje, můžete si je vyžádat zde: https://netair.cz/podpora/zrizeni-pristupu-do-uzivatelskeho-portalu/' . PHP_EOL
            . PHP_EOL
            . 'NETAIR, s.r.o.' . PHP_EOL
            . 'Jablonec nad Jizerou 299' . PHP_EOL
            . '512 43 Jablonec nad Jizerou' . PHP_EOL
            . 'IČ: 27496139, DIČ: CZ27496139' . PHP_EOL;
        // phpcs:enable

        return $this->generateEmail($debtor, $debtor->getCustomer()->emails, $subjectTemplate, $contentTemplate);
    }

    /**
     * Generate SMS Message
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
     * Generate Notify SMS Message
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
     * Generate Notify SMS Message for Inactive Services
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

        return $this->generateSms($debtor, $debtor->getCustomer()->phones, $subjectTemplate, $contentTemplate);
    }

    /**
     * Generate Block SMS Message
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
