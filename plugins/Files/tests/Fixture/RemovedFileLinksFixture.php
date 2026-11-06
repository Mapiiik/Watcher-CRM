<?php
declare(strict_types=1);

namespace Files\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;
use Override;

/**
 * RemovedFileLinksFixture
 *
 * Empty on purpose, for the same reason as the links themselves: what is in the trash is what a
 * test just removed.
 */
class RemovedFileLinksFixture extends TestFixture
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
