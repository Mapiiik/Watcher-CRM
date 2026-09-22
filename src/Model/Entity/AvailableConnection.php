<?php
declare(strict_types=1);

namespace App\Model\Entity;

/**
 * AvailableConnection Entity
 *
 * @property string $id
 * @property string $address_registry_source
 * @property string $address_registry_reference
 * @property string|null $address_label
 * @property float|null $gps_x
 * @property float|null $gps_y
 * @property \App\Model\Enum\AccessTechnology $access_technology
 * @property int $speed_down_max
 * @property int $speed_up_max
 * @property string|null $access_point_id
 * @property \App\Model\Enum\AvailableConnectionOrigin $origin
 * @property string|null $contract_id
 * @property \Cake\I18n\Date|null $retired
 * @property string|null $note
 * @property \Cake\I18n\DateTime|null $created
 * @property string|null $created_by
 * @property \Cake\I18n\DateTime|null $modified
 * @property string|null $modified_by
 *
 * @property \App\Model\Entity\Contract|null $contract
 */
class AvailableConnection extends AppEntity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * The origin is not among them: it is the synchronisation's to set, and editing by hand is
     * what turns it to manual.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'address_registry_source' => true,
        'address_registry_reference' => true,
        'address_label' => true,
        'gps_x' => true,
        'gps_y' => true,
        'access_technology' => true,
        'speed_down_max' => true,
        'speed_up_max' => true,
        'access_point_id' => true,
        'retired' => true,
        'note' => true,
    ];

    /**
     * The address point as the rest of the application keys it, "source|reference".
     *
     * @return string
     */
    public function registryKey(): string
    {
        return $this->address_registry_source . '|' . $this->address_registry_reference;
    }

    /**
     * The download speed commonly available, derived from the maximum the way a tariff's is.
     *
     * @return int|null
     */
    public function getSpeedDownCommon(): ?int
    {
        return (new ConnectionProfile(['speed_down' => $this->speed_down_max]))->getSpeedDownCommon();
    }

    /**
     * The upload speed commonly available, derived from the maximum the way a tariff's is.
     *
     * @return int|null
     */
    public function getSpeedUpCommon(): ?int
    {
        return (new ConnectionProfile(['speed_up' => $this->speed_up_max]))->getSpeedUpCommon();
    }
}
