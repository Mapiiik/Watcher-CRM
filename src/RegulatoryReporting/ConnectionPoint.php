<?php
declare(strict_types=1);

namespace App\RegulatoryReporting;

use App\Model\Entity\AvailableConnection;
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
     * @param list<\App\Model\Entity\AvailableConnection> $available The connections recorded as
     *      there to be had, whether or not anybody is on them.
     * @param string|null $reportedReference The reference to report, null when the registry does
     *      not know the one on file.
     * @param string|null $formattedAddress The address as the registry writes it.
     */
    public function __construct(
        public readonly string $group,
        public readonly string $registrySource,
        public readonly string $registryReference,
        public array $billings = [],
        public array $available = [],
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
     * Whether a customer can be connected at the point, being there already or not.
     *
     * @return bool
     */
    public function isCovered(): bool
    {
        return $this->billings !== [] || $this->available !== [];
    }

    /**
     * How many connections the point can take, as far as is known: at least as many as are on it.
     *
     * @return int
     */
    public function availableConnections(): int
    {
        return max(count($this->billings), count($this->available));
    }

    /**
     * The fastest download the point can reach, by its tariffs or by what the line carries.
     *
     * @return int|null
     */
    public function maximalSpeedDown(): ?int
    {
        return $this->greatest(
            $this->fastestDownloadProfile()?->getSpeedDown(),
            array_map(fn(AvailableConnection $connection): int => $connection->speed_down_max, $this->available),
        );
    }

    /**
     * The fastest upload the point can reach.
     *
     * @return int|null
     */
    public function maximalSpeedUp(): ?int
    {
        return $this->greatest(
            $this->fastestUploadProfile()?->getSpeedUp(),
            array_map(fn(AvailableConnection $connection): int => $connection->speed_up_max, $this->available),
        );
    }

    /**
     * The download commonly available at the fastest the point can reach.
     *
     * @return int|null
     */
    public function effectiveSpeedDown(): ?int
    {
        return $this->greatest(
            $this->fastestDownloadProfile()?->getSpeedDownCommon(),
            array_map(fn(AvailableConnection $connection): ?int => $connection->getSpeedDownCommon(), $this->available),
        );
    }

    /**
     * The upload commonly available at the fastest the point can reach.
     *
     * @return int|null
     */
    public function effectiveSpeedUp(): ?int
    {
        return $this->greatest(
            $this->fastestUploadProfile()?->getSpeedUpCommon(),
            array_map(fn(AvailableConnection $connection): ?int => $connection->getSpeedUpCommon(), $this->available),
        );
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
        foreach ($this->technologies() as $technology) {
            if ($technology->isVhcn()) {
                return true;
            }
        }

        return false;
    }

    /**
     * The technologies at the point, active and recorded alike.
     *
     * @return list<\App\Model\Enum\AccessTechnology>
     */
    public function technologies(): array
    {
        $technologies = [];
        foreach ($this->billings as $billing) {
            $technology = $billing->service?->connection_profile?->access_technology;
            if ($technology !== null) {
                $technologies[$technology->value] = $technology;
            }
        }
        foreach ($this->available as $connection) {
            $technologies[$connection->access_technology->value] = $connection->access_technology;
        }

        return array_values($technologies);
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
     * The greatest of the speeds known, null when none is.
     *
     * @param int|null $fromTariffs What the tariffs on the point say.
     * @param list<int|null> $fromRecords What the recorded connections say.
     * @return int|null
     */
    private function greatest(?int $fromTariffs, array $fromRecords): ?int
    {
        $known = array_filter([$fromTariffs, ...$fromRecords], fn(?int $speed): bool => $speed !== null);

        return $known === [] ? null : max($known);
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
