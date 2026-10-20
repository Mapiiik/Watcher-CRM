<?php
declare(strict_types=1);

use Access\Database\Type\EncryptedType;
use Migrations\BaseMigration;

/**
 * The key of an account's one-time codes is kept encrypted, so that a copy of the database does
 * not give it away, and the hash function it was set up with is kept beside it. Keys set up so
 * far are SHA-1 ones.
 */
class UsersKeepOneTimePasswordKeysEncrypted extends BaseMigration
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        $this->getUpdateBuilder()
            ->update('users')
            ->set('secret_verified', false, 'boolean')
            ->where(['secret_verified IS' => null])
            ->execute();
        $this->table('users')
            ->addColumn('otp_algorithm', 'string', ['default' => 'SHA1', 'null' => false])
            ->changeColumn('secret', 'string', ['null' => true])
            ->changeColumn('secret_verified', 'boolean', ['default' => false, 'null' => false])
            ->update();

        foreach ($this->secrets() as $id => $secret) {
            if (EncryptedType::open($secret) !== null) {
                continue;
            }
            $this->getUpdateBuilder()
                ->update('users')
                ->set('secret', EncryptedType::seal($secret))
                ->where(['id' => $id])
                ->execute();
        }
    }

    /**
     * Down Method.
     *
     * A key that is not an SHA-1 one, or cannot be read, does not fit any more, so its account
     * sets its app up again.
     *
     * @return void
     */
    public function down(): void
    {
        $algorithms = $this->getSelectBuilder()
            ->select(['id', 'otp_algorithm'])
            ->from('users')
            ->execute()
            ->fetchAll('assoc');
        $algorithms = array_column($algorithms, 'otp_algorithm', 'id');

        foreach ($this->secrets() as $id => $secret) {
            $plain = EncryptedType::open($secret);
            $fits = $plain !== null && ($algorithms[$id] ?? 'SHA1') === 'SHA1';
            $values = $fits
                ? ['secret' => $plain]
                : ['secret' => null, 'secret_verified' => false, 'otp_last_timestep' => null];
            $this->getUpdateBuilder()
                ->update('users')
                ->set($values, ['secret_verified' => 'boolean'])
                ->where(['id' => $id])
                ->execute();
            if (!$fits) {
                $this->getDeleteBuilder()->delete('recovery_codes')->where(['user_id' => $id])->execute();
            }
        }

        $this->table('users')
            ->removeColumn('otp_algorithm')
            ->changeColumn('secret', 'string', ['limit' => 32, 'null' => true])
            ->changeColumn('secret_verified', 'boolean', ['default' => null, 'null' => true])
            ->update();
    }

    /**
     * The keys stored, by account.
     *
     * @return array<string, string>
     */
    private function secrets(): array
    {
        $rows = $this->getSelectBuilder()
            ->select(['id', 'secret'])
            ->from('users')
            ->where(['secret IS NOT' => null])
            ->execute()
            ->fetchAll('assoc');

        return array_column($rows, 'secret', 'id');
    }
}
