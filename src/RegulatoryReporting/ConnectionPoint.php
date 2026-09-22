<?php
declare(strict_types=1);

namespace App\RegulatoryReporting;

use App\Model\Entity\Billing;
use App\Model\Entity\ConnectionProfile;
use Cake\Collection\Collection;

/**
 * One address point as a regulator counts it: the connections at it that fall in one of its
 * categories.
 */
final class ConnectionPoint
{
    /**
     * @param string $group The regulator's category the connections fall in.
     * @param string $registrySource Which national registry the address is from.
     * @param string $registryReference The address point in that registry.
     * @param list<\App\Model\Entity\Billing> $billings The connections active at it.
     * @param string|null $reportedReference The reference to report, null when the registry does
     *      not know the one on file.
     * @param string|null $formattedAddress The address as the registry writes it.
     */
    public function __construct(
        public readonly string $group,
        public readonly string $registrySource,
        public readonly string $registryReference,
        public array $billings = [],
        public ?string $reportedReference = null,
        public ?string $formattedAddress = null,
    ) {
    }

    /**
     * The connections active at the point.
     *
     * @return int
     */
    public function activeConnections(): int
    {
        return count($this->billings);
    }

    /**
     * The active connections of households, the customers that do no business.
     *
     * @return int
     */
    public function activeNonBusinessConnections(): int
    {
        return count(array_filter(
            $this->billings,
            fn(Billing $billing): bool => !$billing->customer->isBusiness(),
        ));
    }

    /**
     * The profile with the fastest download, whose speeds the point is reported as able to reach.
     *
     * @return \App\Model\Entity\ConnectionProfile|null
     */
    public function fastestDownloadProfile(): ?ConnectionProfile
    {
        return $this->fastestBy('speed_down');
    }

    /**
     * The profile with the fastest upload.
     *
     * @return \App\Model\Entity\ConnectionProfile|null
     */
    public function fastestUploadProfile(): ?ConnectionProfile
    {
        return $this->fastestBy('speed_up');
    }

    /**
     * Whether any connection at the point runs over a very high capacity network.
     *
     * @return bool
     */
    public function isVhcn(): bool
    {
        foreach ($this->billings as $billing) {
            if ($billing->service?->connection_profile?->access_technology?->isVhcn()) {
                return true;
            }
        }

        return false;
    }

    /**
     * The contract numbers behind the point, for telling the operator which ones to look at.
     *
     * @return list<string>
     */
    public function contractNumbers(): array
    {
        return array_values(array_map(
            fn(Billing $billing): string => (string)$billing->contract->number,
            $this->billings,
        ));
    }

    /**
     * @param string $field Which speed.
     * @return \App\Model\Entity\ConnectionProfile|null
     */
    private function fastestBy(string $field): ?ConnectionProfile
    {
        if ($this->billings === []) {
            return null;
        }

        // Resolved against the billing itself, so it carries no "billing." prefix.
        /** @var \App\Model\Entity\Billing $fastest */
        $fastest = (new Collection($this->billings))->max('service.connection_profile.' . $field);

        return $fastest->service?->connection_profile;
    }
}
