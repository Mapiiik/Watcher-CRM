<?php
declare(strict_types=1);

namespace App\Command\Traits;

use App\Messages\Message;
use App\Messages\Messages;
use Cake\Console\ConsoleIo;
use Cake\Log\Log;

trait MessageHandlerTrait
{
    /**
     * Prints everything a layer below has said, and empties the buffer behind itself.
     *
     * The buffer is taken rather than read, so that a command draining it more than once in a run
     * does not print the earlier batch again - the buffer is static and outlives every instance.
     *
     * @param \App\Messages\Messages $messages The buffer to drain.
     * @param \Cake\Console\ConsoleIo $io The console io.
     * @return void
     */
    protected function handleMessages(Messages $messages, ConsoleIo $io): void
    {
        // Process each message
        foreach ($messages->getMessages() as $message) {
            $this->handleMessage($message, $io);
        }

        $messages->clear();
    }

    /**
     * Handles a single flash message.
     *
     * @param \App\Messages\Message $message The message.
     * @param \Cake\Console\ConsoleIo $io The console io.
     * @return void
     */
    protected function handleMessage(Message $message, ConsoleIo $io): void
    {
        // Check if a method for this message type exists in the console io
        if (method_exists($io, $message->type)) {
            // Call the specific method for this message type
            $io->{$message->type}($message->text);
        } else {
            // If the method doesn't exist, use a default method
            $io->info($message->text);

            // Log a warning about the unknown message type
            Log::warning('Unknown message type: ' . $message->type);
        }
    }
}
