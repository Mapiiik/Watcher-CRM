<?php
declare(strict_types=1);

use Migrations\BaseMigration;
use Migrations\Migration\IrreversibleMigrationException;

/**
 * API keys kept only as their SHA-256.
 *
 * A key is looked up by its hash from now on, so the clients keep the keys they have and nothing
 * readable is left in the database. A key cannot be shown again, only replaced.
 */
class HashApiTokens extends BaseMigration
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        $this->execute(
            "UPDATE users SET api_token = encode(sha256(convert_to(api_token, 'UTF8')), 'hex')
            WHERE api_token IS NOT NULL AND api_token <> ''",
        );
    }

    /**
     * Down Method.
     *
     * @return void
     * @throws \Migrations\Migration\IrreversibleMigrationException Always, as a hash cannot be turned
     *      back into the key, and running `up` again would hash the hash.
     */
    public function down(): void
    {
        throw new IrreversibleMigrationException('A hash of an API key cannot be turned back into the key.');
    }
}
