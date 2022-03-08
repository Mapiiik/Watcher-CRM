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
}
