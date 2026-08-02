<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * HistoricalConnectionsFixture
 *
 * Left empty. The history is only ever built by the update, so the tests that
 * matter start from nothing and let the updater fill it in.
 */
class HistoricalConnectionsFixture extends TestFixture
{
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
