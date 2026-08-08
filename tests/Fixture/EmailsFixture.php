<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;
use Override;

/**
 * EmailsFixture
 *
 * The address is a real one rather than filler text, because the validator asks for an address on
 * every save - a record carrying anything else could not be stored by the application that is under
 * test, and anything sending to it would be handed something a mailer refuses.
 */
class EmailsFixture extends TestFixture
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
                'email' => 'customer@example.com',
                'use_for_billing' => 1,
                'use_for_outages' => 1,
                'use_for_commercial' => 1,
            ],
        ];
        parent::init();
    }
}
