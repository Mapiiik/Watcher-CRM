<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Address Entity
 *
 * @property int $id
 * @property int $type
 * @property int $customer_id
 * @property string|null $title
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $suffix
 * @property string|null $company
 * @property string|null $street
 * @property string|null $number
 * @property string|null $city
 * @property string|null $zip
 * @property int $country_id
 * @property int|null $created_by
 * @property \Cake\I18n\FrozenTime|null $created
 * @property int|null $modified_by
 * @property \Cake\I18n\FrozenTime|null $modified
 * @property int|null $ruian_gid
 * @property float|null $gpsx
 * @property float|null $gpsy
 *
 * @property \App\Model\Entity\Customer $customer
 * @property \App\Model\Entity\Country $country
 */
class Address extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        'type' => true,
        'customer_id' => true,
        'title' => true,
        'first_name' => true,
        'last_name' => true,
        'suffix' => true,
        'company' => true,
        'street' => true,
        'number' => true,
        'city' => true,
        'zip' => true,
        'country_id' => true,
        'created_by' => true,
        'created' => true,
        'modified_by' => true,
        'modified' => true,
        'ruian_gid' => true,
        'gpsx' => true,
        'gpsy' => true,
        'customer' => true,
        'country' => true,
    ];
}
