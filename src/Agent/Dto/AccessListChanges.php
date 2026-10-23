<?php
declare(strict_types=1);

namespace App\Agent\Dto;

/**
 * What the agent took off the access lists of one access point.
 */
final readonly class AccessListChanges
{
    /**
     * @param string|null $host The access point.
     * @param string|null $transport How the agent talked to it, `api-ssl` or the unencrypted `api`.
     * @param list<\App\Agent\Dto\AccessListEntry> $removed Entries taken off.
     * @param array<string, mixed> $raw The answer as it arrived.
     */
    public function __construct(
        public ?string $host = null,
        public ?string $transport = null,
        public array $removed = [],
        public array $raw = [],
    ) {
    }
}
