<?php
declare(strict_types=1);

namespace RADIUS\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * RadacctFixture
 */
class RadacctFixture extends TestFixture
{
    /**
     * Table name
     *
     * @var string
     */
    public $table = 'radacct';
    /**
     * Fields
     *
     * @var array
     */
    // phpcs:disable
    public $fields = [
        'radacctid' => ['type' => 'biginteger', 'length' => 20, 'autoIncrement' => true, 'default' => null, 'null' => false, 'comment' => null, 'precision' => null, 'unsigned' => null],
        'acctsessionid' => ['type' => 'string', 'length' => 32, 'default' => null, 'null' => false, 'collate' => null, 'comment' => null, 'precision' => null],
        'acctuniqueid' => ['type' => 'string', 'length' => 32, 'default' => null, 'null' => false, 'collate' => null, 'comment' => null, 'precision' => null],
        'username' => ['type' => 'string', 'length' => 253, 'default' => null, 'null' => true, 'collate' => null, 'comment' => null, 'precision' => null],
        'realm' => ['type' => 'string', 'length' => 64, 'default' => null, 'null' => true, 'collate' => null, 'comment' => null, 'precision' => null],
        'nasipaddress' => ['type' => 'string', 'length' => 39, 'default' => null, 'null' => false, 'collate' => null, 'comment' => null, 'precision' => null],
        'nasportid' => ['type' => 'string', 'length' => 15, 'default' => null, 'null' => true, 'collate' => null, 'comment' => null, 'precision' => null],
        'nasporttype' => ['type' => 'string', 'length' => 32, 'default' => null, 'null' => true, 'collate' => null, 'comment' => null, 'precision' => null],
        'acctstarttime' => ['type' => 'timestamptimezone', 'length' => null, 'default' => null, 'null' => true, 'comment' => null, 'precision' => null],
        'acctstoptime' => ['type' => 'timestamptimezone', 'length' => null, 'default' => null, 'null' => true, 'comment' => null, 'precision' => null],
        'acctsessiontime' => ['type' => 'biginteger', 'length' => 20, 'default' => null, 'null' => true, 'comment' => null, 'precision' => null, 'unsigned' => null, 'autoIncrement' => null],
        'acctauthentic' => ['type' => 'string', 'length' => 32, 'default' => null, 'null' => true, 'collate' => null, 'comment' => null, 'precision' => null],
        'connectinfo_start' => ['type' => 'string', 'length' => 50, 'default' => null, 'null' => true, 'collate' => null, 'comment' => null, 'precision' => null],
        'connectinfo_stop' => ['type' => 'string', 'length' => 50, 'default' => null, 'null' => true, 'collate' => null, 'comment' => null, 'precision' => null],
        'acctinputoctets' => ['type' => 'biginteger', 'length' => 20, 'default' => null, 'null' => true, 'comment' => null, 'precision' => null, 'unsigned' => null, 'autoIncrement' => null],
        'acctoutputoctets' => ['type' => 'biginteger', 'length' => 20, 'default' => null, 'null' => true, 'comment' => null, 'precision' => null, 'unsigned' => null, 'autoIncrement' => null],
        'calledstationid' => ['type' => 'string', 'length' => 50, 'default' => null, 'null' => true, 'collate' => null, 'comment' => null, 'precision' => null],
        'callingstationid' => ['type' => 'string', 'length' => 50, 'default' => null, 'null' => true, 'collate' => null, 'comment' => null, 'precision' => null],
        'acctterminatecause' => ['type' => 'string', 'length' => 32, 'default' => null, 'null' => true, 'collate' => null, 'comment' => null, 'precision' => null],
        'servicetype' => ['type' => 'string', 'length' => 32, 'default' => null, 'null' => true, 'collate' => null, 'comment' => null, 'precision' => null],
        'framedprotocol' => ['type' => 'string', 'length' => 32, 'default' => null, 'null' => true, 'collate' => null, 'comment' => null, 'precision' => null],
        'framedipaddress' => ['type' => 'string', 'length' => 39, 'default' => null, 'null' => true, 'collate' => null, 'comment' => null, 'precision' => null],
        'acctstartdelay' => ['type' => 'biginteger', 'length' => 20, 'default' => null, 'null' => true, 'comment' => null, 'precision' => null, 'unsigned' => null, 'autoIncrement' => null],
        'acctstopdelay' => ['type' => 'biginteger', 'length' => 20, 'default' => null, 'null' => true, 'comment' => null, 'precision' => null, 'unsigned' => null, 'autoIncrement' => null],
        'groupname' => ['type' => 'string', 'length' => 253, 'default' => null, 'null' => true, 'collate' => null, 'comment' => null, 'precision' => null],
        'xascendsessionsvrkey' => ['type' => 'string', 'length' => 10, 'default' => null, 'null' => true, 'collate' => null, 'comment' => null, 'precision' => null],
        '_indexes' => [
            'radacct_active_user_idx' => ['type' => 'index', 'columns' => ['username'], 'length' => []],
            'radacct_start_user_idx' => ['type' => 'index', 'columns' => ['username', 'acctstarttime'], 'length' => []],
        ],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['radacctid'], 'length' => []],
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
                'radacctid' => 1,
                'acctsessionid' => 'Lorem ipsum dolor sit amet',
                'acctuniqueid' => 'Lorem ipsum dolor sit amet',
                'username' => 'Lorem ipsum dolor sit amet',
                'realm' => 'Lorem ipsum dolor sit amet',
                'nasipaddress' => 'Lorem ipsum dolor sit amet',
                'nasportid' => 'Lorem ipsum d',
                'nasporttype' => 'Lorem ipsum dolor sit amet',
                'acctstarttime' => 1621588337,
                'acctstoptime' => 1621588337,
                'acctsessiontime' => 1,
                'acctauthentic' => 'Lorem ipsum dolor sit amet',
                'connectinfo_start' => 'Lorem ipsum dolor sit amet',
                'connectinfo_stop' => 'Lorem ipsum dolor sit amet',
                'acctinputoctets' => 1,
                'acctoutputoctets' => 1,
                'calledstationid' => 'Lorem ipsum dolor sit amet',
                'callingstationid' => 'Lorem ipsum dolor sit amet',
                'acctterminatecause' => 'Lorem ipsum dolor sit amet',
                'servicetype' => 'Lorem ipsum dolor sit amet',
                'framedprotocol' => 'Lorem ipsum dolor sit amet',
                'framedipaddress' => 'Lorem ipsum dolor sit amet',
                'acctstartdelay' => 1,
                'acctstopdelay' => 1,
                'groupname' => 'Lorem ipsum dolor sit amet',
                'xascendsessionsvrkey' => 'Lorem ip',
            ],
        ];
        parent::init();
    }
}
