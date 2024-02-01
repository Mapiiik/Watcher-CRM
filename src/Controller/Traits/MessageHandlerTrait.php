<?php
declare(strict_types=1);

namespace App\Controller\Traits;

use App\Messages\Message;
use Cake\Log\Log;
use Cake\View\Exception\MissingElementException;

trait MessageHandlerTrait
{
    /**
     * Handles messages.
     *
     * @param array<\App\Messages\Message> $messages
     * @return void
     */
    protected function handleMessages(array $messages): void
    {
        // Process each message
        foreach ($messages as $message) {
            $this->handleMessage($message);
        }
    }

    /**
     * Handles a single flash message.
     *
     * @param \App\Messages\Message $message The message.
     * @return void
     */
    protected function handleMessage(Message $message): void
    {
        try {
            // Call the specific method for this message type
            $this->Flash->{$message->type}($message->text);
        } catch (MissingElementException $e) {
            // If the method doesn't exist, use a default method
            $this->Flash->info($message->text);

            // Log a warning about the unknown message type
            Log::warning("Unknown message type: {$message->type}");
        }
    }
}
