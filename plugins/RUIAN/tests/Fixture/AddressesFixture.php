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
     * Fields
     *
     * @var array
     */
    // phpcs:disable
    public $fields = [
        'kod_adm' => ['type' => 'integer', 'length' => 10, 'autoIncrement' => true, 'default' => null, 'null' => false, 'comment' => null, 'precision' => null, 'unsigned' => null],
        'obec_kod' => ['type' => 'integer', 'length' => 10, 'default' => null, 'null' => true, 'comment' => null, 'precision' => null, 'unsigned' => null, 'autoIncrement' => null],
        'obec_nazev' => ['type' => 'string', 'length' => null, 'default' => null, 'null' => true, 'collate' => null, 'comment' => null, 'precision' => null],
        'momc_kod' => ['type' => 'integer', 'length' => 10, 'default' => null, 'null' => true, 'comment' => null, 'precision' => null, 'unsigned' => null, 'autoIncrement' => null],
        'momc_nazev' => ['type' => 'string', 'length' => null, 'default' => null, 'null' => true, 'collate' => null, 'comment' => null, 'precision' => null],
        'mop_kod' => ['type' => 'integer', 'length' => 10, 'default' => null, 'null' => true, 'comment' => null, 'precision' => null, 'unsigned' => null, 'autoIncrement' => null],
        'mop_nazev' => ['type' => 'string', 'length' => null, 'default' => null, 'null' => true, 'collate' => null, 'comment' => null, 'precision' => null],
        'cast_obce_kod' => ['type' => 'integer', 'length' => 10, 'default' => null, 'null' => true, 'comment' => null, 'precision' => null, 'unsigned' => null, 'autoIncrement' => null],
        'cast_obce_nazev' => ['type' => 'string', 'length' => null, 'default' => null, 'null' => true, 'collate' => null, 'comment' => null, 'precision' => null],
        'ulice_kod' => ['type' => 'integer', 'length' => 10, 'default' => null, 'null' => true, 'comment' => null, 'precision' => null, 'unsigned' => null, 'autoIncrement' => null],
        'ulice_nazev' => ['type' => 'string', 'length' => null, 'default' => null, 'null' => true, 'collate' => null, 'comment' => null, 'precision' => null],
        'typ_so' => ['type' => 'string', 'length' => null, 'default' => null, 'null' => true, 'collate' => null, 'comment' => null, 'precision' => null],
        'cislo_domovni' => ['type' => 'integer', 'length' => 10, 'default' => null, 'null' => true, 'comment' => null, 'precision' => null, 'unsigned' => null, 'autoIncrement' => null],
        'cislo_orientacni' => ['type' => 'integer', 'length' => 10, 'default' => null, 'null' => true, 'comment' => null, 'precision' => null, 'unsigned' => null, 'autoIncrement' => null],
        'cislo_orientacni_znak' => ['type' => 'string', 'length' => null, 'default' => null, 'null' => true, 'collate' => null, 'comment' => null, 'precision' => null],
        'psc' => ['type' => 'integer', 'length' => 10, 'default' => null, 'null' => true, 'comment' => null, 'precision' => null, 'unsigned' => null, 'autoIncrement' => null],
        'plati_od' => ['type' => 'date', 'length' => null, 'default' => null, 'null' => true, 'comment' => null, 'precision' => null],
        'geometry' => ['type' => 'string', 'length' => null, 'default' => null, 'null' => true, 'collate' => null, 'comment' => null, 'precision' => null],
        'geometry_jtsk' => ['type' => 'string', 'length' => null, 'default' => null, 'null' => true, 'collate' => null, 'comment' => null, 'precision' => null],
        '_indexes' => [
            'idx_psc' => ['type' => 'index', 'columns' => ['psc'], 'length' => []],
            'idx_addresses_geometry' => ['type' => 'index', 'columns' => ['geometry'], 'length' => []],
            'idx_addresses_geometry_jtsk' => ['type' => 'index', 'columns' => ['geometry_jtsk'], 'length' => []],
        ],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['kod_adm'], 'length' => []],
        ],
    ];
    // phpcs:enable
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
