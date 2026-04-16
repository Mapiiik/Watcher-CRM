<?php
declare(strict_types=1);

namespace Settings\Model\Entity;

use App\Model\Entity\AppEntity;

/**
 * Setting Entity
 *
 * @property string $id
 * @property string $plugin
 * @property string $key
 * @property array $value
 */
class Setting extends AppEntity
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
        'plugin' => true,
        'key' => true,
        'value' => true,
        'created' => true,
        'created_by' => true,
        'modified' => true,
        'modified_by' => true,
        'creator' => true,
        'modifier' => true,
    ];
}
