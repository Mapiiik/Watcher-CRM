<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;
use Override;

/**
 * FulltextSearchCustomersFixture
 *
 * Left empty. A test that needs a document lets it be built - by saving, or by rebuilding the
 * table - rather than stating a `tsvector` here that nothing would check against its source.
 */
class FulltextSearchCustomersFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    #[Override]
    public function init(): void
    {
        $this->records = [];
        parent::init();
    }
}
