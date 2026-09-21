<?php
declare(strict_types=1);

namespace WorkReports\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;
use Override;

/**
 * WorkReportsFixture
 *
 * Empty on purpose. What the table holds is what a test just put there, so the fixture is
 * here to have the table emptied between tests rather than to fill it.
 */
class WorkReportsFixture extends TestFixture
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
