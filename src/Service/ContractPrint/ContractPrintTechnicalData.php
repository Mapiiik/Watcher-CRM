<?php
declare(strict_types=1);

namespace App\Service\ContractPrint;

/**
 * Data Transfer Object for technical details used in contract printouts.
 *
 * This DTO encapsulates technical information required mainly
 * for handover protocol documents.
 *
 * It intentionally contains only simple scalar values and does not
 * perform any validation or data loading by itself.
 */
final class ContractPrintTechnicalData
{
    /**
     * Name of the access point.
     *
     * @var string|null
     */
    public ?string $accessPoint = null;

    /**
     * RADIUS username.
     *
     * @var string|null
     */
    public ?string $radiusUsername = null;

    /**
     * RADIUS password.
     *
     * @var string|null
     */
    public ?string $radiusPassword = null;
}
