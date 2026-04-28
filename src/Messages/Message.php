<?php
declare(strict_types=1);

namespace App\Messages;

/**
 * Message
 */
class Message
{
    /**
     * Message Type
     */
    public string $type;

    /**
     * Message Text
     */
    public string $text;

    /**
     * Message Options
     *
     * @var array<string, mixed>
     */
    public array $options;

    /**
     * Constructor
     */
    public function __construct(string $type, string $message, array $options = [])
    {
        $this->type = $type;
        $this->text = $message;
        $this->options = $options;
    }
}
