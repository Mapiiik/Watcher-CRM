<?php
declare(strict_types=1);

namespace App\Agent\Dto;

/**
 * What came of asking the agent to drop a RADIUS session.
 *
 * The access server answers a disconnect with a verdict and, where it refused, with the numbers
 * RFC 5176 gives its reasons. Those numbers are kept as they came: what they mean is the caller's
 * to say, in words an operator reads.
 */
final readonly class DisconnectResult
{
    /**
     * @param bool $success Whether the session was dropped.
     * @param string|null $result What the access server answered, in its own words.
     * @param string|null $message What the agent said about it.
     * @param list<int> $errorCauses Why the access server refused, as RFC 5176 numbers them.
     * @param array<string, mixed> $raw The answer as it arrived.
     */
    public function __construct(
        public bool $success,
        public ?string $result = null,
        public ?string $message = null,
        public array $errorCauses = [],
        public array $raw = [],
    ) {
    }
}
