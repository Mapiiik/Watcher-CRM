<?php
declare(strict_types=1);

namespace RADIUS\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * RadpostauthFixture
 */
class RadpostauthFixture extends TestFixture
{
    /**
     * Table name
     *
     * @var string
     */
    public $table = 'radpostauth';
    /**
     * Fields
     *
     * @var array
     */
    // phpcs:disable
    public $fields = [
        'id' => ['type' => 'biginteger', 'length' => 20, 'autoIncrement' => true, 'default' => null, 'null' => false, 'comment' => null, 'precision' => null, 'unsigned' => null],
        'username' => ['type' => 'string', 'length' => 253, 'default' => null, 'null' => false, 'collate' => null, 'comment' => null, 'precision' => null],
        'pass' => ['type' => 'string', 'length' => 128, 'default' => null, 'null' => true, 'collate' => null, 'comment' => null, 'precision' => null],
        'reply' => ['type' => 'string', 'length' => 32, 'default' => null, 'null' => true, 'collate' => null, 'comment' => null, 'precision' => null],
        'calledstationid' => ['type' => 'string', 'length' => 50, 'default' => null, 'null' => true, 'collate' => null, 'comment' => null, 'precision' => null],
        'callingstationid' => ['type' => 'string', 'length' => 50, 'default' => null, 'null' => true, 'collate' => null, 'comment' => null, 'precision' => null],
        'authdate' => ['type' => 'timestamptimezone', 'length' => null, 'default' => '2009-07-20 20:38:34.891225+02', 'null' => false, 'comment' => null, 'precision' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
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
                'id' => 1,
                'username' => 'Lorem ipsum dolor sit amet',
                'pass' => 'Lorem ipsum dolor sit amet',
                'reply' => 'Lorem ipsum dolor sit amet',
                'calledstationid' => 'Lorem ipsum dolor sit amet',
                'callingstationid' => 'Lorem ipsum dolor sit amet',
                'authdate' => 1621588375,
            ],
        ];
        parent::init();
    }
}
