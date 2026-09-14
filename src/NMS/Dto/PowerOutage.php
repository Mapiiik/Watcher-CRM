<?php
declare(strict_types=1);

namespace App\NMS\Dto;

use App\Model\Enum\OutageCertainty;

/**
 * One planned outage over one of the network's masts, as the network management system reports it.
 *
 * The outage itself is the distributor's word and the match to a mast is the network management
 * system's guess at it, so both travel here with the grounds beside them: how sure the match is
 * and what it was made on. A number this application cannot work out for itself travels too -
 * how many customer connections hang below the mast, its own and every mast fed from it. Which
 * masts feed which is not written down here, so it has to arrive already counted.
 *
 * Read only, and never kept: what is here is whatever the last reading said.
 */
final readonly class PowerOutage
{
    /**
     * @param string $accessPointId The number the network management system keeps the mast under.
     * @param string|null $accessPointName What the mast is called.
     * @param int $connections Active customer connections below the mast, its own and those fed from it.
     * @param string|null $beginsAt When the power goes off, as the NMS wrote it.
     * @param string|null $endsAt When it is expected back.
     * @param \App\Model\Enum\OutageCertainty|null $certainty How much the match is worth, or nothing where
     *   the other application said a word this one does not know.
     * @param string|null $matchedBy What the match was made on - the supply point, an address, a street.
     * @param string|null $matchNote The address or street the match was made on, spelled out.
     * @param string|null $summary Where the distributor says the power goes off.
     * @param string|null $announcementUrl Where the distributor published it.
     * @param array<string, mixed> $raw The outage as it arrived.
     */
    public function __construct(
        public string $accessPointId,
        public ?string $accessPointName = null,
        public int $connections = 0,
        public ?string $beginsAt = null,
        public ?string $endsAt = null,
        public ?OutageCertainty $certainty = null,
        public ?string $matchedBy = null,
        public ?string $matchNote = null,
        public ?string $summary = null,
        public ?string $announcementUrl = null,
        public array $raw = [],
    ) {
    }

    /**
     * Whether the outage is known to be about this mast rather than guessed at from the addresses
     * around it.
     *
     * @return bool
     */
    public function isCertain(): bool
    {
        return $this->certainty === OutageCertainty::Certain;
    }
}
