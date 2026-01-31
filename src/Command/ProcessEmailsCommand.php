<?php
declare(strict_types=1);

namespace App\Command;

use App\Model\Enum\CustomerMessageBodyFormat;
use App\Model\Enum\CustomerMessageDeliveryStatus;
use App\Model\Enum\CustomerMessageDirection;
use App\Model\Enum\CustomerMessageType;
use App\Model\Table\CustomerMessagesTable;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
use Cake\Mailer\Message;
use InvalidArgumentException;
use Override;
use Throwable;

/**
 * Process Emails command.
 */
class ProcessEmailsCommand extends Command
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
            'help' => __('Number of emails to process.'),
            'default' => '50',
            'required' => false,
        ]);

        $parser->addOption('maximum_message_age', [
            'help' => __('Maximum age of a message that will still be processed (days).'),
            'default' => '7',
            'required' => false,
        ]);

        $parser->addOption('wait_min', [
            'help' => __('Minimum waiting time after request to send (seconds).'),
            'default' => '0',
            'required' => false,
        ]);

        $parser->addOption('wait_max', [
            'help' => __('Maximum waiting time after request to send (seconds).'),
            'default' => '0',
            'required' => false,
        ]);

        return $parser;
    }

    /**
     * Get Email Format
     */
    private function getEmailFormat(CustomerMessageBodyFormat $format): string
    {
        switch ($format) {
            case CustomerMessageBodyFormat::Plaintext:
                return Message::MESSAGE_TEXT;
            case CustomerMessageBodyFormat::Html:
                return Message::MESSAGE_HTML;
            default:
                throw new InvalidArgumentException('Unsupported customer message body format: ' . $format->name);
        }
    }

    /**
     * Get Mailer Config
     */
    private function getMailerConfig(CustomerMessageType $type): string
    {
        switch ($type) {
            case CustomerMessageType::Email:
                return 'default';
            case CustomerMessageType::EmailContracts:
                return 'contracts';
            case CustomerMessageType::EmailInvoices:
                return 'invoices';
            case CustomerMessageType::EmailSupport:
                return 'support';
            default:
                throw new InvalidArgumentException('Unsupported customer message type: ' . $type->name);
        }
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
        $customerMessagesTable = $this->fetchTable(CustomerMessagesTable::class);

        /** @var iterable<\App\Model\Entity\CustomerMessage> $emailMessages */
        $emailMessages = $customerMessagesTable
            ->find()
            ->where([
                'CustomerMessages.type IN' => [
                    CustomerMessageType::Email,
                    CustomerMessageType::EmailContracts,
                    CustomerMessageType::EmailInvoices,
                    CustomerMessageType::EmailSupport,
                ],
                'CustomerMessages.direction' => CustomerMessageDirection::Outgoing,
                'CustomerMessages.delivery_status IN' => [
                    CustomerMessageDeliveryStatus::Pending,
                ],
                'CustomerMessages.created >=' =>
                    DateTime::now()->subDays((int)$args->getOption('maximum_message_age')),
            ])
            ->orderBy([
                'CustomerMessages.created',
            ])
            ->limit((int)$args->getOption('limit'))
            ->all();

        $io->info(__('Processing email messages:'));

        foreach ($emailMessages as $emailMessage) {
            // Submit messages that have not yet been processed
            if ($emailMessage->delivery_status == CustomerMessageDeliveryStatus::Pending) {
                // prepare message object
                $mailer = new Mailer($this->getMailerConfig($emailMessage->type));
                $mailer->setEmailFormat($this->getEmailFormat($emailMessage->body_format));
                $mailer->setTo($emailMessage->recipients);
                $mailer->setSubject($emailMessage->subject);

                try {
                    // add attachments
                    if (is_array($emailMessage->attachments)) {
                        $mailer->setAttachments($emailMessage->attachments);
                    }

                    // send message
                    $mailer->deliver($emailMessage->body);

                    // info to console
                    $io->info(__(
                        'Message with ID {0} was sent with identifier: {1}',
                        $emailMessage->id,
                        $mailer->getMessageId(),
                    ));
                    // patch entity data
                    $emailMessage->processed = DateTime::now();
                    $emailMessage->identifier = $mailer->getMessageId();
                    $emailMessage->delivery_status = CustomerMessageDeliveryStatus::Sent;
                    $customerMessagesTable->saveOrFail($emailMessage);
                } catch (Throwable $e) {
                    // log error and continue processing
                    Log::error('Error sending email message with ID ' . $emailMessage->id . ': ' . $e->getMessage());
                    $io->error(__('Error sending message with ID {0}: {1}', $emailMessage->id, $e->getMessage()));

                    // try to send a notification of the problem to mail (if it fails it will crash)
                    $errorMailer = new Mailer('default');

                    foreach (explode(' ', (string)env('REPORT_EMAILS')) as $email) {
                        $errorMailer->addTo($email);
                    }

                    $errorMailer->setSubject(__('Error sending email message with ID {0}', $emailMessage->id));

                    $errorMailer->deliver(__(
                        'Error sending message with ID {0}: {1}',
                        $emailMessage->id,
                        $e->getMessage(),
                    ));

                    unset($errorMailer);
                }

                // sleep for a while to slow down the sending
                sleep(max(0, rand((int)$args->getOption('wait_min'), (int)$args->getOption('wait_max'))));
            }
        }
        $io->info(__('Done'));
    }
}
