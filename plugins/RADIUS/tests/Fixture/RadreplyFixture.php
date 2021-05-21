<?php
declare(strict_types=1);

namespace RADIUS\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * RadreplyFixture
 */
class RadreplyFixture extends TestFixture
{
    /**
     * Table name
     *
     * @var string
     */
    public $table = 'radreply';
    /**
     * Fields
     *
     * @var array
     */
    // phpcs:disable
    public $fields = [
        'id' => ['type' => 'integer', 'length' => 10, 'autoIncrement' => true, 'default' => null, 'null' => false, 'comment' => null, 'precision' => null, 'unsigned' => null],
        'username' => ['type' => 'string', 'length' => 64, 'default' => '', 'null' => false, 'collate' => null, 'comment' => null, 'precision' => null],
        'attribute' => ['type' => 'string', 'length' => 64, 'default' => '', 'null' => false, 'collate' => null, 'comment' => null, 'precision' => null],
        'op' => ['type' => 'string', 'length' => 2, 'default' => '=', 'null' => false, 'collate' => null, 'comment' => null, 'precision' => null],
        'value' => ['type' => 'string', 'length' => 253, 'default' => '', 'null' => false, 'collate' => null, 'comment' => null, 'precision' => null],
        '_indexes' => [
            'radreply_username' => ['type' => 'index', 'columns' => ['username', 'attribute'], 'length' => []],
        ],
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
                'attribute' => 'Lorem ipsum dolor sit amet',
                'op' => 'Lo',
                'value' => 'Lorem ipsum dolor sit amet',
            ],
        ];
        parent::init();
    }
}
