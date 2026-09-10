<?php
declare(strict_types=1);

namespace Files\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;
use Override;

/**
 * FileLinksFixture
 *
 * Empty on purpose. What the store holds is what a test just put there, so the fixture is here
 * to have the table emptied between tests rather than to fill it.
 */
class FileLinksFixture extends TestFixture
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
