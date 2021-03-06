<?php
declare(strict_types=1);

namespace BookkeepingPohoda\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\I18n\FrozenDate;
use Cake\I18n\FrozenTime;
use Cake\I18n\Number;
use Cake\Log\Log;
use Cake\Mailer\Mailer;

/**
 * SendIssuedInvoices command.
 */
class SendIssuedInvoicesCommand extends Command
{
    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/4/en/console-commands/commands.html#defining-arguments-and-options
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
     * @return null|void|int The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $issued_invoices_table = $this->fetchTable('BookkeepingPohoda.Invoices');
        $issued_invoices = $issued_invoices_table->find('all', [
            'contain' => ['Customers' => ['Emails']],
            'conditions' => [
                'send_by_email' => true,
                'email_sent IS' => null,
            ],
            'limit' => 50,
        ]);

        echo 'Sending notifications:' . "\n";
        foreach ($issued_invoices as $issued_invoice) {
            if (
                $issued_invoice->has('customer') &&
                $issued_invoice->customer->agree_mailing_billing &&
                count($issued_invoice->customer->billing_emails) > 0
            ) {
                // send email with notification
                echo 'Invoice - ' . $issued_invoice->number . ' - ' . $issued_invoice->customer->billing_email . ' - ';

                $mailer = new Mailer('invoices');

                foreach ($issued_invoice->customer->billing_emails as $email) {
                    $mailer->addTo($email->email);
                }
                $mailer->setSubject(
                    'NETAIR - ' . $issued_invoice->text
                        . ' - ' . $issued_invoice->number
                        . ' - VS' . $issued_invoice->variable_symbol
                );

                $mailer->setAttachments([
                    'NETAIR-' . $issued_invoice->number . '-VS' . $issued_invoice->variable_symbol . '.pdf' => [
                        'file' => '/data/nginx/crm.netair.net/data/invoices/'
                                    . 'Faktura_' . $issued_invoice->number . '.pdf',
                        'mimetype' => 'application/pdf',
                        'contentId' => 'issued-invoice-' . $issued_invoice->number,
                    ],
                ]);

                // define date format
                FrozenDate::setToStringFormat('dd.MM.yyyy');

                $message =
                    'V????en?? z??kazn??ku,' . PHP_EOL
                    . PHP_EOL
                    . 'dne ' . $issued_invoice->creation_date
                    . ' V??m byla vystavena faktura - da??ov?? doklad ??. ' . $issued_invoice->number
                    . ' splatn?? ' . $issued_invoice->due_date . '.' . PHP_EOL
                    . PHP_EOL
                    . 'Variabiln?? symbol pro platbu: ' . $issued_invoice->variable_symbol . PHP_EOL
                    . '????slo na??eho ????tu: 207385091/0100' . PHP_EOL
                    . 'Celkov?? ????stka (v??etn?? DPH): ' . Number::currency($issued_invoice->total) . PHP_EOL
                    . PHP_EOL
                    . 'V p????loze V??m zas??l??me doklad ve form??tu PDF.' . PHP_EOL
                    . PHP_EOL
                    . 'Tuto i dal???? n??mi vystaven?? faktury je mo??n?? stahovat i v na??em U??ivatelsk??m port??lu'
                    . ', kde si z??rove?? m????ete zkontrolovat, zda jsou uhrazeny.' . PHP_EOL
                    . 'Pokud si nep??ejete dost??vat faktury e-mailem, m????ete si zde zm??nit i formu zas??l??n??.' . PHP_EOL
                    . PHP_EOL
                    . 'U??ivatelsk?? port??l: https://nms.netair.cz/netair/' . PHP_EOL
                    . PHP_EOL
                    . 'Tento email byl vygenerov??n automaticky.' . PHP_EOL
                    . PHP_EOL
                    . 'NETAIR, s.r.o.' . PHP_EOL
                    . 'Jablonec nad Jizerou 299' . PHP_EOL
                    . '512 43 Jablonec nad Jizerou' . PHP_EOL
                    . 'I??: 27496139, DI??: CZ27496139';

                try {
                    $mailer->deliver($message);
                    Log::write('debug', 'Email was successfully sent.');
                    $io->info(__d('bookkeeping_pohoda', 'Email was successfully sent.'));

                    // save the date of submission to the database
                    $issued_invoice->email_sent = new FrozenTime();
                    $issued_invoices_table->save($issued_invoice);
                } catch (\Exception $e) {
                    Log::write('warning', 'The email cannot be sent. (' . $e->getMessage() . ')');
                    $io->abort(__d('bookkeeping_pohoda', 'The email cannot be sent.'));
                }

                // clean mailer
                unset($mailer);
            } else {
                Log::write('warning', 'Skipping invoice because no valid contact found.'
                    . ' (' . $issued_invoice->number . ' - ' . $issued_invoice->variable_symbol . ')');

                // do not attempt to re-deliver this invoice by email
                $issued_invoice->send_by_email = false;
                $issued_invoices_table->save($issued_invoice);
            }
        }
        echo 'Done' . "\n";
    }
}
