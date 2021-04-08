<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * LoginsFixture
 */
class LoginsFixture extends TestFixture
{
    /**
     * Fields
     *
     * @var array
     */
    // phpcs:disable
    public $fields = [
        'id' => ['type' => 'integer', 'length' => 10, 'autoIncrement' => true, 'default' => null, 'null' => false, 'comment' => null, 'precision' => null, 'unsigned' => null],
        'customer_id' => ['type' => 'integer', 'length' => 10, 'default' => '0', 'null' => false, 'comment' => null, 'precision' => null, 'unsigned' => null, 'autoIncrement' => null],
        'login' => ['type' => 'string', 'length' => null, 'default' => null, 'null' => false, 'collate' => null, 'comment' => null, 'precision' => null],
        'password' => ['type' => 'string', 'length' => null, 'default' => null, 'null' => false, 'collate' => null, 'comment' => null, 'precision' => null],
        'rights' => ['type' => 'smallinteger', 'length' => 5, 'default' => '0', 'null' => false, 'comment' => null, 'precision' => null, 'unsigned' => null],
        'locked' => ['type' => 'smallinteger', 'length' => 5, 'default' => '0', 'null' => false, 'comment' => null, 'precision' => null, 'unsigned' => null],
        'last_granted' => ['type' => 'timestampfractional', 'length' => null, 'default' => null, 'null' => true, 'comment' => null, 'precision' => 6],
        'last_granted_ip' => ['type' => 'string', 'length' => 39, 'default' => null, 'null' => true, 'collate' => null, 'comment' => null, 'precision' => null],
        'last_denied' => ['type' => 'timestampfractional', 'length' => null, 'default' => null, 'null' => true, 'comment' => null, 'precision' => 6],
        'last_denied_ip' => ['type' => 'string', 'length' => 39, 'default' => null, 'null' => true, 'collate' => null, 'comment' => null, 'precision' => null],
        'modified_by' => ['type' => 'integer', 'length' => 10, 'default' => '0', 'null' => false, 'comment' => null, 'precision' => null, 'unsigned' => null, 'autoIncrement' => null],
        'modified' => ['type' => 'timestampfractional', 'length' => null, 'default' => null, 'null' => true, 'comment' => null, 'precision' => 6],
        'created_by' => ['type' => 'integer', 'length' => 10, 'default' => '0', 'null' => false, 'comment' => null, 'precision' => null, 'unsigned' => null, 'autoIncrement' => null],
        'created' => ['type' => 'timestampfractional', 'length' => null, 'default' => null, 'null' => false, 'comment' => null, 'precision' => 6],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
            'login_index' => ['type' => 'unique', 'columns' => ['login'], 'length' => []],
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
                'customer_id' => 1,
                'login' => 'Lorem ipsum dolor sit amet',
                'password' => 'Lorem ipsum dolor sit amet',
                'rights' => 1,
                'locked' => 1,
                'last_granted' => 1617875337,
                'last_granted_ip' => 'Lorem ipsum dolor sit amet',
                'last_denied' => 1617875337,
                'last_denied_ip' => 'Lorem ipsum dolor sit amet',
                'modified_by' => 1,
                'modified' => 1617875337,
                'created_by' => 1,
                'created' => 1617875337,
            ],
        ];
        parent::init();
    }
}
