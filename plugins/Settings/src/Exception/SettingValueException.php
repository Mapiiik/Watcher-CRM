<?php
declare(strict_types=1);

namespace Settings\Exception;

use RuntimeException;
use Throwable;

/**
 * Raised when a value cannot be stored as the type its setting was declared with.
 *
 * The message is shown to whoever submitted the form, so it says which value was refused and what
 * was expected of it. A type is in no position to say which setting that was - it is declared
 * without being told where it hangs - so the path is added by the service, which knows.
 */
class SettingValueException extends RuntimeException
{
    /**
     * @param string $message What was refused and what was expected of it.
     * @param string|null $path Full path of the setting, where it is known.
     * @param \Throwable|null $previous The failure this one was raised from.
     */
    public function __construct(
        string $message,
        protected ?string $path = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * The setting the refused value belongs to.
     *
     * @return string|null
     */
    public function getPath(): ?string
    {
        return $this->path;
    }
}
