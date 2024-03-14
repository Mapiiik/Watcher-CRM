<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use App\Model\Enum\LoginRights;
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
                'login' => 'Lorem ipsum dolor sit amet',
                'password' => 'Lorem ipsum dolor sit amet',
                'rights' => LoginRights::User,
                'locked' => 1,
                'last_granted' => 1698516611,
                'last_granted_ip' => 'Lorem ipsum dolor sit amet',
                'last_denied' => 1698516611,
                'last_denied_ip' => 'Lorem ipsum dolor sit amet',
                'modified' => 1698516611,
                'created' => 1698516611,
                'customer_id' => '403bab0e-52cd-4a8e-83f8-43c2457d0481',
                'id' => '5457ac8a-1c92-4d5f-a035-41de7fad3509',
                'created_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
                'modified_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
            ],
        ];
        parent::init();
    }
}
