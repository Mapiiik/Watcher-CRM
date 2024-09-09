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
     * @var array<\App\Messages\Message>
     */
    private static array $messages = [];

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
        $this->set($name, $args[0]);
    }

    /**
     * Add new message to message buffer
     */
    public function set(string $type, string $message): void
    {
            self::$messages[] = new Message($type, $message);
    }

    /**
     * Add new message to message buffer
     *
     * @return array<\App\Messages\Message>
     */
    public function getMessages(): array
    {
            return self::$messages;
    }
}
