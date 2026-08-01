<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ConnectionHistoryFixture
 *
 * Left empty. The history is only ever built by the update, so the tests that
 * matter start from nothing and let the updater fill it in.
 */
class ConnectionHistoryFixture extends TestFixture
{
    /**
     * Table name, singular like the table itself rather than the inflected
     * `connection_histories` a fixture would otherwise look for.
     *
     * @var string
     */
    public string $table = 'connection_history';

    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [];
        parent::init();
    }
}
