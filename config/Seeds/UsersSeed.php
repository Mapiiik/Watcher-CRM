<?php
declare(strict_types=1);

use Cake\Utility\Text;
use Migrations\AbstractSeed;

/**
 * Users seed.
 */
class UsersSeed extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeds is available here:
     * https://book.cakephp.org/phinx/0/en/seeding.html
     *
     * @return void
     */
    public function run()
    {
        $data = [
            [
                'id' => Text::uuid(),
                'username' => 'admin',
                'password' => '$2y$10$xFQXP4qGYX3e97gRTx.qJ.0nFAzMOmBdRISzgMf0hmrikHR/03txu',
                'email' => 'admin@example.com',
                'role' => 'admin',
                'active' => true,
                'created' => date('Y-m-d H:i:s'),
                'modified' => date('Y-m-d H:i:s'),
            ],
        ];

        $table = $this->table('users');
        $table->insert($data)->save();
    }
}
