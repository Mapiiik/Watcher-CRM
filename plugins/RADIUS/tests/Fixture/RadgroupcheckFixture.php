<?php
declare(strict_types=1);

namespace Radius\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * RadgroupcheckFixture
 */
class RadgroupcheckFixture extends TestFixture
{
    /**
     * Table name
     *
     * @var string
     */
    public $table = 'radgroupcheck';
    /**
     * Fields
     *
     * @var array
     */
    // phpcs:disable
    public $fields = [
        'id' => ['type' => 'integer', 'length' => 10, 'autoIncrement' => true, 'default' => null, 'null' => false, 'comment' => null, 'precision' => null, 'unsigned' => null],
        'groupname' => ['type' => 'text', 'length' => null, 'default' => '', 'null' => false, 'collate' => null, 'comment' => null, 'precision' => null],
        'attribute' => ['type' => 'text', 'length' => null, 'default' => '', 'null' => false, 'collate' => null, 'comment' => null, 'precision' => null],
        'op' => ['type' => 'string', 'length' => 2, 'default' => '==', 'null' => false, 'collate' => null, 'comment' => null, 'precision' => null],
        'value' => ['type' => 'text', 'length' => null, 'default' => '', 'null' => false, 'collate' => null, 'comment' => null, 'precision' => null],
        '_indexes' => [
            'radgroupcheck_groupname' => ['type' => 'index', 'columns' => ['groupname', 'attribute'], 'length' => []],
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
                'groupname' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
                'attribute' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
                'op' => 'Lo',
                'value' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
            ],
        ];
        parent::init();
    }
}
