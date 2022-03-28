<?php
declare(strict_types=1);

namespace Ruian\Model\Entity;

use Cake\ORM\Entity;

/**
 * Address Entity
 *
 * @property int $kod_adm
 * @property int|null $obec_kod
 * @property string|null $obec_nazev
 * @property int|null $momc_kod
 * @property string|null $momc_nazev
 * @property int|null $mop_kod
 * @property string|null $mop_nazev
 * @property int|null $cast_obce_kod
 * @property string|null $cast_obce_nazev
 * @property int|null $ulice_kod
 * @property string|null $ulice_nazev
 * @property string|null $typ_so
 * @property int|null $cislo_domovni
 * @property int|null $cislo_orientacni
 * @property string|null $cislo_orientacni_znak
 * @property int|null $psc
 * @property \Cake\I18n\FrozenDate|null $plati_od
 * @property string|null $geometry
 * @property string|null $geometry_jtsk
 * @property string $address
 * @property string $street_and_number
 * @property string $zip_and_city
 * @property float|null $gps_x
 * @property float|null $gps_y
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
     * @var array<bool>
     */
    protected $_accessible = [
        'obec_kod' => true,
        'obec_nazev' => true,
        'momc_kod' => true,
        'momc_nazev' => true,
        'mop_kod' => true,
        'mop_nazev' => true,
        'cast_obce_kod' => true,
        'cast_obce_nazev' => true,
        'ulice_kod' => true,
        'ulice_nazev' => true,
        'typ_so' => true,
        'cislo_domovni' => true,
        'cislo_orientacni' => true,
        'cislo_orientacni_znak' => true,
        'psc' => true,
        'plati_od' => true,
        'geometry' => true,
        'geometry_jtsk' => true,
    ];

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

        if (!empty($this->ulice_nazev)) {
                $street_and_number .= $this->ulice_nazev . ' ' . $this->cislo_domovni;
        } else {
                $street_and_number .= $this->typ_so . ' ' . $this->cislo_domovni;
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

        if (!empty($this->psc)) {
            $zip_and_city .= substr((string)$this->psc, 0, 3) . ' ' . substr((string)$this->psc, 3, 2);
        }

        if (!empty($this->obec_nazev)) {
            $zip_and_city .= ' ' . $this->obec_nazev;
        }

        if (!empty($this->cast_obce_nazev) && $this->cast_obce_nazev <> $this->obec_nazev) {
            $zip_and_city .= ' - ' . $this->cast_obce_nazev;
        }

        return $zip_and_city;
    }
}
