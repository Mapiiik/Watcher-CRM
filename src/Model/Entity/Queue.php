<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Settings\Utility\Settings;

/**
 * Queue Entity
 *
 * @property string $id
 * @property int $nid
 * @property string $name
 * @property string|null $caption
 * @property int|null $fup_limit
 * @property int|null $data_limit
 * @property int|null $overlimit_fragment
 * @property int|null $overlimit_cost
 * @property int|null $service_type_id
 * @property int|null $speed_down
 * @property int|null $speed_up
 * @property int|null $speed_down_common
 * @property int|null $speed_up_common
 * @property int|null $speed_down_minimum
 * @property int|null $speed_up_minimum
 * @property string|null $cto_category
 *
 * @property \App\Model\Entity\ServiceType $service_type
 * @property \App\Model\Entity\Service[] $services
 */
class Queue extends AppEntity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'created' => true,
        'created_by' => true,
        'modified' => true,
        'modified_by' => true,
        'name' => true,
        'caption' => true,
        'fup_limit' => true,
        'data_limit' => true,
        'overlimit_fragment' => true,
        'overlimit_cost' => true,
        'service_type_id' => true,
        'speed_down' => true,
        'speed_up' => true,
        'speed_down_common' => true,
        'speed_up_common' => true,
        'speed_down_minimum' => true,
        'speed_up_minimum' => true,
        'cto_category' => true,
        'service_type' => true,
        'services' => true,
    ];

    /**
     * Setting keys holding the coefficients an underived speed falls back to.
     */
    private const COEFFICIENT_COMMON = 'core.documents.contracts.summary.speed_coefficients.common';
    private const COEFFICIENT_MINIMUM = 'core.documents.contracts.summary.speed_coefficients.minimum';

    /**
     * Coefficients the settings file ships, repeated so an unbooted application still derives.
     */
    private const DEFAULT_COMMON = 0.6;
    private const DEFAULT_MINIMUM = 0.3;

    /**
     * Advertised download speed in kbps, which is also the maximum one.
     *
     * @return int|null
     */
    public function getSpeedDown(): ?int
    {
        return $this->speed_down;
    }

    /**
     * Advertised upload speed in kbps, which is also the maximum one.
     *
     * @return int|null
     */
    public function getSpeedUp(): ?int
    {
        return $this->speed_up;
    }

    /**
     * Commonly available download speed in kbps.
     *
     * @param float|null $coefficient Share of the advertised speed to fall back to, or null for the configured one
     * @return int|null
     */
    public function getSpeedDownCommon(?float $coefficient = null): ?int
    {
        return $this->speed_down_common
            ?? $this->derive($this->speed_down, $coefficient, self::COEFFICIENT_COMMON, self::DEFAULT_COMMON);
    }

    /**
     * Commonly available upload speed in kbps.
     *
     * @param float|null $coefficient Share of the advertised speed to fall back to, or null for the configured one
     * @return int|null
     */
    public function getSpeedUpCommon(?float $coefficient = null): ?int
    {
        return $this->speed_up_common
            ?? $this->derive($this->speed_up, $coefficient, self::COEFFICIENT_COMMON, self::DEFAULT_COMMON);
    }

    /**
     * Minimum download speed in kbps.
     *
     * @param float|null $coefficient Share of the advertised speed to fall back to, or null for the configured one
     * @return int|null
     */
    public function getSpeedDownMinimum(?float $coefficient = null): ?int
    {
        return $this->speed_down_minimum
            ?? $this->derive($this->speed_down, $coefficient, self::COEFFICIENT_MINIMUM, self::DEFAULT_MINIMUM);
    }

    /**
     * Minimum upload speed in kbps.
     *
     * @param float|null $coefficient Share of the advertised speed to fall back to, or null for the configured one
     * @return int|null
     */
    public function getSpeedUpMinimum(?float $coefficient = null): ?int
    {
        return $this->speed_up_minimum
            ?? $this->derive($this->speed_up, $coefficient, self::COEFFICIENT_MINIMUM, self::DEFAULT_MINIMUM);
    }

    /**
     * Take a share of an advertised speed.
     *
     * A caller that passes the coefficient never reaches the configuration, which is what keeps
     * these usable where the application has not been booted. The literal beside the setting key
     * is the same value the settings file ships.
     *
     * @param int|null $advertised Advertised speed in kbps
     * @param float|null $coefficient Coefficient given by the caller, or null to read the configured one
     * @param string $key Setting key holding the coefficient
     * @param float $default Coefficient to use when nothing is configured
     * @return int|null
     */
    private function derive(?int $advertised, ?float $coefficient, string $key, float $default): ?int
    {
        if ($advertised === null) {
            return null;
        }

        $coefficient ??= (float)Settings::get($key, $default);

        return (int)round($advertised * $coefficient);
    }
}
