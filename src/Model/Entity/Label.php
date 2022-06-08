<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Label Entity
 *
 * @property \Cake\I18n\FrozenTime|null $created
 * @property int|null $created_by
 * @property \CakeDC\Users\Model\Entity\User|null $creator
 * @property \Cake\I18n\FrozenTime|null $modified
 * @property int|null $modified_by
 * @property \CakeDC\Users\Model\Entity\User|null $modifier
 * @property int $id
 * @property string|null $name
 * @property string|null $caption
 * @property string|null $color
 * @property int|null $validity
 * @property bool $dynamic
 * @property string|null $dynamic_sql
 *
 * @property \App\Model\Entity\CustomerLabel[] $customer_labels
 */
class Label extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<bool>
     */
    protected $_accessible = [
        'created' => true,
        'created_by' => true,
        'modified' => true,
        'modified_by' => true,
        'name' => true,
        'caption' => true,
        'color' => true,
        'validity' => true,
        'dynamic' => true,
        'dynamic_sql' => true,
        'customer_labels' => true,
    ];
}
