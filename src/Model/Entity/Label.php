<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Label Entity
 *
 * @property \Cake\I18n\DateTime|null $created
 * @property string|null $created_by
 * @property \App\Model\Entity\AppUser|null $creator
 * @property \Cake\I18n\DateTime|null $modified
 * @property string|null $modified_by
 * @property \App\Model\Entity\AppUser|null $modifier
 * @property string $id
 * @property int $nid
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
     * @var array<string, bool>
     */
    protected array $_accessible = [
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
