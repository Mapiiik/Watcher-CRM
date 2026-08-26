<?php
declare(strict_types=1);

namespace App\Messages;

/**
 * Message Handler
 *
 * @method void success(string $message, array<string, mixed> $options = []) Set a message using "success" element
 * @method void info(string $message, array<string, mixed> $options = []) Set a message using "info" element
 * @method void warning(string $message, array<string, mixed> $options = []) Set a message using "warning" element
 * @method void error(string $message, array<string, mixed> $options = []) Set a message using "error" element
 */
class Messages
{
    /**
     * Message Buffer
     *
     * Held per instance. Whoever produces messages owns a buffer and hands that same buffer over;
     * a shared one would let anything that never drains it leak into whoever drains next - across
     * requests in a long-running process, and across cases in a test run.
     *
     * @var array<\App\Messages\Message>
     */
    private array $messages = [];

    /**
     * Magic method for set method based on element names.
     *
     * @param string $name Message type to use.
     * @param array<array-key, mixed> $args Parameters to pass when calling `Messages::push()`.
     * @return void
     * @throws \Cake\Http\Exception\InternalErrorException If missing the flash message.
     */
    public function __call(string $name, array $args): void
    {
        $this->set($name, $args[0], $args[1] ?? []);
    }

    /**
     * Add new message to message buffer
     */
    public function set(string $type, string $message, array $options = []): void
    {
            $this->messages[] = new Message($type, $message, $options);
    }

    /**
     * Add new message to message buffer
     *
     * @return array<\App\Messages\Message>
     */
    public function getMessages(): array
    {
            return $this->messages;
    }

    /**
     * Empty the message buffer.
     *
     * A producer is used for more than one round of work - a controller action that saves twice,
     * a command that drains between steps - so whoever has handled the messages says so and the
     * next round starts empty, rather than repeating what was already shown.
     *
     * @return void
     */
    public function clear(): void
    {
            $this->messages = [];
    }
}
