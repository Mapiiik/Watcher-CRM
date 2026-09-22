<?php
declare(strict_types=1);

namespace App\RegulatoryReporting\Cz;

use App\Model\Entity\Billing;
use App\RegulatoryReporting\ConnectionPoint;

/**
 * One line of ČTÚ's report of address points, worked out from the point.
 */
final class CtuConnectionPointRow
{
    /**
     * @param string|null $reference The address point in RÚIAN.
     * @param \App\RegulatoryReporting\Cz\CtuTechnologyCategory $category The annex it is filed under.
     * @param int $activeConnections The connections active at it.
     * @param int $activeNonBusinessConnections Those of them that are households'.
     * @param array<string, int> $activeSpeedBands The active ones by band of the common speed.
     * @param int $availableConnections The connections that could be there.
     * @param \App\RegulatoryReporting\Cz\CtuSpeedInterval $effectiveDownload Commonly available down.
     * @param \App\RegulatoryReporting\Cz\CtuSpeedInterval $effectiveUpload Commonly available up.
     * @param \App\RegulatoryReporting\Cz\CtuSpeedInterval $maximalDownload Reachable down.
     * @param \App\RegulatoryReporting\Cz\CtuSpeedInterval $maximalUpload Reachable up.
     * @param bool $vhcn Whether it is a very high capacity network.
     * @param string|null $address The address as the registry writes it.
     */
    public function __construct(
        public readonly ?string $reference,
        public readonly CtuTechnologyCategory $category,
        public readonly int $activeConnections,
        public readonly int $activeNonBusinessConnections,
        public readonly array $activeSpeedBands,
        public readonly int $availableConnections,
        public readonly CtuSpeedInterval $effectiveDownload,
        public readonly CtuSpeedInterval $effectiveUpload,
        public readonly CtuSpeedInterval $maximalDownload,
        public readonly CtuSpeedInterval $maximalUpload,
        public readonly bool $vhcn,
        public readonly ?string $address,
    ) {
    }

    /**
     * @param \App\RegulatoryReporting\ConnectionPoint $point The point.
     * @return self
     */
    public static function fromPoint(ConnectionPoint $point): self
    {
        $category = CtuTechnologyCategory::from($point->group);
        $download = $point->fastestDownloadProfile();
        $upload = $point->fastestUploadProfile();

        $bands = [];
        foreach ($point->billings as $billing) {
            $band = self::activeBandOf($billing)->value;
            $bands[$band] = ($bands[$band] ?? 0) + 1;
        }

        return new self(
            reference: $point->reportedReference,
            category: $category,
            activeConnections: $point->activeConnections(),
            activeNonBusinessConnections: $point->activeNonBusinessConnections(),
            activeSpeedBands: $bands,
            availableConnections: $point->activeConnections(),
            effectiveDownload: CtuSpeedInterval::of($download?->getSpeedDownCommon(), $category),
            effectiveUpload: CtuSpeedInterval::of($upload?->getSpeedUpCommon(), $category),
            maximalDownload: CtuSpeedInterval::of($download?->getSpeedDown(), $category),
            maximalUpload: CtuSpeedInterval::of($upload?->getSpeedUp(), $category),
            vhcn: $point->isVhcn(),
            address: $point->formattedAddress,
        );
    }

    /**
     * How many active connections fall in a band.
     *
     * @param \App\RegulatoryReporting\Cz\CtuActiveSpeedBand $band Which.
     * @return int|null Null when none do, the table leaving the cell empty.
     */
    public function activeIn(CtuActiveSpeedBand $band): ?int
    {
        return $this->activeSpeedBands[$band->value] ?? null;
    }

    /**
     * @param \App\Model\Entity\Billing $billing An active connection.
     * @return \App\RegulatoryReporting\Cz\CtuActiveSpeedBand
     */
    private static function activeBandOf(Billing $billing): CtuActiveSpeedBand
    {
        return CtuActiveSpeedBand::fromKbps($billing->service?->connection_profile?->getSpeedDownCommon());
    }
}
