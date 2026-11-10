<?php
declare(strict_types=1);

namespace Radius\Check;

use Override;

/**
 * An active RADIUS account handed more than one IPv4 or more than one IPv6 address.
 *
 * The router takes one address of each family, so the customer may end up on another address than
 * the one meant, typically one left on the contract beside a newer one.
 */
class RadiusCompetingAddressesCheck extends AbstractRadiusCheck
{
    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'radius_competing_addresses';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __d('radius', 'Active RADIUS Account With More Than One IP Address');
    }

    /**
     * @return string
     */
    #[Override]
    protected function nothingFound(): string
    {
        return __d('radius', 'No active RADIUS account is handed more than one IPv4 or IPv6 address.');
    }

    /**
     * @param \Radius\Check\TariffComparison $comparison The comparison.
     * @return list<\Radius\Check\AccountFinding>
     */
    #[Override]
    protected function findings(TariffComparison $comparison): array
    {
        return $comparison->withCompetingAddresses();
    }
}
