<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;
use Override;

/**
 * PhonesFixture
 *
 * The number is a real one rather than filler text, because the table's own rules read it on every
 * save - a record carrying anything else could not be stored by the application that is under test.
 */
class PhonesFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    #[Override]
    public function init(): void
    {
        $this->records = [
            [
                'customer_id' => '403bab0e-52cd-4a8e-83f8-43c2457d0481',
                'phone' => '+420 601 234 567',
            ],
        ];
        parent::init();
    }
}
