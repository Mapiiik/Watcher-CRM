<?php
declare(strict_types=1);

namespace RADIUS\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * RadcheckFixture
 */
class RadcheckFixture extends TestFixture
{
    /**
     * Table name
     *
     * @var string
     */
    public $table = 'radcheck';
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
        'op' => ['type' => 'string', 'length' => 2, 'default' => '==', 'null' => false, 'collate' => null, 'comment' => null, 'precision' => null],
        'value' => ['type' => 'string', 'length' => 253, 'default' => '', 'null' => false, 'collate' => null, 'comment' => null, 'precision' => null],
        'customer_connection_id' => ['type' => 'integer', 'length' => 10, 'default' => '0', 'null' => false, 'comment' => null, 'precision' => null, 'unsigned' => null, 'autoIncrement' => null],
        'modified_by' => ['type' => 'integer', 'length' => 10, 'default' => '0', 'null' => false, 'comment' => null, 'precision' => null, 'unsigned' => null, 'autoIncrement' => null],
        'modified' => ['type' => 'timestampfractional', 'length' => null, 'default' => null, 'null' => true, 'comment' => null, 'precision' => null],
        'created_by' => ['type' => 'integer', 'length' => 10, 'default' => '0', 'null' => false, 'comment' => null, 'precision' => null, 'unsigned' => null, 'autoIncrement' => null],
        'created' => ['type' => 'timestampfractional', 'length' => null, 'default' => null, 'null' => false, 'comment' => null, 'precision' => null],
        'type' => ['type' => 'integer', 'length' => 10, 'default' => '0', 'null' => false, 'comment' => null, 'precision' => null, 'unsigned' => null, 'autoIncrement' => null],
        'customer_id' => ['type' => 'integer', 'length' => 10, 'default' => null, 'null' => true, 'comment' => null, 'precision' => null, 'unsigned' => null, 'autoIncrement' => null],
        'contract_id' => ['type' => 'integer', 'length' => 10, 'default' => null, 'null' => true, 'comment' => null, 'precision' => null, 'unsigned' => null, 'autoIncrement' => null],
        '_indexes' => [
            'radcheck_username' => ['type' => 'index', 'columns' => ['username', 'attribute'], 'length' => []],
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
                'customer_connection_id' => 1,
                'modified_by' => 1,
                'modified' => 1621588330,
                'created_by' => 1,
                'created' => 1621588330,
                'type' => 1,
                'customer_id' => 1,
                'contract_id' => 1,
            ],
        ];
        parent::init();
    }
}
