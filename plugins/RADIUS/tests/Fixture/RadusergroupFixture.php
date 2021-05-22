<?php
declare(strict_types=1);

namespace RADIUS\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * RadusergroupFixture
 */
class RadusergroupFixture extends TestFixture
{
    /**
     * Table name
     *
     * @var string
     */
    public $table = 'radusergroup';
    /**
     * Fields
     *
     * @var array
     */
    // phpcs:disable
    public $fields = [
        'username' => ['type' => 'string', 'length' => 64, 'default' => '', 'null' => false, 'collate' => null, 'comment' => null, 'precision' => null],
        'groupname' => ['type' => 'string', 'length' => 64, 'default' => '', 'null' => false, 'collate' => null, 'comment' => null, 'precision' => null],
        'priority' => ['type' => 'integer', 'length' => 10, 'default' => '0', 'null' => false, 'comment' => null, 'precision' => null, 'unsigned' => null, 'autoIncrement' => null],
        '_indexes' => [
            'radusergroup_username' => ['type' => 'index', 'columns' => ['username'], 'length' => []],
        ],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['username'], 'length' => []],
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
                'username' => '1e29d002-c406-4f9b-909d-84199461d08e',
                'groupname' => 'Lorem ipsum dolor sit amet',
                'priority' => 1,
            ],
        ];
        parent::init();
    }
}
