<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * LoginsFixture
 */
class LoginsFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'customer_id' => 1,
                'login' => 'Lorem ipsum dolor sit amet',
                'password' => 'Lorem ipsum dolor sit amet',
                'rights' => 1,
                'locked' => 1,
                'last_granted' => 1636113487,
                'last_granted_ip' => 'Lorem ipsum dolor sit amet',
                'last_denied' => 1636113487,
                'last_denied_ip' => 'Lorem ipsum dolor sit amet',
                'modified_by' => 1,
                'modified' => 1636113487,
                'created_by' => 1,
                'created' => 1636113487,
            ],
        ];
        parent::init();
    }
}
