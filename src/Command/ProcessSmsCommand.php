<?php
declare(strict_types=1);

namespace App\Command;

use AndroidSmsGateway\Client;
use AndroidSmsGateway\Domain\Message;
use AndroidSmsGateway\Domain\MessageState;
use AndroidSmsGateway\Encryptor;
use AndroidSmsGateway\Enums\ProcessState;
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
use Exception;
use InvalidArgumentException;

/**
 * Process SMS command.
 */
class ProcessSmsCommand extends Command
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
            'help' => __('Number of SMS to process.'),
            'default' => '50',
            'required' => false,
        ]);

        $parser->addOption('wait_min', [
            'help' => __('Minimum waiting time after request to send (seconds).'),
            'default' => '60',
            'required' => false,
        ]);

        $parser->addOption('wait_max', [
            'help' => __('Maximum waiting time after request to send (seconds).'),
            'default' => '90',
            'required' => false,
        ]);

        return $parser;
    }

    /**
     * Get Delivery State
     */
    private function getMessageState(MessageState $messageState): CustomerMessageDeliveryStatus
    {
        switch ($messageState->State()) {
            case ProcessState::PENDING():
                return CustomerMessageDeliveryStatus::Pending;
            case ProcessState::PROCESSED():
                return CustomerMessageDeliveryStatus::Processed;
            case ProcessState::SENT():
                return CustomerMessageDeliveryStatus::Sent;
            case ProcessState::DELIVERED():
                return CustomerMessageDeliveryStatus::Delivered;
            case ProcessState::FAILED():
                return CustomerMessageDeliveryStatus::Failed;
            default:
                throw new InvalidArgumentException('Invalid messsage state: ' . $messageState->State());
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

        /** @var iterable<\App\Model\Entity\CustomerMessage> $smsMessages */
        $smsMessages = $customerMessagesTable
            ->find()
            ->where([
                'CustomerMessages.type' => CustomerMessageType::Sms,
                'CustomerMessages.direction' => CustomerMessageDirection::Outgoing,
                'CustomerMessages.body_format' => CustomerMessageBodyFormat::Plaintext,
                'CustomerMessages.delivery_status IN' => [
                    CustomerMessageDeliveryStatus::Pending,
                    CustomerMessageDeliveryStatus::Processed,
                    CustomerMessageDeliveryStatus::Sent,
                ],
            ])
            ->orderBy([
                'CustomerMessages.created',
            ])
            ->all();

        // set send limit from option or default setting
        $send_limit = (int)$args->getOption('limit');
        // initialization of the send counter
        $send_count = 0;

        $io->info(__('Processing SMS messages:'));

        $login = env('ANDROID_SMS_GATEWAY_LOGIN');
        $password = env('ANDROID_SMS_GATEWAY_PASSWORD');
        $passphrase = env('ANDROID_SMS_GATEWAY_PASSPHRASE');
        $serverUrl = env('ANDROID_SMS_GATEWAY_URL', Client::DEFAULT_URL);

        // Prepare encryption if passphrase is set
        if (!empty($passphrase)) {
            $encryptor = new Encryptor($passphrase);
        } else {
            $encryptor = null;
        }

        // Android SMS Gateway Client
        $client = new Client(
            login: $login,
            password: $password,
            serverUrl: $serverUrl,
            encryptor: $encryptor,
        );

        foreach ($smsMessages as $smsMessage) {
            // Submit messages that have not yet been processed
            if (
                $smsMessage->delivery_status == CustomerMessageDeliveryStatus::Pending
                && $smsMessage->identifier === null
            ) {
                // do not send if we have already reached the limit
                if ($send_count >= $send_limit) {
                    continue;
                }

                // prepare message object
                $message = new Message(
                    message: $smsMessage->body,
                    phoneNumbers: $smsMessage->recipients,
                    ttl: 86400,
                );

                try {
                    // submit a message to the Android SMS gateway
                    $messageState = $client->Send($message);
                    // info to console
                    $io->info(__(
                        'Message with ID {0} was sent with identifier: {1}',
                        $smsMessage->id,
                        $messageState->ID(),
                    ));
                    // patch entity data
                    $smsMessage->processed = DateTime::now();
                    $smsMessage->identifier = $messageState->ID();
                    $smsMessage->delivery_status = $this->getMessageState($messageState);
                    $customerMessagesTable->saveOrFail($smsMessage);

                    // increase the send counter
                    $send_count++;
                } catch (Exception $e) {
                    // log error and abort processing
                    Log::error('Error sending message with ID ' . $smsMessage->id . ': ' . $e->getMessage());
                    $io->abort(__('Error sending message with ID {0}: {1}', $smsMessage->id, $e->getMessage()));
                }

                // sleep for a while to slow down the sending
                sleep(max(0, rand((int)$args->getOption('wait_min'), (int)$args->getOption('wait_max'))));
            }

            // Find out the status of individual messages
            try {
                // get message status from Android SMS gateway
                $messageState = $client->GetState($smsMessage->identifier);
                // info to console
                $io->info(__('Message status with ID {0}: {1}', $smsMessage->id, $messageState->State()));
                // patch entity data
                $smsMessage->delivery_status = $this->getMessageState($messageState);
                $customerMessagesTable->saveOrFail($smsMessage);
            } catch (Exception $e) {
                // log error and abort processing
                Log::error('Error getting message status with ID ' . $smsMessage->id . ': ' . $e->getMessage());
                $io->abort(__('Error getting message status with ID {0}: {1}', $smsMessage->id, $e->getMessage()));
            }
        }
        $io->info(__('Done'));
    }
}
