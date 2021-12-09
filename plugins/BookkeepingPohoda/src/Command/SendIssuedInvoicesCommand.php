<?php
declare(strict_types=1);

namespace BookkeepingPohoda\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\I18n\FrozenDate;
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
        $issued_invoices = $this->fetchTable('Invoices')->find('all', [
            'conditions' => [
                'send_by_email' => true,
                'email_sent IS' => null,
            ],
            'limit' => 50,
        ]);

        echo 'Sending notifications:' . "\n";
        foreach ($issued_invoices as $issued_invoice)
        {
            $customer = $this->fetchTable('Customer')->get($issued_invoice->customer_id, [
                'contain' => ['Emails'],
            ]);

            // send email with notification
            echo 'Invoice - ' . $issued_invoice->number . ' - ' . $customer->emails->emails . ' - ';

            $mailer = new Mailer('default');
            $mailer->setFrom([
                (string)env('EMAIL_TRANSPORT_DEFAULT_SENDER_EMAIL', 'mapik@mapik.net')
                => (string)env('EMAIL_TRANSPORT_DEFAULT_SENDER_NAME', 'Mapik'),
            ]);

            $mailer->addTo('mapik@mapik.net');
            foreach ($customer->emails as $email) {
                if ($email->use_for_billing) {
                    //$mailer->addTo($email->email);
                    echo $email->email;
                }
            }
            $mailer->setSubject($issued_invoice->text . ' - ' . $issued_invoice->number . ' - VS' . $issued_invoice->variable_symbol . ' - NETAIR, s.r.o.');

            // define date format
            FrozenDate::setToStringFormat('dd.MM.YYYY');

            $message = "Vážený zákazníku,

dne " . $issued_invoice->creation_date . " Vám byla na základě smlouvy č. " . $issued_invoice->variable_symbol . ",
vystavena faktura - daňový doklad, č. " . $issued_invoice->number . ", splatná " . $issued_invoice->due_date . ".

Variabilní symbol pro úhradu: " . $issued_invoice->variable_symbol . "
Celková částka (včetně DPH): " . Number::currency($issued_invoice->total) . "
Úhadu proveďte prosím na účet: 207385091 / 0100

V příloze Vám zasíláme doklad ve formátu PDF.

Tuto i další námi vystavené faktury je možné zároveň stahovat i v našem uživatelském systému (http://data.netair.cz/), kde si můžete zkontrolovat také zda jsou uhrazeny.
Pokud si nepřejete dostávat faktury e-mailem, můžete si zde změnit i formu zasílání.

Na tento e-mail prosím neodpovídejte, byl vygenerován automaticky.

NETAIR, s.r.o.
Jablonec nad Jizerou 299, 512 43 Jablonec nad Jizerou
IČ: 27496139, DIČ: CZ27496139";

            try {
                $mailer->deliver($message);
                Log::write('debug', 'E-mail was successfully sent..');
                $io->info('E-mail was successfully sent..');
            } catch (\Exception $e) {
                Log::write('warning', 'The e-mail cannot be sent.');
                $io->abort('The e-mail cannot be sent.');
            }
        }
        echo 'Done' . "\n";
    }
}
