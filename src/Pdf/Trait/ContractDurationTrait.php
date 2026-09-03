<?php
declare(strict_types=1);

namespace App\Pdf\Trait;

use App\Model\Entity\ContractVersion;
use InvalidArgumentException;
use Settings\Utility\Settings;

/**
 * How a contract term is put into words.
 *
 * Shared by the documents that have to state the same term the same way - the contract and
 * the contract summary would otherwise describe one obligation in two wordings.
 */
trait ContractDurationTrait
{
    /**
     * Getter for contract duration text - short.
     *
     * @param int|null $duration Duration in months
     * @return string
     * @throws \InvalidArgumentException If duration is null or <= 0
     */
    protected function contractDurationBefore(?int $duration): string
    {
        if (is_null($duration) || $duration <= 0) {
            throw new InvalidArgumentException('Invalid contract duration');
        }

        if ($duration < 2) {
            return strtr(Settings::getString('core.documents.contracts.duration.short_month'), [
                '{duration}' => $duration,
            ]);
        }

        return strtr(Settings::getString('core.documents.contracts.duration.short_months'), [
            '{duration}' => $duration,
        ]);
    }

    /**
     * Getter for contract duration text - long.
     *
     * An end date makes the contract a fixed-term one, and a fixed term is its own minimum
     * period of performance. The activation fee and its clause are decided by the obligation
     * alone, so an obligation that does not reach the end would charge the fee as if nothing
     * had been promised while the document claims otherwise.
     *
     * @param \App\Model\Entity\ContractVersion $contract_version Contract version being executed
     * @return string
     * @throws \InvalidArgumentException If a fixed term is not matched by the obligation
     */
    protected function contractDuration(ContractVersion $contract_version): string
    {
        if ($contract_version->valid_until !== null) {
            if (
                $contract_version->obligation_until === null
                || !$contract_version->obligation_until->equals($contract_version->valid_until)
            ) {
                throw new InvalidArgumentException(
                    'The obligation must last until the end of a fixed-term contract',
                );
            }

            return strtr(Settings::getString('core.documents.contracts.duration.definite'), [
                '{valid_until}' => (string)$contract_version->valid_until,
            ]);
        }

        $duration = $contract_version->minimum_duration;

        if ($duration <= 0) {
            return Settings::getString('core.documents.contracts.duration.indefinite');
        }

        if ($duration < 2) {
            return strtr(Settings::getString('core.documents.contracts.duration.indefinite_with_min_month'), [
                '{duration}' => $duration,
            ]);
        }

        return strtr(Settings::getString('core.documents.contracts.duration.indefinite_with_min_months'), [
            '{duration}' => $duration,
        ]);
    }
}
