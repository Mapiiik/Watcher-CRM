<?php
declare(strict_types=1);

namespace BookkeepingPohoda\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\I18n\Number;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
use Exception;

/**
 * SendIssuedInvoices command.
 */
class SendIssuedInvoicesCommand extends Command
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

        $parser->addOption('limit', [
            'help' => __d('bookkeeping_pohoda', 'Number of emails to process.'),
            'default' => '50',
            'required' => false,
        ]);

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
        $invoices_table = $this->fetchTable('BookkeepingPohoda.Invoices');
        $invoices = $invoices_table
            ->find()
            ->contain([
                'Customers' => [
                    'Emails',
                ],
            ])
            ->where([
                'send_by_email' => true,
                'email_sent IS' => null,
            ])
            ->limit((int)$args->getOption('limit'))
            ->all();

        $io->info(__d('bookkeeping_pohoda', 'Sending invoices:'));

        foreach ($invoices as $invoice) {
            if (
                $invoice->__isset('customer') &&
                $invoice->customer->agree_mailing_billing &&
                count($invoice->customer->billing_emails) > 0
            ) {
                // send email with notification
                $io->info(
                    __d(
                        'bookkeeping_pohoda',
                        'Invoice - {0} - {1}',
                        $invoice->number,
                        $invoice->customer->billing_email
                    )
                );

                $mailer = new Mailer('invoices');

                foreach ($invoice->customer->billing_emails as $email) {
                    $mailer->addTo($email->email);
                }
                $mailer->setSubject(
                    'NETAIR - ' . $invoice->text
                        . ' - ' . $invoice->number
                        . ' - VS: ' . $invoice->variable_symbol
                );

                $mailer->setAttachments([
                    'Faktura_' . $invoice->number . '.pdf' => [
                        'file' =>
                            env('DATA_ROOT', DS . 'data' . DS)
                            . 'invoices' . DS . 'Faktura_' . $invoice->number . '.pdf',
                        'mimetype' => 'application/pdf',
                        'contentId' => 'invoice-' . $invoice->number,
                    ],
                ]);

                // define date format
                Date::setToStringFormat('dd.MM.yyyy');

                $message =
                    'Vážený zákazníku,' . PHP_EOL
                    . PHP_EOL
                    . 'dne ' . $invoice->creation_date
                    . ' Vám byla vystavena faktura - daňový doklad č. ' . $invoice->number
                    . ' splatná ' . $invoice->due_date . '.' . PHP_EOL
                    . PHP_EOL
                    . 'Variabilní symbol pro platbu: ' . $invoice->variable_symbol . PHP_EOL
                    . 'Číslo našeho účtu: 207385091/0100' . PHP_EOL
                    . 'Celková částka (včetně DPH): ' . Number::currency($invoice->total) . PHP_EOL
                    . PHP_EOL
                    . 'V příloze Vám zasíláme doklad ve formátu PDF.' . PHP_EOL
                    . PHP_EOL
                    . 'Tuto i další námi vystavené faktury je možné stahovat i v našem Uživatelském portálu'
                    . ', kde si zároveň můžete zkontrolovat, zda jsou uhrazeny.' . PHP_EOL
                    . 'Pokud si nepřejete dostávat faktury e-mailem, můžete si zde změnit i formu zasílání.' . PHP_EOL
                    . PHP_EOL
                    . 'Uživatelský portál: https://nms.netair.cz/netair/' . PHP_EOL
                    . PHP_EOL
                    . 'Tento email byl vygenerován automaticky.' . PHP_EOL
                    . PHP_EOL
                    . 'NETAIR, s.r.o.' . PHP_EOL
                    . 'Jablonec nad Jizerou 299' . PHP_EOL
                    . '512 43 Jablonec nad Jizerou' . PHP_EOL
                    . 'IČ: 27496139, DIČ: CZ27496139';

                try {
                    $mailer->deliver($message);
                    Log::write('debug', 'Email was successfully sent.');
                    $io->info(__d('bookkeeping_pohoda', 'Email was successfully sent.'));

                    // save the date of submission to the database
                    $invoice->email_sent = DateTime::now();
                    $invoices_table->save($invoice);
                } catch (Exception $e) {
                    Log::write('warning', 'The email cannot be sent. (' . $e->getMessage() . ')');
                    $io->abort(__d('bookkeeping_pohoda', 'The email cannot be sent.'));
                }

                // clean mailer
                unset($mailer);
            } else {
                Log::write('warning', 'Skipping invoice because no valid contact found.'
                    . ' (' . $invoice->number . ' - ' . $invoice->variable_symbol . ')');

                // do not attempt to re-deliver this invoice by email
                $invoice->send_by_email = false;
                $invoices_table->save($invoice);
            }
        }
        $io->info(__d('bookkeeping_pohoda', 'Done'));
    }
}
