<?php
declare(strict_types=1);

namespace App\RegulatoryReporting\Hr;

use App\RegulatoryReporting\CsvFile;

/**
 * HAKOM's listing of the address points, one line per point and type of infrastructure, with the
 * households and the businesses on it counted by band of their contracted speed.
 *
 * The columns follow what the old system sent. The county and the municipality it had are not
 * among what the address registry gives, so they are left out rather than guessed.
 */
final class HakomConnectionPointsCsv
{
    /**
     * @param iterable<\App\RegulatoryReporting\ConnectionPoint> $points The points, grouped by type of
     *      infrastructure.
     * @param string $owner Whose the infrastructure is.
     * @return string
     */
    public static function render(iterable $points, string $owner): string
    {
        $headers = ['kb_id', 'na_ime', 'ul_ime', 'kb', 'infrastructure_owner', 'infrastructure_type'];
        foreach (['private', 'business'] as $who) {
            foreach (HakomAddressSpeedBand::cases() as $band) {
                $headers[] = $who . '_' . $band->value;
            }
        }

        $lines = [];
        foreach ($points as $point) {
            $counts = [0 => [], 1 => []];
            foreach ($point->billings as $billing) {
                $band = HakomAddressSpeedBand::fromKbps($billing->service?->connection_profile?->speed_down)->value;
                $business = (int)$billing->customer->isBusiness();
                $counts[$business][$band] = ($counts[$business][$band] ?? 0) + 1;
            }

            $line = [
                $point->reportedReference ?? $point->registryReference,
                $point->registryAddress?->city,
                $point->registryAddress?->street,
                $point->registryAddress?->houseNumber,
                $owner,
                $point->group,
            ];
            foreach ([0, 1] as $business) {
                foreach (HakomAddressSpeedBand::cases() as $band) {
                    $line[] = $counts[$business][$band->value] ?? 0;
                }
            }
            $lines[] = $line;
        }

        return CsvFile::render($headers, $lines);
    }
}
