<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AlterDealerOnCustomers extends AbstractMigration
{
    /**
     * Up Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-up-method
     *
     * @return void
     */
    public function up(): void
    {
        $this->execute('ALTER TABLE customers ALTER COLUMN dealer DROP DEFAULT');
        $this->execute(
            'ALTER TABLE customers ALTER COLUMN dealer TYPE smallint'
                . ' USING CASE WHEN dealer = TRUE THEN 1 ELSE 0 END'
        );
        $this->execute('ALTER TABLE customers ALTER COLUMN dealer SET DEFAULT 0');
        $this->execute('ALTER TABLE customers ALTER COLUMN dealer SET NOT NULL');
    }

    /**
     * Down Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-down-method
     *
     * @return void
     */
    public function down(): void
    {
        $this->execute('ALTER TABLE customers ALTER COLUMN dealer DROP DEFAULT');
        $this->execute(
            'ALTER TABLE customers ALTER COLUMN dealer TYPE boolean'
                . ' USING CASE WHEN dealer = 0 THEN FALSE ELSE TRUE END'
        );
        $this->execute('ALTER TABLE customers ALTER COLUMN dealer SET DEFAULT false');
        $this->execute('ALTER TABLE customers ALTER COLUMN dealer SET NOT NULL');
    }
}
