<?php
declare(strict_types=1);

namespace BookkeepingPohoda\Command;

use App\Utility\Settings;
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
use Override;

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
    #[Override]
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
     */
    #[Override]
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $invoices_table = $this->fetchTable('BookkeepingPohoda.Invoices');
        /** @var iterable<\BookkeepingPohoda\Model\Entity\Invoice> $invoices */
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
                        $invoice->customer->billing_email,
                    ),
                );

                $mailer = new Mailer('invoices');

                foreach ($invoice->customer->billing_emails as $email) {
                    $mailer->addTo($email->email);
                }
                $mailer->setSubject(
                    strtr(Settings::getString('core.invoices.emails.subject'), [
                        '{invoice_text}' => $invoice->text,
                        '{invoice_number}' => (string)$invoice->number,
                        '{variable_symbol}' => (string)$invoice->variable_symbol,
                    ]),
                );

                // define date format
                Date::setToStringFormat('dd.MM.yyyy');

                $message = strtr(Settings::getString('core.invoices.emails.body'), [
                    '{creation_date}' => $invoice->creation_date->__toString(),
                    '{due_date}' => $invoice->due_date->__toString(),
                    '{invoice_number}' => (string)$invoice->number,
                    '{variable_symbol}' => (string)$invoice->variable_symbol,
                    '{bank_account_number}' => Settings::getString('core.company.bank_account_number'),
                    '{total_amount}' => Number::currency($invoice->total->toFloat()),
                    '{user_portal_url}' => Settings::getString('core.company.user_portal_url'),
                    '{company_name}' => Settings::getString('core.company.name'),
                    '{company_address_line_1}' => Settings::getString('core.company.address_line_1'),
                    '{company_address_line_2}' => Settings::getString('core.company.address_line_2'),
                    '{identity_number}' => Settings::getString('core.company.identity_number'),
                    '{vat_number}' => Settings::getString('core.company.vat_number'),
                ]);

                try {
                    // add attachment
                    $mailer->setAttachments([
                        'Faktura_' . $invoice->number . '.pdf' => [
                            'file' =>
                                (string)env('DATA_ROOT', DS . 'data' . DS)
                                . 'invoices' . DS . 'Faktura_' . $invoice->number . '.pdf',
                            'mimetype' => 'application/pdf',
                            'contentId' => 'invoice-' . $invoice->number,
                        ],
                    ]);

                    // send message
                    $mailer->deliver($message);

                    // info to console
                    $io->info(__d('bookkeeping_pohoda', 'Email was successfully sent.'));

                    // save the date of submission to the database
                    $invoice->email_sent = DateTime::now();
                    $invoices_table->save($invoice);
                } catch (Exception $e) {
                    Log::error('Error sending email message with issued invoice ID '
                        . $invoice->id . ': ' . $e->getMessage());
                    $io->error(__d(
                        'bookkeeping_pohoda',
                        'Error sending email message with issued invoice ID {0}: {1}',
                        $invoice->id,
                        $e->getMessage(),
                    ));

                    // try to send a notification of the problem to mail (if it fails it will crash)
                    $errorMailer = new Mailer('default');

                    foreach (explode(' ', (string)env('REPORT_EMAILS')) as $email) {
                        $errorMailer->addTo($email);
                    }

                    $errorMailer->setSubject(__d(
                        'bookkeeping_pohoda',
                        'Error sending email message with issued invoice ID {0}',
                        $invoice->id,
                    ));

                    $errorMailer->deliver(__d(
                        'bookkeeping_pohoda',
                        'Error sending email message with issued invoice ID {0}: {1}',
                        $invoice->id,
                        $e->getMessage(),
                    ));

                    unset($errorMailer);
                }

                // clean mailer
                unset($mailer);
            } else {
                Log::warning('Skipping invoice because no valid contact found.'
                    . ' (' . $invoice->number . ' - ' . (string)$invoice->variable_symbol . ')');

                // do not attempt to re-deliver this invoice by email
                $invoice->send_by_email = false;
                $invoices_table->save($invoice);
            }
        }
        $io->info(__d('bookkeeping_pohoda', 'Done'));
    }
}
