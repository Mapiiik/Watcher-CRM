<?php
declare(strict_types=1);

namespace Bookkeeping\Command;

use App\Model\Enum\CustomerInvoiceDeliveryType;
use Bookkeeping\Model\Table\InvoicesTable;
use Bookkeeping\Service\BookkeepingService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\I18n\DateTime;
use Cake\I18n\Number;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
use Override;
use Settings\Utility\Settings;
use Throwable;

/**
 * SendIssuedInvoices command.
 *
 * Sends issued invoices to customers by email.
 *
 * This command processes invoices that:
 * - are marked as sendable by email (`send_by_email = true`)
 * - have not yet been sent (`email_sent IS NULL`)
 *
 * For each eligible invoice, it:
 * - resolves customer billing email addresses
 * - generates an email message using configured templates
 * - attaches the invoice PDF
 * - sends the email to all billing contacts
 * - marks the invoice as sent by setting `email_sent`
 *
 * In case of delivery failure:
 * - the error is logged
 * - a notification email is sent to configured report recipients
 *
 * This command acts only as an orchestration layer and does NOT:
 * - generate invoices
 * - modify invoice amounts or accounting data
 * - communicate with the accounting system
 *
 * It is intended to be run periodically (e.g. via cron)
 * to deliver already issued invoices to customers.
 */
class SendIssuedInvoicesCommand extends Command
{
    private readonly BookkeepingService $bookkeeping;

    /**
     * The name of this command.
     */
    protected string $name = 'send_issued_invoices';

    /**
     * Get the default command name.
     *
     * @return string
     */
    public static function defaultName(): string
    {
        return 'send_issued_invoices';
    }

    /**
     * Get the command description.
     *
     * @return string
     */
    public static function getDescription(): string
    {
        return 'Send issued invoices to customers by email.';
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

        $parser->addOption('limit', [
            'help' => __d('bookkeeping', 'Number of emails to process.'),
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
     * @return void The exit code or null for success
     */
    #[Override]
    public function execute(Arguments $args, ConsoleIo $io): void
    {
        $invoicesTable = $this->fetchTable(InvoicesTable::class);
        /** @var iterable<\Bookkeeping\Model\Entity\Invoice> $invoices */
        $invoices = $invoicesTable
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

        $io->info(__d('bookkeeping', 'Sending invoices:'));

        foreach ($invoices as $invoice) {
            if (
                $invoice->customer !== null &&
                $invoice->customer->agree_mailing_billing &&
                $invoice->customer->invoice_delivery_type === CustomerInvoiceDeliveryType::Email &&
                count($invoice->customer->billing_emails) > 0
            ) {
                // send email with notification
                $io->info(
                    __d(
                        'bookkeeping',
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
                    strtr(Settings::getString('bookkeeping.invoices.emails.subject'), [
                        '{invoice_text}' => $invoice->text,
                        '{invoice_number}' => (string)$invoice->number,
                        '{variable_symbol}' => (string)$invoice->variable_symbol,
                    ]),
                );

                $message = strtr(Settings::getString('bookkeeping.invoices.emails.body_text'), [
                    '{creation_date}' => (string)$invoice->creation_date,
                    '{due_date}' => (string)$invoice->due_date,
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
                    $filePath = $this->bookkeeping->getInvoicePdfPath($invoice);

                    $mailer->setAttachments([
                        basename($filePath) => [
                            'file' => $filePath,
                            'mimetype' => 'application/pdf',
                            'contentId' => 'invoice-' . $invoice->number,
                        ],
                    ]);

                    // send message
                    $mailer->deliver($message);

                    // info to console
                    $io->info(__d('bookkeeping', 'Email was successfully sent.'));

                    // save the date of submission to the database
                    $invoice->email_sent = DateTime::now();
                    $invoicesTable->save($invoice);
                } catch (Throwable $e) {
                    Log::error('Error sending email message with issued invoice ID '
                        . $invoice->id . ': ' . $e->getMessage());
                    $io->error(__d(
                        'bookkeeping',
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
                        'bookkeeping',
                        'Error sending email message with issued invoice ID {0}',
                        $invoice->id,
                    ));

                    $errorMailer->deliver(__d(
                        'bookkeeping',
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
                    . ' (' . $invoice->number . ' - ' . $invoice->variable_symbol . ')');

                // do not attempt to re-deliver this invoice by email
                $invoice->send_by_email = false;
                $invoicesTable->save($invoice);
            }
        }
        $io->info(__d('bookkeeping', 'Done'));
    }
}
