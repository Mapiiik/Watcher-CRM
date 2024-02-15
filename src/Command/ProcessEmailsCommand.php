<?php
declare(strict_types=1);

namespace App\Command;

use App\Model\Enum\CustomerMessageBodyFormat;
use App\Model\Enum\CustomerMessageDeliveryStatus;
use App\Model\Enum\CustomerMessageDirection;
use App\Model\Enum\CustomerMessageType;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
use Cake\Mailer\Message;
use Exception;
use InvalidArgumentException;

/**
 * Process Emails command.
 */
class ProcessEmailsCommand extends Command
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
                throw new InvalidArgumentException('Invalid email format: ' . $format);
        }
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
        /** @var \App\Model\Table\CustomerMessagesTable $customerMessagesTable */
        $customerMessagesTable = $this->fetchTable('CustomerMessages');

        /** @var iterable<\App\Model\Entity\CustomerMessage> $emailMessages */
        $emailMessages = $customerMessagesTable
            ->find()
            ->where([
                'CustomerMessages.type' => CustomerMessageType::Email,
                'CustomerMessages.direction' => CustomerMessageDirection::Outgoing,
                'CustomerMessages.delivery_status IN' => [
                    CustomerMessageDeliveryStatus::Pending,
                ],
            ])
            ->limit(50)
            ->all();

        $io->info(__('Processing email messages:'));

        foreach ($emailMessages as $emailMessage) {
            // Submit messages that have not yet been processed
            if ($emailMessage->delivery_status == CustomerMessageDeliveryStatus::Pending) {
                // prepare message object
                $mailer = new Mailer();
                $mailer->setEmailFormat($this->getEmailFormat($emailMessage->body_format));
                $mailer->setTo($emailMessage->recipients);
                $mailer->setSubject($emailMessage->subject);

                try {
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
                } catch (Exception $e) {
                    // log error and abort processing
                    Log::error('Error sending message with ID ' . $emailMessage->id . ': ' . $e->getMessage());
                    $io->abort(__('Error sending message with ID {0}: {1}', $emailMessage->id, $e->getMessage()));
                }

                // sleep for a while to slow down the sending
                sleep(rand(1, 5));
            }

            // saving the entity to DB if changes have been made
            if ($emailMessage->isDirty()) {
                $customerMessagesTable->saveOrFail($emailMessage);
            }
        }
        $io->info(__('Done'));
    }
}
