<?php
declare(strict_types=1);

namespace Radius\Check;

use Override;

/**
 * An active RADIUS account holding an address its contract does not have.
 *
 * A contract left without an address keeps what its account was given, so the address may by then
 * be another contract's, and two accounts handed one address cut each other off.
 */
class RadiusAddressOutsideContractCheck extends AbstractRadiusCheck
{
    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'radius_address_outside_contract';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __d('radius', 'Active RADIUS Account With an Address Outside Its Contract');
    }

    /**
     * @return string
     */
    #[Override]
    protected function nothingFound(): string
    {
        return __d('radius', 'Every active RADIUS account keeps to the addresses of its contract.');
    }

    /**
     * @param \Radius\Check\TariffComparison $comparison The comparison.
     * @return list<\Radius\Check\AccountFinding>
     */
    #[Override]
    protected function findings(TariffComparison $comparison): array
    {
        return $comparison->outsideTheirContract();
    }
}
