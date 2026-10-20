<?php
declare(strict_types=1);

namespace Radius\Check;

use Override;

/**
 * An active RADIUS account on a contract with no tariff naming a group, now or within a month.
 *
 * Either the billing is missing, or the account should have been deactivated.
 */
class RadiusAccountWithoutTariffCheck extends AbstractRadiusCheck
{
    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'radius_account_without_tariff';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __d('radius', 'Active RADIUS Account Without a Tariff');
    }

    /**
     * @return string
     */
    #[Override]
    public function emptyMessage(): string
    {
        return __d('radius', 'Every active RADIUS account has a tariff naming its group.');
    }

    /**
     * @param \Radius\Check\TariffComparison $comparison The comparison.
     * @return list<\Radius\Check\AccountFinding>
     */
    #[Override]
    protected function findings(TariffComparison $comparison): array
    {
        return $comparison->withoutTariff();
    }
}
