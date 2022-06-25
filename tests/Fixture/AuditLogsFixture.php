<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * AuditLogsFixture
 */
class AuditLogsFixture extends TestFixture
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
                'id' => 'f5865fe3-ce6c-491a-ae9e-91730165523c',
                'transaction' => 'fe3c5317-fa1a-4587-91eb-853c357f076a',
                'type' => 'Lorem',
                'primary_key' => '',
                'display_value' => 'Lorem ipsum dolor sit amet',
                'source' => 'Lorem ipsum dolor sit amet',
                'parent_source' => 'Lorem ipsum dolor sit amet',
                'username' => 'Lorem ipsum dolor sit amet',
                'original' => '',
                'changed' => '',
                'meta' => '',
                'created' => 1656187392,
            ],
        ];
        parent::init();
    }
}
