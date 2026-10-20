<?php
declare(strict_types=1);

namespace Radius\Check;

use Override;

/**
 * An active RADIUS account in another group than the one its tariff names.
 *
 * The customer is then served a speed they are not paying for, one way or the other.
 */
class RadiusGroupMismatchCheck extends AbstractRadiusCheck
{
    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'radius_group_mismatch';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __d('radius', 'RADIUS Group Not Matching the Tariff');
    }

    /**
     * @return string
     */
    #[Override]
    public function emptyMessage(): string
    {
        return __d('radius', 'Every active RADIUS account is in the group its tariff names.');
    }

    /**
     * @param \Radius\Check\TariffComparison $comparison The comparison.
     * @return list<\Radius\Check\AccountFinding>
     */
    #[Override]
    protected function findings(TariffComparison $comparison): array
    {
        return $comparison->mismatched();
    }
}
