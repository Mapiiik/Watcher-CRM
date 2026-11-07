<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * The times of the recovery codes say which moment they are, like every other time on file.
 *
 * A column without a zone holds what the application wrote, which is the time of its own zone and
 * not of the connection, so that zone is what they are read from. Read from any other, every record
 * would move by the difference. A column that has its zone already is left alone, as reading it
 * again would do just that. Afterwards the schema cache has to be cleared, or the application goes
 * on writing a local time with no zone.
 */
class RecoveryCodesKeepTheirTimeZone extends BaseMigration
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        foreach (['used', 'created'] as $column) {
            if ($this->typeOf($column) === 'timestamp without time zone') {
                $this->execute(sprintf(
                    "ALTER TABLE recovery_codes ALTER COLUMN %s TYPE timestamptz USING %s AT TIME ZONE '%s'",
                    $column,
                    $column,
                    date_default_timezone_get(),
                ));
            }
        }
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        foreach (['used', 'created'] as $column) {
            if ($this->typeOf($column) === 'timestamp with time zone') {
                $this->execute(sprintf(
                    "ALTER TABLE recovery_codes ALTER COLUMN %s TYPE timestamp USING %s AT TIME ZONE '%s'",
                    $column,
                    $column,
                    date_default_timezone_get(),
                ));
            }
        }
    }

    /**
     * What the database says the column is.
     *
     * @param string $column The column of the recovery codes.
     * @return string
     */
    private function typeOf(string $column): string
    {
        $row = $this->fetchRow(sprintf(
            "SELECT data_type FROM information_schema.columns
            WHERE table_schema = current_schema() AND table_name = 'recovery_codes' AND column_name = '%s'",
            $column,
        ));

        return (string)($row['data_type'] ?? '');
    }
}
