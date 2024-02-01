<?php
declare(strict_types=1);

namespace App\Command\Traits;

use App\Messages\Message;
use Cake\Console\ConsoleIo;
use Cake\Log\Log;

trait MessageHandlerTrait
{
    /**
     * Handles messages.
     *
     * @param array<\App\Messages\Message> $messages
     * @param \Cake\Console\ConsoleIo $io The console io.
     * @return void
     */
    protected function handleMessages(array $messages, ConsoleIo $io): void
    {
        // Process each message
        foreach ($messages as $message) {
            $this->handleMessage($message, $io);
        }
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
            Log::warning("Unknown message type: {$message->type}");
        }
    }
}
