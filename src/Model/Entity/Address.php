<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Address Entity
 *
 * @property \Cake\I18n\DateTime|null $created
 * @property string|null $created_by
 * @property \App\Model\Entity\AppUser|null $creator
 * @property \Cake\I18n\DateTime|null $modified
 * @property string|null $modified_by
 * @property \App\Model\Entity\AppUser|null $modifier
 * @property string $id
 * @property int $nid
 * @property int $type
 * @property int $number_type
 * @property string $customer_id
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
 * @property int|null $ruian_gid
 * @property float|null $gps_x
 * @property float|null $gps_y
 * @property string|null $note
 * @property bool $manual_coordinate_setting
 *
 * @property \App\Model\Entity\Customer $customer
 * @property \App\Model\Entity\Country $country
 *
 * @property string $full_name
 * @property string $name
 * @property string $address
 * @property string $street_and_number
 * @property string $zip_and_city
 * @property string $full_address
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
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'created' => true,
        'created_by' => true,
        'modified' => true,
        'modified_by' => true,
        'type' => true,
        'number_type' => true,
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
        'ruian_gid' => true,
        'gps_x' => true,
        'gps_y' => true,
        'note' => true,
        'manual_coordinate_setting' => true,
        'customer' => true,
        'country' => true,
    ];

    /**
     * getter for full name of person
     *
     * @return string
     */
    protected function _getFullName(): string
    {
        $full_name = '';

        if (isset($this->title)) {
            $full_name .= $this->title;
        }
        if (isset($this->first_name)) {
            if ($full_name <> '') {
                $full_name .= ' ';
            }
            $full_name .= $this->first_name;
        }
        if (isset($this->last_name)) {
            if ($full_name <> '') {
                $full_name .= ' ';
            }
            $full_name .= $this->last_name;
        }
        if (isset($this->suffix)) {
            if ($full_name <> '') {
                $full_name .= ' ';
            }
            $full_name .= $this->suffix;
        }

        return $full_name;
    }

    /**
     * getter for full name with company
     *
     * @return string
     */
    protected function _getName(): string
    {
        $name = '';

        if (isset($this->company)) {
            $name .= '[' . $this->company . ']';
        }
        if ($this->full_name <> '') {
            if ($name <> '') {
                $name .= ' ';
            }
            $name .= $this->full_name;
        }

        return $name;
    }

    /**
     * getter for address without company/name
     *
     * @return string
     */
    protected function _getAddress(): string
    {
        $address = '';

        $address .= $this->street_and_number;
        $address .= ', ' . $this->zip_and_city;

        return $address;
    }

    /**
     * getter for street and object number line
     *
     * @return string
     */
    protected function _getStreetAndNumber(): string
    {
        $street_and_number = '';

        if (isset($this->street)) {
                $street_and_number .= $this->street . ' ' . $this->number;
        } elseif (isset($this->number)) {
                $street_and_number .= $this->number_type == 1 ? 'č.ev. ' : 'č.p. ' . $this->number;
        }

        return $street_and_number;
    }

    /**
     * getter for zip and city line
     *
     * @return string
     */
    protected function _getZipAndCity(): string
    {
        $zip_and_city = '';

        if (isset($this->zip)) {
            $zip_and_city .= substr($this->zip, 0, 3) . ' ' . substr($this->zip, 3, 2);
        }

        if (isset($this->city)) {
            $zip_and_city .= ' ' . $this->city;
        }

        return $zip_and_city;
    }

    /**
     * getter for address with company/name
     *
     * @return string
     */
    protected function _getFullAddress(): string
    {
        $address = '';

        $address .= $this->name;
        $address .= ', ';
        $address .= $this->address;

        return $address;
    }

    /**
     * Get address type options method
     *
     * @return array<int, string>
     */
    public function getTypeOptions(): array
    {
        return [
            0 => __('Installation Address'),
            1 => __('Billing Address'),
            2 => __('Delivery Address'),
            3 => __('Permanent Address'),
        ];
    }

    /**
     * Get address type name method
     *
     * @return string
     */
    public function getTypeName(): string
    {
        return $this->getTypeOptions()[$this->type] ?? (string)$this->type;
    }

    /**
     * Get number type options method
     *
     * @return array<int, string>
     */
    public function getNumberTypeOptions(): array
    {
        return [
            0 => __('House Number'),
            1 => __('Registration Number'),
        ];
    }

    /**
     * Get number type name method
     *
     * @return string
     */
    public function getNumberTypeName(): string
    {
        return $this->getNumberTypeOptions()[$this->number_type] ?? (string)$this->number_type;
    }
}
