<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;
use Override;

/**
 * TaskCollaboratorsFixture
 *
 * Empty on purpose. Who works on a task beside whoever holds it is what the tests about it put
 * there themselves, and a link standing here would answer half of every one of those questions
 * before it was asked. It is declared all the same, so that what a test writes is cleared before
 * the next one runs.
 */
class TaskCollaboratorsFixture extends TestFixture
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
