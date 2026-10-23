<?php
declare(strict_types=1);

namespace App\Agent\Dto;

/**
 * What the agent did to a firewall address list on one router.
 *
 * Only what changed is listed. An entry that was already as asked is counted and nothing more,
 * so a nightly run that changes nothing has nothing to say.
 */
final readonly class AddressListChanges
{
    /**
     * @param string|null $host The router.
     * @param string|null $transport How the agent talked to it, `api-ssl` or the unencrypted `api`.
     * @param list<\App\Agent\Dto\AddressListEntry> $added Entries put on the list.
     * @param list<\App\Agent\Dto\AddressListEntry> $updated Entries whose comment was corrected or that were enabled.
     * @param list<\App\Agent\Dto\AddressListEntry> $removed Entries taken off, as the router had them.
     * @param int $unchanged Entries that were already as asked.
     * @param array<string, mixed> $raw The answer as it arrived.
     */
    public function __construct(
        public ?string $host = null,
        public ?string $transport = null,
        public array $added = [],
        public array $updated = [],
        public array $removed = [],
        public int $unchanged = 0,
        public array $raw = [],
    ) {
    }
}
