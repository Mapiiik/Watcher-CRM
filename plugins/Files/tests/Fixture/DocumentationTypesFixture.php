<?php
declare(strict_types=1);

namespace Files\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;
use Override;

/**
 * DocumentationTypesFixture
 *
 * Empty on purpose. A folder means nothing without the vocabulary of the application filing it,
 * so a test says what it is about and the fixture is here to have the table emptied afterwards.
 */
class DocumentationTypesFixture extends TestFixture
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
