<?php
declare(strict_types=1);

namespace App\Command\Traits;

use Cake\Console\ConsoleIo;
use Cake\Http\ServerRequest;
use Cake\Log\Log;

trait FlashMessageHandlerTrait
{
    /**
     * Handles flash messages.
     *
     * @param \Cake\Http\ServerRequest $request The server request.
     * @param \Cake\Console\ConsoleIo $io The console io.
     * @return void
     */
    protected function handleFlashMessages(ServerRequest $request, ConsoleIo $io): void
    {
        // Consume flash messages from the request
        $flashMessages = $request->getFlash()->consume('flash');

        // Process each flash message
        foreach ($flashMessages as $flashMessage) {
            $this->handleFlashMessage($io, $flashMessage);
        }
    }

    /**
     * Handles a single flash message.
     *
     * @param \Cake\Console\ConsoleIo $io The console io.
     * @param array $flashMessage The flash message.
     * @return void
     */
    protected function handleFlashMessage(ConsoleIo $io, array $flashMessage): void
    {
        // Extract the message type from the flash message element
        $type = str_replace('flash/', '', $flashMessage['element']);

        // Check if a method for this message type exists in the console io
        if (method_exists($io, $type)) {
            // Call the specific method for this message type
            $io->{$type}($flashMessage['message']);
        } else {
            // If the method doesn't exist, use a default method
            $io->info($flashMessage['message']);

            // Log a warning about the unknown message type
            Log::warning("Unknown flash message type: $type");
        }
    }
}
