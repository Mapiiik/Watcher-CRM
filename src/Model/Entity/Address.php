<?php
declare(strict_types=1);

namespace App\Model\Entity;

use App\Model\Enum\AddressNumberType;

/**
 * Address Entity
 *
 * @property string $id
 * @property int $nid
 * @property \App\Model\Enum\AddressType $type
 * @property \App\Model\Enum\AddressNumberType $number_type
 * @property string $customer_id
 * @property string|null $title
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $suffix
 * @property string|null $company
 * @property string|null $street
 * @property string|null $number
 * @property string|null $entrance
 * @property string|null $unit
 * @property string|null $city
 * @property string|null $zip
 * @property int $country_id
 * @property string|null $address_registry_reference
 * @property string|null $address_registry_source
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
 * @property string $street_and_number getter for street and object number line
 * @property string $street_and_number_extra getter for street and object number line (including entrance and unit)
 * @property string $zip_and_city
 * @property string $full_address
 */
class Address extends AppEntity
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
        'entrance' => true,
        'unit' => true,
        'city' => true,
        'zip' => true,
        'country_id' => true,
        'address_registry_reference' => true,
        'address_registry_source' => true,
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
     * getter for address without company/name (including entrance and unit)
     *
     * @return string
     */
    protected function _getAddress(): string
    {
        $address = '';

        $address .= $this->street_and_number_extra;
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
            $street_and_number .=
                $this->number_type == AddressNumberType::Registration
                    ? __d('addresses', 'Reg. No.') . ' ' . $this->number
                    : __d('addresses', 'No.') . ' ' . $this->number;
        }

        return $street_and_number;
    }

    /**
     * getter for street and object number line (including entrance and unit)
     *
     * @return string
     */
    protected function _getStreetAndNumberExtra(): string
    {
        $street_and_number = '';

        if (isset($this->street)) {
            $street_and_number .= $this->street . ' ' . $this->number;
        } elseif (isset($this->number)) {
            $street_and_number .=
                $this->number_type == AddressNumberType::Registration
                    ? __d('addresses', 'Reg. No.') . ' ' . $this->number
                    : __d('addresses', 'No.') . ' ' . $this->number;
        }

        return $street_and_number . $this->getEntranceAndUnit();
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
     * getter for address with company/name (including entrance and unit)
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
     * Getter for entrance and unit.
     *
     * @param bool $addLeadingComma Add leading comma before result.
     * @param bool $addLeadingSpace Add leading space before result.
     * @param bool $addParentheses Add parentheses around result.
     * @param string $partsSeparator Default ', '.
     * @param string $valueSeparator Default ' '.
     * @return string
     */
    public function getEntranceAndUnit(
        bool $addLeadingComma = true,
        bool $addLeadingSpace = true,
        bool $addParentheses = false,
        string $partsSeparator = ', ',
        string $valueSeparator = ' ',
    ): string {
        $parts = [];

        if (!empty($this->entrance)) {
            $parts[] = __d('addresses', 'entrance') . $valueSeparator . $this->entrance;
        }

        if (!empty($this->unit)) {
            $parts[] = __d('addresses', 'unit') . $valueSeparator . $this->unit;
        }

        if (empty($parts)) {
            return '';
        }

        $result = implode($partsSeparator, $parts);

        if ($addParentheses) {
            $result = '(' . $result . ')';
        }

        if ($addLeadingSpace) {
            $result = ' ' . $result;
        }

        if ($addLeadingComma) {
            $result = ',' . $result;
        }

        return $result;
    }
}
