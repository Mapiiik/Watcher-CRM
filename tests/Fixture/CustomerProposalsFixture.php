<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;
use Override;

/**
 * CustomerProposalsFixture
 *
 * Empty on purpose. What rounds a customer has is what a test just opened, so this is here to have
 * the table emptied between tests rather than to fill it.
 */
class CustomerProposalsFixture extends TestFixture
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
