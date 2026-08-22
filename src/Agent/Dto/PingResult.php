<?php
declare(strict_types=1);

namespace App\Agent\Dto;

/**
 * What came of asking the agent to ping a host.
 *
 * Reachable and lossless are two different things: a host that answers nine packets out of ten is
 * up and in trouble, and an operator wants to see the difference. The agent reports both, so both
 * are kept apart here rather than folded into one verdict.
 */
final readonly class PingResult
{
    /**
     * @param bool $reachable Whether anything came back at all.
     * @param float|null $lossPercent How much of what was sent went missing.
     * @param string|null $message What the agent said about it, where it said anything.
     * @param array<string, mixed> $raw The answer as it arrived.
     */
    public function __construct(
        public bool $reachable,
        public ?float $lossPercent = null,
        public ?string $message = null,
        public array $raw = [],
    ) {
    }

    /**
     * Whether the host answered everything it was asked.
     *
     * @return bool
     */
    public function isHealthy(): bool
    {
        return $this->reachable && $this->lossPercent === 0.0;
    }
}
