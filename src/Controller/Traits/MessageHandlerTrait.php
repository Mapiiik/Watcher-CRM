<?php
declare(strict_types=1);

namespace App\Controller\Traits;

use App\Messages\Message;
use App\Messages\Messages;
use Cake\Log\Log;
use Cake\View\Exception\MissingElementException;

trait MessageHandlerTrait
{
    /**
     * Flashes everything a layer below has said, and empties the buffer behind itself.
     *
     * The buffer is taken rather than read, so that whoever handed it over cannot hand the same
     * messages over twice - the buffer is static and outlives every instance of it.
     *
     * @param \App\Messages\Messages $messages The buffer to drain.
     * @return void
     */
    protected function handleMessages(Messages $messages): void
    {
        // Process each message
        foreach ($messages->getMessages() as $message) {
            $this->handleMessage($message);
        }

        $messages->clear();
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
            $this->Flash->{$message->type}($message->text, $message->options);
        } catch (MissingElementException) {
            // If the method doesn't exist, use a default method
            $this->Flash->info($message->text, $message->options);

            // Log a warning about the unknown message type
            Log::warning('Unknown message type: ' . $message->type);
        }
    }
}
