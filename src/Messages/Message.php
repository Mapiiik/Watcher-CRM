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
     * Constructor
     */
    public function __construct(string $type, string $message)
    {
        $this->type = $type;
        $this->text = $message;
    }
}
