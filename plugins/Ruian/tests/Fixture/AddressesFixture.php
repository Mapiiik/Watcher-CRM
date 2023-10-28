<?php
declare(strict_types=1);

namespace Ruian\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * AddressesFixture
 */
class AddressesFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'kod_adm' => 1,
                'obec_kod' => 1,
                'obec_nazev' => 'Lorem ipsum dolor sit amet',
                'momc_kod' => 1,
                'momc_nazev' => 'Lorem ipsum dolor sit amet',
                'mop_kod' => 1,
                'mop_nazev' => 'Lorem ipsum dolor sit amet',
                'cast_obce_kod' => 1,
                'cast_obce_nazev' => 'Lorem ipsum dolor sit amet',
                'ulice_kod' => 1,
                'ulice_nazev' => 'Lorem ipsum dolor sit amet',
                'typ_so' => 'Lorem ipsum dolor sit amet',
                'cislo_domovni' => 1,
                'cislo_orientacni' => 1,
                'cislo_orientacni_znak' => 'Lorem ipsum dolor sit amet',
                'psc' => 1,
                'plati_od' => '2021-05-21',
                'geometry' => 'Lorem ipsum dolor sit amet',
                'geometry_jtsk' => 'Lorem ipsum dolor sit amet',
            ],
        ];
        parent::init();
    }
}
