<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * AppUsersFixture
 */
class AppUsersFixture extends TestFixture
{
    /**
     * Table name
     *
     * @var string
     */
    public string $table = 'users';
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'username' => 'Lorem ipsum dolor sit amet',
                'email' => 'Lorem ipsum dolor sit amet',
                'password' => 'Lorem ipsum dolor sit amet',
                'first_name' => 'Lorem ipsum dolor sit amet',
                'last_name' => 'Lorem ipsum dolor sit amet',
                'token' => 'Lorem ipsum dolor sit amet',
                'token_expires' => 1698515383,
                'api_token' => 'Lorem ipsum dolor sit amet',
                'activation_date' => 1698515383,
                'tos_date' => 1698515383,
                'active' => 1,
                'is_superuser' => 1,
                'role' => 'Lorem ipsum dolor sit amet',
                'created' => 1698515383,
                'modified' => 1698515383,
                'secret' => 'Lorem ipsum dolor sit amet',
                'secret_verified' => 1,
                'additional_data' => '',
                'last_login' => 1698515383,
                'nid' => 1,
                'user_settings' => '',
                'customer_id' => null,
                'id' => '11edb519-be76-4d66-aea0-34188d31eae1',
            ],
        ];
        parent::init();
    }
}
